<?php
/**
* Content Access class for Anchor plugin.
*
* Handles client-side access control and metering.
* All access decisions are made client-side - this class only outputs metadata.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Content_Access {

	private $settings;

	private $session;

	public function __construct() {
		$this->settings = new Settings();
		$this->session  = new Session();

		// Output access metadata and config on singular posts
		add_action( 'wp_head', [ $this, 'output_access_metadata' ], 1 );
		add_action( 'wp_head', [ $this, 'output_config' ], 2 );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_filter( 'the_content', [ $this, 'wrap_content' ],           999 );
		add_filter( 'the_content', [ $this, 'add_paywall_container' ], 1000 );
	}

	/**
	* Check if metering is enabled.
	*
	* @return bool
	*/
	public function is_metering_enabled() {
		return $this->settings->get( 'enable_metering', '0' ) === '1';
	}

	/**
	* Check if current post should have access control.
	*
	* @return bool
	*/
	public function should_apply_access_control() {
		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();
		if ( ! $post ) {
			return false;
		}

		// Get metered post types
		$post_types = $this->settings->get( 'meter_post_types', [ 'post' ] );
		if ( ! is_array( $post_types ) ) {
			$post_types = [ 'post' ];
		}

		return in_array( $post->post_type, $post_types, true );
	}

	/**
	* Get access level required for a post.
	*
	* @param int|\WP_Post|null $post Post ID or object.
	* @return string Access level: 'public', 'registered', or 'subscriber'.
	*/
	public function get_post_access_level( $post = null ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return 'public';
		}

		$access = get_post_meta( $post->ID, '_anchor_access_level', true );
		if ( $access && in_array( $access, [ 'public', 'registered', 'subscriber' ], true ) ) {
			return $access;
		}

		// Fallback
		return $this->settings->get( 'default_access_level', 'public' );
	}

	/**
	* Check if a post counts toward the meter.
	*
	* @param int|\WP_Post|null $post Post ID or object.
	* @return bool
	*/
	public function post_counts_toward_meter( $post = null ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return false;
		}

		// Per-post override
		$exempt = get_post_meta( $post->ID, '_anchor_meter_exempt', true );
		if ( $exempt === '1' ) {
			return false;
		}

		// Exemption rules
		if ( $this->is_exempt_by_rules( $post ) ) {
			return false;
		}

		return true;
	}

	/**
	* Check if post is exempt by taxonomy rules.
	*
	* @param \WP_Post $post Post object.
	* @return bool
	*/
	private function is_exempt_by_rules( $post ) {
		$rules = $this->settings->get( 'meter_exemption_rules', '' );
		if ( empty( $rules ) ) {
			return false;
		}

		$rules = json_decode( $rules, true );
		if ( ! is_array( $rules ) || empty( $rules ) ) {
			return false;
		}

		// OR logic - any matching rule exempts the post
		foreach ( $rules as $rule ) {
			if ( empty( $rule['taxonomy'] ) || empty( $rule['term'] ) ) {
				continue;
			}

			$has_term = has_term( $rule['term'], $rule['taxonomy'], $post );
			$operator = isset( $rule['operator'] ) ? $rule['operator'] : 'is';

			if ( $operator === 'is' && $has_term ) {
				return true;
			}
			if ( $operator === 'is_not' && ! $has_term ) {
				return true;
			}
		}

		return false;
	}

	/**
	* Output per-page access metadata as inline JSON.
	*/
	public function output_access_metadata() {
		if ( ! $this->should_apply_access_control() ) {
			return;
		}

		$post = get_post();
		if ( ! $post ) {
			return;
		}

		$data = [
			'postId'            => $post->ID,
			'accessRequired'    => $this->get_post_access_level( $post ),
			'countsTowardMeter' => $this->post_counts_toward_meter( $post ),
			'publishedDate'     => get_the_date( 'Y-m-d', $post ),
		];

		$data = apply_filters( 'anchor_access_metadata', $data, $post );

		printf(
			'<script type="application/json" id="anchor-access-data">%s</script>' . "\n",
			wp_json_encode( $data )
		);
	}

	/**
	* Output global configuration as inline script.
	*/
	public function output_config() {
		if ( ! $this->should_apply_access_control() ) {
			return;
		}

		$config = $this->get_config();

		printf(
			'<script>window.AnchorConfig = %s;</script>' . "\n",
			wp_json_encode( $config )
		);
	}

	/**
	* Get the configuration object.
	*
	* @return array
	*/
	public function get_config() {
		// Build enabled levels array
		$enabled_levels = [ 'public' ];
		if ( $this->settings->get( 'enable_registered', '0' ) === '1' ) {
			$enabled_levels[] = 'registered';
		}
		if ( $this->settings->get( 'enable_subscriber', '0' ) === '1' ) {
			$enabled_levels[] = 'subscriber';
		}

		// Get exemption rules (stored as JSON string)
		$exemption_rules = $this->settings->get( 'meter_exemption_rules', '[]' );
		if ( is_string( $exemption_rules ) ) {
			$exemption_rules = json_decode( $exemption_rules, true );
		}
		if ( ! is_array( $exemption_rules ) ) {
			$exemption_rules = [];
		}

		// Get post types (Settings handles unserialization)
		$post_types = $this->settings->get( 'meter_post_types', [ 'post' ] );
		if ( ! is_array( $post_types ) || empty( $post_types ) ) {
			$post_types = [ 'post' ];
		}

		$config = [
			'enabledLevels' => $enabled_levels,
			'defaultAccess' => $this->settings->get( 'default_access_level', 'public' ),

			'metering' => [
				'enabled'           => $this->is_metering_enabled(),
				'anonymousLimit'    => (int) $this->settings->get( 'meter_limit_anonymous', 5 ),
				'anonymousPeriod'   => $this->settings->get( 'meter_period_anonymous', 'monthly' ),
				'anonymousAction'   => $this->settings->get( 'meter_action_anonymous', 'require_registration' ),
				'registeredEnabled' => $this->settings->get( 'meter_limit_registered', '0' ) === '1',
				'registeredLimit'   => (int) $this->settings->get( 'meter_limit_registered_count', 10 ),
				'registeredPeriod'  => $this->settings->get( 'meter_period_registered', 'monthly' ),
			],

			'timeDecay' => [
				'enabled' => $this->settings->get( 'enable_time_decay', '0' ) === '1',
				'days'    => (int) $this->settings->get( 'time_decay_days', 30 ),
			],

			'postTypes'       => $post_types,
			'exemptionRules'  => $exemption_rules,

			'urls' => [
				'login'    => home_url( '/account/sign-in' ),
				'register' => home_url( '/account/register' ),
			],

			'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG,
		];

		return apply_filters( 'anchor_access_config', $config );
	}

	/**
	* Get current member info for client-side auth check.
	*
	* @return array|null
	*/
	public function get_member_info() {
		if ( ! $this->session->is_logged_in() ) {
			return null;
		}

		$member = $this->session->get_current_member();
		if ( ! $member ) {
			return null;
		}

		// Get member's highest access level based on roles
		$access_level = 'registered';
		$roles = anchor_get_member_roles( $member->ID );
		if ( $roles ) {
			foreach ( $roles as $role ) {
				// Check if role grants subscriber access or not
				$capabilities = $role->capabilities;
				if ( is_string( $capabilities ) ) {
					$capabilities = json_decode( $capabilities, true );
				}
				if ( is_array( $capabilities ) && in_array( 'subscriber_access', $capabilities, true ) ) {
					$access_level = 'subscriber';
					break;
				}
			}
		}

		return [
			'memberId'    => $member->ID,
			'accessLevel' => $access_level,
		];
	}

	/**
	* Enqueue access control scripts.
	*/
	public function enqueue_scripts() {
		if ( ! $this->should_apply_access_control() ) {
			return;
		}

		wp_enqueue_script(
			'anchor-access',
			ANCHOR_PLUGIN_URL . 'assets/js/access.js',
			[], ANCHOR_VERSION, true
		);

		// Pass member info via localization
		$member_info = $this->get_member_info();
		wp_localize_script( 'anchor-access', 'AnchorMember', $member_info ? $member_info : (object) [] );

		wp_enqueue_style(
			'anchor-access',
			ANCHOR_PLUGIN_URL . 'assets/css/access.css',
			[], ANCHOR_VERSION
		);
	}

	/**
	* Wrap content with access control container.
	*
	* @param string $content Post content.
	* @return string Modified content.
	*/
	public function wrap_content( $content ) {
		if ( ! $this->should_apply_access_control() ) {
			return $content;
		}

		// Only wrap in the main query, not in widgets/shortcodes
		if ( ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		return sprintf(
			'<div class="anchor-content"><div class="anchor-content-body">%s</div></div>',
			$content
		);
	}

	/**
	* Add paywall container after content.
	*
	* @param string $content Post content.
	* @return string Modified content.
	*/
	public function add_paywall_container( $content ) {
		if ( ! $this->should_apply_access_control() ) {
			return $content;
		}

		if ( ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		// Add empty paywall container - will be populated by JS if needed
		// TODO Consider moving this client-side too
		$content .= '<div class="anchor-paywall" style="display:none;"></div>';

		return $content;
	}
}
