<?php
/**
* Role class for Anchor plugin.
*
* Handles roles and capabilities.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Role {


	private $wpdb;

	private $table;

	private $relationships_table;

	private $default_member_caps = [
		'view_own_profile',
		'edit_own_profile',
		'delete_own_account',
	];

	public function __construct() {
		global $wpdb;
		$this->wpdb                = $wpdb;
		$this->table               = ANCHOR_TABLE_PREFIX . 'member_roles';
		$this->relationships_table = ANCHOR_TABLE_PREFIX . 'member_role_relationships';
	}

	/**
	* Create default roles on activation.
	*/
	public function create_default_roles() {
		$exists = $this->get_by_slug( 'member' );

		if ( ! $exists ) {
			$this->create( [
				'role_name'    => __( 'Member', 'anchor' ),
				'role_slug'    => 'member',
				'capabilities' => $this->default_member_caps,
			] );
		}
	}

	/**
	* Create a new role.
	*
	* @param array $data Role data.
	* @return int|\WP_Error Role ID on success, WP_Error on failure.
	*/
	public function create( $data ) {
		if ( empty( $data['role_name'] ) || empty( $data['role_slug'] ) ) {
			return new \WP_Error( 'missing_fields', __( 'Role name and slug are required.', 'anchor' ) );
		}

		$role_slug = sanitize_title( $data['role_slug'] );

		// Check for existing role
		if ( $this->get_by_slug( $role_slug ) ) {
			return new \WP_Error( 'role_exists', __( 'A role with this slug already exists.', 'anchor' ) );
		}

		$capabilities = isset( $data['capabilities'] ) ? $data['capabilities'] : [];

		$result = $this->wpdb->insert(
			$this->table,
			[
				'role_name'    => sanitize_text_field( $data['role_name'] ),
				'role_slug'    => $role_slug,
				'capabilities' => wp_json_encode( $capabilities ),
				'created_at'   => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s' ]
		);

		if ( ! $result ) {
			return new \WP_Error( 'db_error', __( 'Could not create role.', 'anchor' ) );
		}

		return $this->wpdb->insert_id;
	}

	/**
	* Get a role by ID.
	*
	* @param int $role_id Role ID.
	* @return object|null Role object or null.
	*/
	public function get( $role_id ) {
		$role = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE ID = %d",
				$role_id
			)
		);

		return $role ? $this->prepare_role_object( $role ) : null;
	}

	/**
	* Get a role by slug.
	*
	* @param string $slug Role slug.
	* @return object|null Role object or null.
	*/
	public function get_by_slug( $slug ) {
		$role = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE role_slug = %s",
				$slug
			)
		);

		return $role ? $this->prepare_role_object( $role ) : null;
	}

	/**
	* Get all roles.
	*
	* @return array Array of role objects.
	*/
	public function get_all() {
		$roles = $this->wpdb->get_results(
			"SELECT * FROM {$this->table} ORDER BY role_name ASC"
		);

		$roles = apply_filters( 'anchor_available_roles', $roles );

		return array_map( [ $this, 'prepare_role_object' ], $roles );
	}

	/**
	* Update a role.
	*
	* @param int   $role_id Role ID.
	* @param array $data    Data to update.
	* @return bool|\WP_Error True on success, WP_Error on failure.
	*/
	public function update( $role_id, $data ) {
		$role = $this->get( $role_id );

		if ( ! $role ) {
			return new \WP_Error( 'role_not_found', __( 'Role not found.', 'anchor' ) );
		}

		$update_data   = [];
		$update_format = [];

		if ( isset( $data['role_name'] ) ) {
			$update_data['role_name'] = sanitize_text_field( $data['role_name'] );
			$update_format[]          = '%s';
		}

		if ( isset( $data['capabilities'] ) ) {
			$update_data['capabilities'] = wp_json_encode( $data['capabilities'] );
			$update_format[]             = '%s';
		}

		if ( empty( $update_data ) ) {
			return true;
		}

		$result = $this->wpdb->update(
			$this->table,
			$update_data,
			[ 'ID' => $role_id ],
			$update_format,
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	* Delete a role.
	*
	* @param int $role_id Role ID.
	* @return bool True on success.
	*/
	public function delete( $role_id ) {
		// Remove all member relationships
		$this->wpdb->delete(
			$this->relationships_table,
			[ 'role_id' => $role_id ],
			[ '%d' ]
		);

		// Delete the role
		$result = $this->wpdb->delete(
			$this->table,
			[ 'ID' => $role_id ],
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	* Assign a role to a member.
	*
	* @param int    $member_id Member ID.
	* @param string $role_slug Role slug.
	* @return bool|\WP_Error True on success, WP_Error on failure.
	*/
	public function assign_to_member( $member_id, $role_slug ) {
		$role = $this->get_by_slug( $role_slug );

		if ( ! $role ) {
			return new \WP_Error( 'role_not_found', __( 'Role not found.', 'anchor' ) );
		}

		// Check if already assigned
		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT ID FROM {$this->relationships_table} WHERE member_id = %d AND role_id = %d",
				$member_id,
				$role->ID
			)
		);

		if ( $exists ) {
			return true; // Already assigned
		}

		$result = $this->wpdb->insert(
			$this->relationships_table,
			[
				'member_id'   => $member_id,
				'role_id'     => $role->ID,
				'assigned_at' => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%s' ]
		);

		if ( $result ) {
			do_action( 'anchor_role_assigned', $member_id, $role_slug );
		}

		return $result !== false;
	}

	/**
	* Remove a role from a member.
	*
	* @param int    $member_id Member ID.
	* @param string $role_slug Role slug.
	* @return bool True on success.
	*/
	public function remove_from_member( $member_id, $role_slug ) {
		$role = $this->get_by_slug( $role_slug );

		if ( ! $role ) {
			return false;
		}

		$result = $this->wpdb->delete(
			$this->relationships_table,
			[
				'member_id' => $member_id,
				'role_id'   => $role->ID,
			],
			[ '%d', '%d' ]
		);

		if ( $result ) {
			do_action( 'anchor_role_removed', $member_id, $role_slug );
		}

		return $result !== false;
	}

	/**
	* Remove all roles from a member.
	*
	* @param int $member_id Member ID.
	* @return bool True on success.
	*/
	public function remove_all_from_member( $member_id ) {
		$result = $this->wpdb->delete(
			$this->relationships_table,
			[ 'member_id' => $member_id ],
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	* Get roles for a member.
	*
	* @param int $member_id Member ID.
	* @return array Array of role objects.
	*/
	public function get_member_roles( $member_id ) {
		$roles = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT r.* FROM {$this->table} r
				INNER JOIN {$this->relationships_table} rr ON r.ID = rr.role_id
				WHERE rr.member_id = %d",
				$member_id
			)
		);

		return array_map( [ $this, 'prepare_role_object' ], $roles );
	}

	/**
	* Check if member has a specific role.
	*
	* @param int    $member_id Member ID.
	* @param string $role_slug Role slug.
	* @return bool True if member has role.
	*/
	public function member_has_role( $member_id, $role_slug ) {
		$role = $this->get_by_slug( $role_slug );

		if ( ! $role ) {
			return false;
		}

		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT ID FROM {$this->relationships_table} WHERE member_id = %d AND role_id = %d",
				$member_id,
				$role->ID
			)
		);

		return ! is_null( $exists );
	}

	/**
	* Get all capabilities for a member.
	*
	* @param int $member_id Member ID.
	* @return array Array of capabilities.
	*/
	public function get_member_capabilities( $member_id ) {
		$roles        = $this->get_member_roles( $member_id );
		$capabilities = [];

		foreach ( $roles as $role ) {
			$capabilities = array_merge( $capabilities, $role->capabilities );
		}

		$capabilities = array_unique( $capabilities );

		return apply_filters( 'anchor_get_capabilities', $capabilities, $member_id );
	}

	/**
	* Check if member has a specific capability.
	*
	* @param int    $member_id  Member ID.
	* @param string $capability Capability to check.
	* @return bool True if member has capability.
	*/
	public function member_can( $member_id, $capability ) {
		$capabilities = $this->get_member_capabilities( $member_id );
		$can          = in_array( $capability, $capabilities, true );

		return apply_filters( 'anchor_user_can', $can, $member_id, $capability );
	}

	/**
	* Prepare role object with decoded capabilities.
	*
	* @param object $role Raw role object from database.
	* @return object Prepared role object.
	*/
	private function prepare_role_object( $role ) {
		$role->ID           = (int) $role->ID;
		$role->capabilities = json_decode( $role->capabilities, true );

		if ( ! is_array( $role->capabilities ) ) {
			$role->capabilities = [];
		}

		return $role;
	}

	/**
	* Get member count for a role.
	*
	* @param int $role_id Role ID.
	* @return int Member count.
	*/
	public function get_member_count( $role_id ) {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->relationships_table} WHERE role_id = %d",
				$role_id
			)
		);
	}
}
