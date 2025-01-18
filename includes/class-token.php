<?php
/**
* Token class for Anchor plugin.
*
* Handles temporary tokens for email verification and password reset.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Token {


	private $wpdb;

	private $table;

	const TYPE_VERIFY_EMAIL   = 'verify_email';
	const TYPE_RESET_PASSWORD = 'reset_password';

	const EXPIRY_VERIFY_EMAIL   = 24;
	const EXPIRY_RESET_PASSWORD = 1;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = ANCHOR_TABLE_PREFIX . 'member_tokens';

		add_action( 'anchor_cleanup_tokens', [ $this, 'cleanup_expired' ] );

		if ( ! wp_next_scheduled( 'anchor_cleanup_tokens' ) ) {
			wp_schedule_event( time(), 'daily', 'anchor_cleanup_tokens' );
		}
	}

	/**
	* Generate a new token.
	*
	* @param int    $member_id    Member ID.
	* @param string $type         Token type.
	* @param int    $expiry_hours Token expiry in hours.
	* @return string|false Token string on success, false on failure.
	*/
	public function generate( $member_id, $type, $expiry_hours = null ) {
		$this->invalidate_existing( $member_id, $type );

		if ( is_null( $expiry_hours ) ) {
			switch ( $type ) {
				case self::TYPE_VERIFY_EMAIL:
					$expiry_hours = self::EXPIRY_VERIFY_EMAIL;
					break;
				case self::TYPE_RESET_PASSWORD:
					$expiry_hours = self::EXPIRY_RESET_PASSWORD;
					break;
				default:
					$expiry_hours = 24;
			}
		}

		$token      = wp_generate_password( 32, false );
		$token_hash = hash( 'sha256', $token );
		$expires_at = gmdate( 'Y-m-d H:i:s', time() + ( HOUR_IN_SECONDS * $expiry_hours ) );

		$result = $this->wpdb->insert(
			$this->table,
			[
				'member_id'  => $member_id,
				'token_hash' => $token_hash,
				'token_type' => $type,
				'created_at' => current_time( 'mysql' ),
				'expires_at' => $expires_at,
			],
			[ '%d', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $result ) {
			return false;
		}

		return $token;
	}

	/**
	* Validate a token.
	*
	* @param string $token Token string.
	* @param string $type  Expected token type.
	* @return int|false Member ID if valid, false otherwise.
	*/
	public function validate( $token, $type ) {
		if ( empty( $token ) ) {
			return false;
		}

		$token_hash = hash( 'sha256', $token );

		$token_row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE token_hash = %s
				AND token_type = %s
				AND expires_at > %s
				AND used_at IS NULL",
				$token_hash,
				$type,
				current_time( 'mysql' )
			)
		);

		if ( ! $token_row ) {
			return false;
		}

		return (int) $token_row->member_id;
	}

	/**
	* Mark a token as used.
	*
	* @param string $token Token string.
	* @return bool True on success.
	*/
	public function use_token( $token ) {
		$token_hash = hash( 'sha256', $token );

		$result = $this->wpdb->update(
			$this->table,
			[ 'used_at' => current_time( 'mysql' ) ],
			[ 'token_hash' => $token_hash ],
			[ '%s' ],
			[ '%s' ]
		);

		return $result !== false;
	}

	/**
	* Invalidate existing tokens of a type for a member.
	*
	* @param int    $member_id Member ID.
	* @param string $type      Token type.
	* @return bool True on success.
	*/
	public function invalidate_existing( $member_id, $type ) {
		$result = $this->wpdb->update(
			$this->table,
			[ 'used_at' => current_time( 'mysql' ) ],
			[
				'member_id'  => $member_id,
				'token_type' => $type,
			],
			[ '%s' ],
			[ '%d', '%s' ]
		);

		return $result !== false;
	}

	/**
	* Delete all tokens for a member.
	*
	* @param int $member_id Member ID.
	* @return bool True on success.
	*/
	public function delete_all_for_member( $member_id ) {
		$result = $this->wpdb->delete(
			$this->table,
			[ 'member_id' => $member_id ],
			[ '%d' ]
		);

		return $result !== false;
	}

	// Cleanup expired and used tokens.
	public function cleanup_expired() {
		// Delete expired tokens
		$this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->table} WHERE expires_at < %s",
				current_time( 'mysql' )
			)
		);

		// Delete used tokens older than 7 days
		$this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$this->table} WHERE used_at IS NOT NULL AND used_at < %s",
				gmdate( 'Y-m-d H:i:s', time() - ( 7 * DAY_IN_SECONDS ) )
			)
		);
	}

	/**
	* Get token info (for debugging/admin purposes).
	*
	* @param string $token Token string.
	* @return object|null Token object or null.
	*/
	public function get_info( $token ) {
		$token_hash = hash( 'sha256', $token );

		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT ID, member_id, token_type, created_at, expires_at, used_at
				FROM {$this->table}
				WHERE token_hash = %s",
				$token_hash
			)
		);
	}

	/**
	* Check if a member has an active token of a type.
	*
	* @param int    $member_id Member ID.
	* @param string $type      Token type.
	* @return bool True if active token exists.
	*/
	public function has_active_token( $member_id, $type ) {
		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT ID FROM {$this->table}
				WHERE member_id = %d
				AND token_type = %s
				AND expires_at > %s
				AND used_at IS NULL
				LIMIT 1",
				$member_id,
				$type,
				current_time( 'mysql' )
			)
		);

		return ! is_null( $exists );
	}
}
