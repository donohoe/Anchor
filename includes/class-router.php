<?php
/**
* Router class for Anchor plugin.
*
* Handles URL routing and template loading.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Router {

	// Public pages (accessible when logged out)
	private $public_pages = [
		'sign-in',
		'register',
		'forgot',
		'reset-password',
		'verify',
	];

	public function __construct() {
		add_action( 'init', [ $this, 'register_rewrites' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'handle_template' ] );
		add_filter( 'document_title_parts', [ $this, 'filter_page_title' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	* Register rewrite rules.
	*/
	public function register_rewrites() {
		// Auth pages
		add_rewrite_rule( '^account/sign-in/?$',  'index.php?anchor_page=sign-in', 'top' );
		add_rewrite_rule( '^account/register/?$', 'index.php?anchor_page=register', 'top' );
		add_rewrite_rule( '^account/forgot/?$',   'index.php?anchor_page=forgot', 'top' );
		add_rewrite_rule( '^account/reset-password/?$', 'index.php?anchor_page=reset-password', 'top' );
		add_rewrite_rule( '^account/verify/?$',   'index.php?anchor_page=verify', 'top' );

		// Protected pages
		add_rewrite_rule( '^account/?$',             'index.php?anchor_page=account', 'top' );
		add_rewrite_rule( '^account/newsletters/?$', 'index.php?anchor_page=newsletters', 'top' );
		add_rewrite_rule( '^account/appearance/?$',  'index.php?anchor_page=appearance', 'top' );
		add_rewrite_rule( '^account/settings/?$',    'index.php?anchor_page=settings', 'top' );

		// Public profiles
		add_rewrite_rule( '^account/([^/]+)/?$',     'index.php?anchor_page=public-profile&anchor_username=$matches[1]', 'top' );
	}

	/**
	* Add query vars.
	*
	* @param array $vars Existing query vars.
	* @return array Modified query vars.
	*/
	public function add_query_vars( $vars ) {
		$vars[] = 'anchor_page';
		$vars[] = 'anchor_username';
		return $vars;
	}

	/**
	* Handle template routing.
	*/
	public function handle_template() {
		$page = get_query_var( 'anchor_page' );

		if ( ! $page ) { return; }

		$session     = new Session();
		$is_logged_in = $session->is_logged_in();

		// Handle logged-in users on public pages
		if ( in_array( $page, $this->public_pages, true ) && $is_logged_in ) {
			$settings = new Settings();
			$redirect = $settings->get( 'default_login_redirect_url', '/account/' );
			wp_safe_redirect( home_url( $redirect ) );
			exit;
		}

		// Handle logged-out users on protected pages
		if ( ! in_array( $page, $this->public_pages, true ) && $page !== 'public-profile' && ! $is_logged_in ) {
			$redirect_to = isset( $_SERVER['REQUEST_URI'] ) ? urlencode( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';
			wp_safe_redirect( home_url( '/account/sign-in?redirect_to=' . $redirect_to ) );
			exit;
		}

		// Public profiles
		if ( $page === 'public-profile' ) {
			$settings = new Settings();
			if ( ! $settings->get( 'enable_public_profiles' ) && ! $is_logged_in ) {
				wp_safe_redirect( home_url( '/account/sign-in' ) );
				exit;
			}
		}

		do_action( 'anchor_before_template', $page );

		$template = $this->get_template( $page );

		if ( $template ) {
			$this->setup_template_vars( $page );

			include $template;

			do_action( 'anchor_after_template', $page );
			exit;
		}

		// Not found
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}

	/**
	* Get template file path.
	*
	* @param string $template_name Template name.
	* @return string|false Template path or false.
	*/
	public function get_template( $template_name ) {
		// Allow complete override via filter
		$template = apply_filters( 'anchor_template_path', '', $template_name );

		if ( $template && file_exists( $template ) ) {
			return $template;
		}

		// Check theme directory: /wp-content/themes/{theme}/anchor/{template}.php
		// TODO Make sure this is documented
		$theme_template = locate_template( 'anchor/' . $template_name . '.php' );

		if ( $theme_template ) {
			return $theme_template;
		}

		// Fallback
		$plugin_template = ANCHOR_PLUGIN_DIR . 'templates/' . $template_name . '.php';

		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return false;
	}

	/**
	* Set up template variables.
	*
	* @param string $page Current page.
	*/
	private function setup_template_vars( $page ) {
		global $anchor_page, $anchor_member, $anchor_errors, $anchor_messages;

		$anchor_page     = $page;
		$anchor_errors   = [];
		$anchor_messages = [];

		$session = new Session();
		if ( $session->is_logged_in() ) {
			$anchor_member = $session->get_current_member();
		}
	}

	/**
	* Filter page title.
	*
	* @param array $title_parts Title parts.
	* @return array Modified title parts.
	*/
	public function filter_page_title( $title_parts ) {
		$page = get_query_var( 'anchor_page' );

		if ( ! $page ) {
			return $title_parts;
		}

		$titles = [
			'sign-in'        => __( 'Sign In', 'anchor' ),
			'register'       => __( 'Create Account', 'anchor' ),
			'forgot'         => __( 'Forgot Password', 'anchor' ),
			'reset-password' => __( 'Reset Password', 'anchor' ),
			'verify'         => __( 'Verify Email', 'anchor' ),
			'account'        => __( 'Account', 'anchor' ),
			'newsletters'    => __( 'Newsletters', 'anchor' ),
			'appearance'     => __( 'Appearance', 'anchor' ),
			'settings'       => __( 'Settings', 'anchor' ),
			'public-profile' => __( 'Member Profile', 'anchor' ),
		];

		if ( isset( $titles[ $page ] ) ) {
			$title_parts['title'] = $titles[ $page ];
		}

		return $title_parts;
	}

	/**
	* Enqueue frontend assets.
	*/
	public function enqueue_assets() {
		$page = get_query_var( 'anchor_page' );

		if ( ! $page ) {
			return;
		}

		wp_enqueue_style(
			'anchor-frontend',
			ANCHOR_PLUGIN_URL . 'assets/css/frontend.css',
			[], ANCHOR_VERSION
		);

		wp_enqueue_script(
			'anchor-frontend',
			ANCHOR_PLUGIN_URL . 'assets/js/frontend.js',
			[], ANCHOR_VERSION, true
		);

		wp_localize_script( 'anchor-frontend', 'anchorData', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'anchor_frontend' ),
		] );
	}

	/**
	* Get the login URL.
	*
	* @param string $redirect_to URL to redirect to after login.
	* @return string Login URL.
	*/
	public static function get_login_url( $redirect_to = '' ) {
		$url = home_url( '/account/sign-in' );

		if ( $redirect_to ) {
			$url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $url );
		}

		return $url;
	}

	/**
	* Get the registration URL.
	*
	* @return string Registration URL.
	*/
	public static function get_register_url() {
		return home_url( '/account/register' );
	}

	/**
	* Get the forgot password URL.
	*
	* @return string Forgot password URL.
	*/
	public static function get_forgot_url() {
		return home_url( '/account/forgot' );
	}

	/**
	* Get the profile URL.
	*
	* @return string Profile URL.
	*/
	public static function get_profile_url() {
		return home_url( '/account/' );
	}

	/**
	* Get the settings URL.
	*
	* @return string Settings URL.
	*/
	public static function get_settings_url() {
		return home_url( '/account/settings' );
	}

	/**
	* Get the account URL.
	*
	* @return string Account URL.
	*/
	public static function get_account_url() {
		return home_url( '/account/' );
	}

	/**
	* Get the newsletters URL.
	*
	* @return string Newsletters URL.
	*/
	public static function get_newsletters_url() {
		return home_url( '/account/newsletters' );
	}

	/**
	* Get the appearance URL.
	*
	* @return string Appearance URL.
	*/
	public static function get_appearance_url() {
		return home_url( '/account/appearance' );
	}

	/**
	* Get a member's public profile URL.
	*
	* @param int|string $member_id_or_username Member ID or username.
	* @return string Profile URL.
	*/
	public static function get_public_profile_url( $member_id_or_username ) {
		if ( is_numeric( $member_id_or_username ) ) {
			$member_handler = new Member();
			$member         = $member_handler->get( $member_id_or_username );
			if ( $member ) {
				$member_id_or_username = $member->username;
			}
		}

		return home_url( '/account/' . sanitize_title( $member_id_or_username ) );
	}
}
