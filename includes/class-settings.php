<?php
/**
* Settings class for Anchor plugin.
*
* Handles plugin settings storage and retrieval.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {


	private $wpdb;

	private $table;

	private static $cache = [];

	private $defaults = [
		// General settings
		'enable_public_profiles'      => '0',
		'default_login_redirect_url'  => '/account/',
		'default_logout_redirect_url' => '/',
		'require_email_verification'  => '1',
		'session_expiry_days'         => '7',
		'allow_registration'          => '1',
		'default_role'                => 'member',
		'login_lockout_threshold'     => '5',
		'login_lockout_duration'      => '15',
		'password_min_length'         => '8',
		'from_email'                  => '',
		'from_name'                   => '',

		// Content access settings
		'enable_registered'           => '0',
		'enable_subscriber'           => '0',
		'default_access_level'        => 'public',

		// Metering settings
		'enable_metering'             => '0',
		'meter_limit_anonymous'       => '5',
		'meter_period_anonymous'      => 'monthly',
		'meter_week_end_day'          => 'sunday',
		'meter_timezone'              => 'UTC',
		'meter_action_anonymous'      => 'require_registration',
		'meter_limit_registered'      => '0',
		'meter_limit_registered_count' => '10',
		'meter_period_registered'     => 'monthly',

		// Counting rules
		'meter_post_types'            => [ 'post' ],
		'meter_exemption_rules'       => '[]', // JSON string (not array)
		'enable_time_decay'           => '0',
		'time_decay_days'             => '30',
	];

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = ANCHOR_TABLE_PREFIX . 'member_settings';
	}

	/**
	* Create default settings on activation.
	*/
	public function create_default_settings() {
		$defaults = apply_filters( 'anchor_default_settings', $this->defaults );

		foreach ( $defaults as $key => $value ) {
			$exists = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT ID FROM {$this->table} WHERE setting_key = %s",
					$key
				)
			);

			if ( ! $exists ) {
				$this->wpdb->insert(
					$this->table,
					[
						'setting_key'   => $key,
						'setting_value' => $value,
						'autoload'      => 'yes',
					],
					[ '%s', '%s', '%s' ]
				);
			}
		}
	}

	/**
	* Get a setting value.
	*
	* @param string $key     Setting key.
	* @param mixed  $default Default value if not found.
	* @return mixed Setting value.
	*/
	public function get( $key, $default = null ) {
		if ( isset( self::$cache[ $key ] ) ) {
			return apply_filters( 'anchor_get_setting', self::$cache[ $key ], $key );
		}

		$value = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT setting_value FROM {$this->table} WHERE setting_key = %s",
				$key
			)
		);

		if ( is_null( $value ) ) {
			// Check defaults
			$value = isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : $default;
		}

		// Unserialize if needed
		$value = maybe_unserialize( $value );

		self::$cache[ $key ] = $value;

		$value = apply_filters( 'anchor_get_setting', $value, $key );
		$value = apply_filters( "anchor_get_setting_{$key}", $value );

		return $value;
	}

	/**
	* Update a setting value.
	*
	* @param string $key   Setting key.
	* @param mixed  $value Setting value.
	* @return bool True on success.
	*/
	public function update( $key, $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = maybe_serialize( $value );
		}

		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT ID FROM {$this->table} WHERE setting_key = %s",
				$key
			)
		);

		if ( $exists ) {
			$result = $this->wpdb->update(
				$this->table,
				[ 'setting_value' => $value ],
				[ 'setting_key' => $key ],
				[ '%s' ],
				[ '%s' ]
			);
		} else {
			$result = $this->wpdb->insert(
				$this->table,
				[
					'setting_key'   => $key,
					'setting_value' => $value,
					'autoload'      => 'yes',
				],
				[ '%s', '%s', '%s' ]
			);
		}

		self::$cache[ $key ] = $value;

		return $result !== false;
	}

	/**
	* Delete a setting.
	*
	* @param string $key Setting key.
	* @return bool True on success.
	*/
	public function delete( $key ) {
		$result = $this->wpdb->delete(
			$this->table,
			[ 'setting_key' => $key ],
			[ '%s' ]
		);

		unset( self::$cache[ $key ] );

		return $result !== false;
	}

	/**
	* Get all settings.
	*
	* @return array All settings as key => value.
	*/
	public function get_all() {
		$results = $this->wpdb->get_results(
			"SELECT setting_key, setting_value FROM {$this->table}",
			ARRAY_A
		);

		$settings = [];
		if ( $results ) {
			foreach ( $results as $row ) {
				$settings[ $row['setting_key'] ] = $row['setting_value'];
			}
		}

		// Merge for any missing settings
		return array_merge( $this->defaults, $settings );
	}

	/**
	* Load autoload settings into cache.
	*/
	public function load_autoload_settings() {
		$results = $this->wpdb->get_results(
			"SELECT setting_key, setting_value FROM {$this->table} WHERE autoload = 'yes'",
			ARRAY_A
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				self::$cache[ $row['setting_key'] ] = $row['setting_value'];
			}
		}
	}

	/**
	* Clear the settings cache.
	*/
	public function clear_cache() {
		self::$cache = [];
	}

	/**
	* Get default settings.
	*
	* @return array Default settings.
	*/
	public function get_defaults() {
		return $this->defaults;
	}
}
