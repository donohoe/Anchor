<?php
/**
* Database class for Anchor plugin.
*
* Handles database table creation and management.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {

	private $wpdb;

	private $prefix;

	public function __construct() {
		global $wpdb;
		$this->wpdb   = $wpdb;
		$this->prefix = ANCHOR_TABLE_PREFIX;
	}

	/**
	* Get the full table name with prefix.
	*
	* @param string $table Base table name.
	* @return string Full table name.
	*/
	public function get_table_name( $table ) {
		return $this->prefix . $table;
	}

	/**
	* Create all plugin tables.
	*/
	public function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $this->wpdb->get_charset_collate();

		$this->create_members_table( $charset_collate );
		$this->create_member_meta_table( $charset_collate );
		$this->create_member_roles_table( $charset_collate );
		$this->create_member_role_relationships_table( $charset_collate );
		$this->create_member_sessions_table( $charset_collate );
		$this->create_member_tokens_table( $charset_collate );
		$this->create_member_settings_table( $charset_collate );

		do_action( 'anchor_tables_created' );
	}

	/**
	* Create members table.
	*
	* @param string $charset_collate Database charset collate.
	*/
	private function create_members_table( $charset_collate ) {
		$table_name = $this->get_table_name( 'members' );

		$sql = "CREATE TABLE {$table_name} (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			username varchar(60) NOT NULL,
			email varchar(100) NOT NULL,
			password_hash varchar(255) NOT NULL,
			display_name varchar(250) DEFAULT NULL,
			status varchar(20) DEFAULT 'pending',
			email_verified tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			verified_at datetime DEFAULT NULL,
			last_login datetime DEFAULT NULL,
			PRIMARY KEY  (ID),
			UNIQUE KEY username (username),
			UNIQUE KEY email (email),
			KEY status (status),
			KEY email_verified (email_verified)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	* Create member meta table.
	*
	* @param string $charset_collate Database charset collate.
	*/
	private function create_member_meta_table( $charset_collate ) {
		$table_name = $this->get_table_name( 'member_meta' );

		$sql = "CREATE TABLE {$table_name} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			member_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) NOT NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY member_id (member_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	* Create member roles table.
	*
	* @param string $charset_collate Database charset collate.
	*/
	private function create_member_roles_table( $charset_collate ) {
		$table_name = $this->get_table_name( 'member_roles' );

		$sql = "CREATE TABLE {$table_name} (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			role_name varchar(100) NOT NULL,
			role_slug varchar(100) NOT NULL,
			capabilities longtext,
			created_at datetime NOT NULL,
			PRIMARY KEY  (ID),
			UNIQUE KEY role_slug (role_slug)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	* Create member role relationships table.
	*
	* @param string $charset_collate Database charset collate.
	*/
	private function create_member_role_relationships_table( $charset_collate ) {
		$table_name = $this->get_table_name( 'member_role_relationships' );

		$sql = "CREATE TABLE {$table_name} (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			member_id bigint(20) unsigned NOT NULL,
			role_id bigint(20) unsigned NOT NULL,
			assigned_at datetime NOT NULL,
			PRIMARY KEY  (ID),
			UNIQUE KEY member_role (member_id, role_id),
			KEY member_id (member_id),
			KEY role_id (role_id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	* Create member sessions table.
	*
	* @param string $charset_collate Database charset collate.
	*/
	private function create_member_sessions_table( $charset_collate ) {
		$table_name = $this->get_table_name( 'member_sessions' );

		$sql = "CREATE TABLE {$table_name} (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			member_id bigint(20) unsigned NOT NULL,
			session_token varchar(64) NOT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			last_activity datetime NOT NULL,
			PRIMARY KEY  (ID),
			UNIQUE KEY session_token (session_token),
			KEY member_id (member_id),
			KEY expires_at (expires_at)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	* Create member tokens table.
	*
	* @param string $charset_collate Database charset collate.
	*/
	private function create_member_tokens_table( $charset_collate ) {
		$table_name = $this->get_table_name( 'member_tokens' );

		$sql = "CREATE TABLE {$table_name} (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			member_id bigint(20) unsigned NOT NULL,
			token_hash varchar(64) NOT NULL,
			token_type varchar(20) NOT NULL,
			created_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			used_at datetime DEFAULT NULL,
			PRIMARY KEY  (ID),
			UNIQUE KEY token_hash (token_hash),
			KEY member_id (member_id),
			KEY expires_at (expires_at),
			KEY token_type (token_type)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	* Create member settings table.
	*
	* @param string $charset_collate Database charset collate.
	*/
	private function create_member_settings_table( $charset_collate ) {
		$table_name = $this->get_table_name( 'member_settings' );

		$sql = "CREATE TABLE {$table_name} (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			setting_key varchar(100) NOT NULL,
			setting_value longtext,
			autoload varchar(20) DEFAULT 'yes',
			PRIMARY KEY  (ID),
			UNIQUE KEY setting_key (setting_key)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	* Drop all plugin tables.
	*
	* Used during uninstall if user opts to remove all data.
	*/
	public function drop_tables() {
		$tables = [
			'members',
			'member_meta',
			'member_roles',
			'member_role_relationships',
			'member_sessions',
			'member_tokens',
			'member_settings',
		];

		foreach ( $tables as $table ) {
			$table_name = $this->get_table_name( $table );
			$this->wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		do_action( 'anchor_tables_dropped' );
	}

	/**
	* Check if tables exist.
	*
	* @return bool True if all tables exist.
	*/
	public function tables_exist() {
		$members_table = $this->get_table_name( 'members' );
		$result = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$members_table
			)
		);

		return $result === $members_table;
	}
}
