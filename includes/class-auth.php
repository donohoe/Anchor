<?php
/**
* Auth class for Anchor plugin.
*
* Handles member authentication logic.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Auth class.
*/
class Auth {

	/**
	* Rate limiting transient prefix.
	*/
	const RATE_LIMIT_PREFIX = 'anchor_login_attempts_';

	public function __construct() {
		add_action( 'init', [ $this, 'process_logout' ] );
	}

	/**
	* Attempt to log in a member.
	*
	* @param string $username Username or email.
	* @param string $password Password.
	* @param bool   $remember Remember me checkbox.
	* @return int|\WP_Error Member ID on success, WP_Error on failure.
	*/
	public function login( $username, $password, $remember = false ) {
		$username = sanitize_text_field( $username );

		do_action( 'anchor_before_login_attempt', $username );

		// Check rate limiting
		$rate_limit_error = $this->check_rate_limit( $username );
		if ( is_wp_error( $rate_limit_error ) ) {
			return $rate_limit_error;
		}

		// Find member by username or email
		$member_handler = new Member();
		$member         = null;

		if ( is_email( $username ) ) {
			$member = $member_handler->get_by( 'email', $username );
		} else {
			$member = $member_handler->get_by( 'username', $username );
		}

		if ( ! $member ) {
			$this->record_failed_attempt( $username );
			do_action( 'anchor_login_failed', $username, 'invalid_credentials' );
			return new \WP_Error( 'invalid_credentials', __( 'Invalid username or password.', 'anchor' ) );
		}

		// Verify password
		if ( ! wp_check_password( $password, $member->password_hash, $member->ID ) ) {
			$this->record_failed_attempt( $username );
			do_action( 'anchor_login_failed', $username, 'invalid_credentials' );
			return new \WP_Error( 'invalid_credentials', __( 'Invalid username or password.', 'anchor' ) );
		}

		// Check member status
		if ( $member->status === 'suspended' ) {
			do_action( 'anchor_login_failed', $username, 'account_suspended' );
			return new \WP_Error( 'account_suspended', __( 'Your account has been suspended.', 'anchor' ) );
		}

		if ( $member->status === 'deleted' ) {
			do_action( 'anchor_login_failed', $username, 'account_deleted' );
			return new \WP_Error( 'account_deleted', __( 'This account no longer exists.', 'anchor' ) );
		}

		// Check email verification requirement
		$settings = new Settings();
		if ( $settings->get( 'require_email_verification' ) && ! $member->email_verified ) {
			do_action( 'anchor_login_failed', $username, 'email_not_verified' );
			return new \WP_Error(
				'email_not_verified',
				__( 'Please verify your email address before logging in.', 'anchor' )
			);
		}

		// Check if login is allowed via filter
		$can_login = apply_filters( 'anchor_can_login', true, $member->ID, $member );
		if ( ! $can_login ) {
			do_action( 'anchor_login_failed', $username, 'login_prevented' );
			return new \WP_Error( 'login_prevented', __( 'Login is not allowed for this account.', 'anchor' ) );
		}

		// Clear rate limiting on successful login
		$this->clear_rate_limit( $username );

		// Create session
		$session = new Session();
		$token   = $session->create( $member->ID, $remember );

		if ( ! $token ) {
			return new \WP_Error( 'session_error', __( 'Could not create session.', 'anchor' ) );
		}

		// Update last login
		$member_handler->update_last_login( $member->ID );

		do_action( 'anchor_login_success', $member->ID, $remember );

		return $member->ID;
	}

	/**
	* Log out the current member.
	*
	* @return bool True on success.
	*/
	public function logout() {
		$session   = new Session();
		$member_id = $session->get_current_member_id();

		if ( $member_id ) {
			do_action( 'anchor_logout', $member_id );
		}

		return $session->destroy_current();
	}

	/**
	* Process logout if logout action is requested.
	*/
	public function process_logout() {
		if ( isset( $_GET['anchor_logout'] ) && isset( $_GET['_wpnonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'anchor_logout' ) ) {
				$this->logout();

				$settings = new Settings();
				$redirect = $settings->get( 'default_logout_redirect_url', '/' );
				$redirect = apply_filters( 'anchor_logout_redirect', $redirect );

				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}

	/**
	* Check rate limiting for login attempts.
	*
	* @param string $username Username being attempted.
	* @return true|\WP_Error True if OK, WP_Error if rate limited.
	*/
	private function check_rate_limit( $username ) {
		$settings  = new Settings();
		$threshold = (int) $settings->get( 'login_lockout_threshold', 5 );
		$duration  = (int) $settings->get( 'login_lockout_duration', 15 );

		if ( $threshold <= 0 ) {
			return true; // Rate limiting is disabled
		}

		// Check username
		$username_key     = self::RATE_LIMIT_PREFIX . md5( $username );
		$username_attempts = (int) get_transient( $username_key );

		if ( $username_attempts >= $threshold ) {
			return new \WP_Error(
				'rate_limited',
				sprintf(
					
					__( 'Too many failed login attempts. Please try again in %d minutes.', 'anchor' ),
					$duration
				)
			);
		}

		// Check IP
		$ip     = $this->get_client_ip();
		$ip_key = self::RATE_LIMIT_PREFIX . 'ip_' . md5( $ip );
		$ip_attempts = (int) get_transient( $ip_key );

		if ( $ip_attempts >= ( $threshold * 3 ) ) { // Allow more attempts per IP
			return new \WP_Error(
				'rate_limited',
				sprintf(
					__( 'Too many failed login attempts from this location. Please try again in %d minutes.', 'anchor' ),
					$duration
				)
			);
		}

		return true;
	}

	/**
	* Record a failed login attempt.
	*
	* @param string $username Username attempted.
	*/
	private function record_failed_attempt( $username ) {
		$settings = new Settings();
		$duration = (int) $settings->get( 'login_lockout_duration', 15 );

		// Record by username
		$username_key      = self::RATE_LIMIT_PREFIX . md5( $username );
		$username_attempts = (int) get_transient( $username_key );
		set_transient( $username_key, $username_attempts + 1, $duration * MINUTE_IN_SECONDS );

		// Record by IP
		$ip          = $this->get_client_ip();
		$ip_key      = self::RATE_LIMIT_PREFIX . 'ip_' . md5( $ip );
		$ip_attempts = (int) get_transient( $ip_key );
		set_transient( $ip_key, $ip_attempts + 1, $duration * MINUTE_IN_SECONDS );
	}

	/**
	* Clear rate limiting for a username.
	*
	* @param string $username Username to clear.
	*/
	private function clear_rate_limit( $username ) {
		$username_key = self::RATE_LIMIT_PREFIX . md5( $username );
		delete_transient( $username_key );
	}

	/**
	* Request a password reset.
	*
	* @param string $email Member email.
	* @return true|\WP_Error True on success, WP_Error on failure.
	*/
	public function request_password_reset( $email ) {
		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Please enter a valid email address.', 'anchor' ) );
		}

		$member_handler = new Member();
		$member         = $member_handler->get_by( 'email', $email );

		// Always return success to prevent email enumeration
		if ( ! $member ) {
			do_action( 'anchor_password_reset_failed', $email, 'member_not_found' );
			return true;
		}

		// Generate reset token
		$token_handler = new Token();
		$token         = $token_handler->generate( $member->ID, Token::TYPE_RESET_PASSWORD );

		if ( ! $token ) {
			return new \WP_Error( 'token_error', __( 'Could not generate reset token.', 'anchor' ) );
		}

		// Send reset email
		$email_handler = new Email();
		$sent          = $email_handler->send_password_reset( $member->ID, $token );

		do_action( 'anchor_password_reset_requested', $member->ID, $email );

		return true;
	}

	/**
	* Reset a password using a token.
	*
	* @param string $token        Reset token.
	* @param string $new_password New password.
	* @return true|\WP_Error True on success, WP_Error on failure.
	*/
	public function reset_password( $token, $new_password ) {
		$token_handler = new Token();
		$member_id     = $token_handler->validate( $token, Token::TYPE_RESET_PASSWORD );

		if ( ! $member_id ) {
			return new \WP_Error( 'invalid_token', __( 'This password reset link is invalid or has expired.', 'anchor' ) );
		}

		// Update password
		$member_handler = new Member();
		$result         = $member_handler->update( $member_id, [ 'password' => $new_password ] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Mark the token as used
		$token_handler->use_token( $token );

		do_action( 'anchor_password_reset_success', $member_id );

		return true;
	}

	/**
	* Verify an email using a token.
	*
	* @param string $token Verification token.
	* @return true|\WP_Error True on success, WP_Error on failure.
	*/
	public function verify_email( $token ) {
		$token_handler = new Token();
		$member_id     = $token_handler->validate( $token, Token::TYPE_VERIFY_EMAIL );

		if ( ! $member_id ) {
			return new \WP_Error( 'invalid_token', __( 'This verification link is invalid or has expired.', 'anchor' ) );
		}

		// Update member as verified
		$member_handler = new Member();
		$result         = $member_handler->update( $member_id, [
			'email_verified' => 1,
			'status'         => 'active',
		] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$token_handler->use_token( $token );

		do_action( 'anchor_email_verified', $member_id );

		return true;
	}

	/**
	* Register a new member.
	*
	* @param array $data Registration data.
	* @return int|\WP_Error Member ID on success, WP_Error on failure.
	*/
	public function register( $data ) {
		$settings = new Settings();

		// Check if registration is allowed
		if ( ! $settings->get( 'allow_registration', '1' ) ) {
			return new \WP_Error( 'registration_disabled', __( 'Registration is currently disabled.', 'anchor' ) );
		}

		// Create member
		$member_handler = new Member();
		$member_id      = $member_handler->create( $data );

		if ( is_wp_error( $member_id ) ) {
			return $member_id;
		}

		// Send verification email if required
		if ( $settings->get( 'require_email_verification' ) ) {
			$token_handler = new Token();
			$token         = $token_handler->generate( $member_id, Token::TYPE_VERIFY_EMAIL );

			if ( $token ) {
				$email_handler = new Email();
				$email_handler->send_verification( $member_id, $token );
			}
		} else {
			// Auto-activate and send welcome email
			$member_handler->update( $member_id, [
				'status'         => 'active',
				'email_verified' => 1,
			] );

			$email_handler = new Email();
			$email_handler->send_welcome( $member_id );
		}

		return $member_id;
	}

	/**
	* Resend verification email.
	*
	* @param string $email Member email.
	* @return true|\WP_Error True on success, WP_Error on failure.
	*/
	public function resend_verification( $email ) {
		$email = sanitize_email( $email );

		$member_handler = new Member();
		$member         = $member_handler->get_by( 'email', $email );

		// Don't reveal email if it exists
		if ( ! $member ) {
			return true;
		}

		// Check if previoulsy verified
		if ( $member->email_verified ) {
			return true;
		}

		// Generate new token
		$token_handler = new Token();
		$token         = $token_handler->generate( $member->ID, Token::TYPE_VERIFY_EMAIL );

		if ( ! $token ) {
			return new \WP_Error( 'token_error', __( 'Could not generate verification token.', 'anchor' ) );
		}

		// Send email
		$email_handler = new Email();
		$email_handler->send_verification( $member->ID, $token );

		return true;
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
			if ( strpos( $ip, ',' ) !== false ) {
				$ip = trim( explode( ',', $ip )[0] );
			}
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	* Get logout URL.
	*
	* @param string $redirect_to URL to redirect to after logout.
	* @return string Logout URL.
	*/
	public function get_logout_url( $redirect_to = '' ) {
		$url = add_query_arg( [
			'anchor_logout' => '1',
			'_wpnonce'      => wp_create_nonce( 'anchor_logout' ),
		], home_url() );

		if ( $redirect_to ) {
			$url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $url );
		}

		return $url;
	}
}
