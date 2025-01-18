<?php
/**
* Uninstall script for Anchor plugin.
*
* Fired when the plugin is deleted via WordPress admin.
*
* @package Anchor
*/

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
* Check if we should remove all data.
*
* By default, we keep data to prevent accidental loss.
* Set the ANCHOR_REMOVE_ALL_DATA constant to true to remove all data.
*/
$remove_all_data = defined( 'ANCHOR_REMOVE_ALL_DATA' ) && ANCHOR_REMOVE_ALL_DATA;

// Check for a setting (if tables still exist)
// TODO Use ANCHOR_TABLE_PREFIX when forming table names

global $wpdb;
$settings_table = 'ap_member_settings';
$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $settings_table ) ) === $settings_table;

if ( $table_exists && ! $remove_all_data ) {
	$remove_setting = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT setting_value FROM {$settings_table} WHERE setting_key = %s",
			'remove_data_on_uninstall'
		)
	);
	$remove_all_data = $remove_setting === '1';
}

if ( $remove_all_data ) {
	// Drop all custom tables
	$tables = [
		'ap_members',
		'ap_member_meta',
		'ap_member_roles',
		'ap_member_role_relationships',
		'ap_member_sessions',
		'ap_member_tokens',
		'ap_member_settings',
	];

	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	// Delete options
	delete_option( 'anchor_activated' );
	delete_option( 'anchor_version' );

	// Delete transients
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_anchor_%' OR option_name LIKE '_transient_timeout_anchor_%'"
	);

	// Clear any cron events
	wp_clear_scheduled_hook( 'anchor_cleanup_sessions' );
	wp_clear_scheduled_hook( 'anchor_cleanup_tokens' );
}

flush_rewrite_rules();
