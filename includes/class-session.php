<?php
/**
* Session class for Anchor plugin.
*
* Handles member session management.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Session {


	private $wpdb;

	private $table;

	const COOKIE_NAME = 'anchor_member_auth';

	private static $current_member_id = null;

	private static $current_member = null;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = ANCHOR_TABLE_PREFIX . 'member_sessions';

		add_action( 'init', [ $this, 'validate_current_session' ], 1 );

		add_action( 'anchor_cleanup_sessions', [ $this, 'cleanup_expired' ] );

		if ( ! wp_next_scheduled( 'anchor_cleanup_sessions' ) ) {
			wp_schedule_event( time(), 'daily', 'anchor_cleanup_sessions' );
		}
	}

	/**
	* Create a new session for a member.
	*
	* @param int  $member_id Member ID.
	* @param bool $remember  Whether to extend session duration.
	* @return string|false Session token on success, false on failure.
	*/
	public function create( $member_id, $remember = false ) {
		$token      = wp_generate_password( 43, false );
		$token_hash = hash( 'sha256', $token );

		$settings    = new Settings();
		$expiry_days = (int) $settings->get( 'session_expiry_days', 7 );

		// Extended session for "remember me"
		// TODO add this in plugin settings
		if ( $remember ) {
			$expiry_days = 90; 
		}

		$expires_at = gmdate( 'Y-m-d H:i:s', time() + ( DAY_IN_SECONDS * $expiry_days ) );
		$now        = current_time( 'mysql' );

		$result = $this->wpdb->insert(
			$this->table,
			[
				'member_id'     => $member_id,
				'session_token' => $token_hash,
				'ip_address'    => $this->get_client_ip(),
				'user_agent'    => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '',
				'created_at'    => $now,
				'expires_at'    => $expires_at,
				'last_activity' => $now,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $result ) {
			return false;
		}

		$this->set_cookie( $token, $expiry_days );

		self::$current_member_id = $member_id;

		return $token;
	}

	/**
	* Validate a session token.
	*
	* @param string $token Session token.
	* @return int|false Member ID if valid, false otherwise.
	*/
	public function validate( $token ) {
		if ( empty( $token ) ) {
			return false;
		}

		$token_hash = hash( 'sha256', $token );

		$session = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT s.*, m.status as member_status
				FROM {$this->table} s
				INNER JOIN " . ANCHOR_TABLE_PREFIX . "members m ON s.member_id = m.ID
				WHERE s.session_token = %s AND s.expires_at > %s",
				$token_hash,
				current_time( 'mysql' )
			)
		);

		if ( ! $session ) {
			return false;
		}

		// Check member status
		if ( $session->member_status !== 'active' ) {
			$this->destroy_by_token( $token );
			return false;
		}

		// Update last activity
		$this->wpdb->update(
			$this->table,
			[ 'last_activity' => current_time( 'mysql' ) ],
			[ 'ID' => $session->ID ],
			[ '%s' ],
			[ '%d' ]
		);

		return (int) $session->member_id;
	}

	/**
	* Validate the current session from cookie.
	*/
	public function validate_current_session() {
		if ( self::$current_member_id !== null ) {
			return; // Is already validated
		}

		$token = $this->get_cookie();

		if ( ! $token ) {
			return;
		}

		$member_id = $this->validate( $token );

		if ( $member_id ) {
			self::$current_member_id = $member_id;
		} else {
			$this->clear_cookie(); // Invalid session
		}
	}

	/**
	* Destroy a session by token.
	*
	* @param string $token Session token.
	* @return bool True on success.
	*/
	public function destroy_by_token( $token ) {
		$token_hash = hash( 'sha256', $token );

		$result = $this->wpdb->delete(
			$this->table,
			[ 'session_token' => $token_hash ],
			[ '%s' ]
		);

		return $result !== false;
	}

	/**
	* Destroy the current session.
	*
	* @return bool True on success.
	*/
	public function destroy_current() {
		$token = $this->get_cookie();

		if ( $token ) {
			$this->destroy_by_token( $token );
		}

		$this->clear_cookie();

		self::$current_member_id = null;
		self::$current_member    = null;

		return true;
	}

	/**
	* Destroy all sessions for a member.
	*
	* @param int $member_id Member ID.
	* @return bool True on success.
	*/
	public function destroy_all_for_member( $member_id ) {
		$result = $this->wpdb->delete(
			$this->table,
			[ 'member_id' => $member_id ],
			[ '%d' ]
		);

		// Clear current session if it belongs to this member
		if ( self::$current_member_id === $member_id ) {
			$this->clear_cookie();
			self::$current_member_id = null;
			self::$current_member    = null;
		}

		return $result !== false;
	}

	/**
	* Get all sessions for a member.
	*
	* @param int $member_id Member ID.
	* @return array Array of session objects.
	*/
	public function get_member_sessions( $member_id ) {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT ID, ip_address, user_agent, created_at, last_activity, expires_at
				FROM {$this->table}
				WHERE member_id = %d AND expires_at > %s
				ORDER BY last_activity DESC",
				$member_id,
				current_time( 'mysql' )
			)
		);
	}

	/**
	* Get session count for a member.
	*
	* @param int $member_id Member ID.
	* @return int Session count.
	*/
	public function get_member_session_count( $member_id ) {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE member_id = %d AND expires_at > %s",
				$member_id,
				current_time( 'mysql' )
			)
		);
	}

	/**
	* Check if a member is logged in.
	*
	* @return bool True if logged in.
	*/
	public function is_logged_in() {
		$this->validate_current_session();
		return self::$current_member_id !== null;
	}

	/**
	* Get the current member ID.
	*
	* @return int|null Member ID or null.
	*/
	public function get_current_member_id() {
		$this->validate_current_session();
		return self::$current_member_id;
	}

	/**
	* Get the current member object.
	*
	* @return object|null Member object or null.
	*/
	public function get_current_member() {
		if ( ! $this->is_logged_in() ) {
			return null;
		}

		if ( self::$current_member === null ) {
			$member = new Member();
			self::$current_member = $member->get( self::$current_member_id );
		}

		return self::$current_member;
	}

	/**
	* Cleanup expired sessions.
	*/
	public function cleanup_expired() {
		$this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->table} WHERE expires_at < %s",
				current_time( 'mysql' )
			)
		);
	}

	/**
	* Set the session cookie.
	*
	* @param string $token       Session token.
	* @param int    $expiry_days Cookie expiry in days.
	*/
	private function set_cookie( $token, $expiry_days ) {
		$secure   = is_ssl();
		$expires  = time() + ( DAY_IN_SECONDS * $expiry_days );

		setcookie(
			self::COOKIE_NAME,
			$token,
			[
				'expires'  => $expires,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);

		// Set in $_COOKIE for immediate access
		$_COOKIE[ self::COOKIE_NAME ] = $token;
	}

	/**
	* Get the session cookie value.
	*
	* @return string|null Cookie value or null.
	*/
	private function get_cookie() {
		return isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : null;
	}

	/**
	* Clear the session cookie.
	*/
	private function clear_cookie() {
		setcookie(
			self::COOKIE_NAME,
			'',
			[
				'expires'  => time() - YEAR_IN_SECONDS,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);

		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	/**
	* Get the client IP address.
	*
	* @return string IP address.
	*/
	private function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			// Get the first IP if there are multiple
			if ( strpos( $ip, ',' ) !== false ) {
				$ip = trim( explode( ',', $ip )[0] );
			}
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// Validate IP
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '';
	}
}
