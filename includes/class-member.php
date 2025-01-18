<?php
/**
* Member class for Anchor plugin.
*
* Handles member CRUD operations.
*
* @package Anchor
*/

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member {

	private $wpdb;

	private $table;

	/**
	* Member meta table name.
	*
	* @var string
	*/
	private $meta_table;

	public function __construct() {
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table      = ANCHOR_TABLE_PREFIX . 'members';
		$this->meta_table = ANCHOR_TABLE_PREFIX . 'member_meta';
	}

	/**
	* Get a member by ID.
	*
	* @param int $member_id Member ID.
	* @return object|null Member object or null.
	*/
	public function get( $member_id ) {
		$member = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE ID = %d",
				$member_id
			)
		);

		return $member ? $this->prepare_member_object( $member ) : null;
	}

	/**
	* Get a member by field.
	*
	* @param string $field Field name (email, username, ID).
	* @param mixed  $value Field value.
	* @return object|null Member object or null.
	*/
	public function get_by( $field, $value ) {
		$allowed_fields = [ 'ID', 'email', 'username' ];

		if ( ! in_array( $field, $allowed_fields, true ) ) {
			return null;
		}

		$member = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE {$field} = %s",
				$value
			)
		);

		return $member ? $this->prepare_member_object( $member ) : null;
	}

	/**
	* Create a new member.
	*
	* @param array $data Member data.
	* @return int|\WP_Error Member ID on success, WP_Error on failure.
	*/
	public function create( $data ) {
		$data = apply_filters( 'anchor_registration_data', $data );

		// Validate required fields
		if ( empty( $data['username'] ) || empty( $data['email'] ) || empty( $data['password'] ) ) {
			return new \WP_Error( 'missing_fields', __( 'Username, email, and password are required.', 'anchor' ) );
		}

		// Sanitize
		$username = sanitize_user( $data['username'] );
		$email    = sanitize_email( $data['email'] );

		// Validate username
		$username_error = $this->validate_username( $username );
		if ( is_wp_error( $username_error ) ) {
			return $username_error;
		}

		// Validate email
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Please enter a valid email address.', 'anchor' ) );
		}

		// Check for existing username
		if ( $this->exists( $username, 'username' ) ) {
			return new \WP_Error( 'username_exists', __( 'This username is already registered.', 'anchor' ) );
		}

		// Check for existing email
		if ( $this->exists( $email, 'email' ) ) {
			return new \WP_Error( 'email_exists', __( 'This email address is already registered.', 'anchor' ) );
		}

		// Validate password
		$password_error = $this->validate_password( $data['password'] );
		if ( is_wp_error( $password_error ) ) {
			return $password_error;
		}

		$errors = apply_filters( 'anchor_registration_errors', new \WP_Error(), $data );
		if ( $errors->has_errors() ) {
			return $errors;
		}

		do_action( 'anchor_before_register', $data );

		$password_hash = wp_hash_password( $data['password'] );

		// Ready insert data
		$insert_data = [
			'username'       => $username,
			'email'          => $email,
			'password_hash'  => $password_hash,
			'display_name'   => ! empty( $data['display_name'] ) ? sanitize_text_field( $data['display_name'] ) : $username,
			'status'         => 'pending',
			'email_verified' => 0,
			'created_at'     => current_time( 'mysql' ),
		];

		$result = $this->wpdb->insert(
			$this->table,
			$insert_data,
			[ '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
		);

		if ( ! $result ) {
			do_action( 'anchor_registration_failed', __( 'Database error', 'anchor' ), $data );
			return new \WP_Error( 'db_error', __( 'Could not create member account.', 'anchor' ) );
		}

		$member_id = $this->wpdb->insert_id;

		// Default role
		$role = new Role();
		$settings = new Settings();
		$default_role = $settings->get( 'default_role', 'member' );
		$role->assign_to_member( $member_id, $default_role );

		do_action( 'anchor_after_register', $member_id, $data );

		return $member_id;
	}

	/**
	* Update a member.
	*
	* @param int   $member_id Member ID.
	* @param array $data      Data to update.
	* @return bool|\WP_Error True on success, WP_Error on failure.
	*/
	public function update( $member_id, $data ) {
		$member = $this->get( $member_id );

		if ( ! $member ) {
			return new \WP_Error( 'member_not_found', __( 'Member not found.', 'anchor' ) );
		}

		$data = apply_filters( 'anchor_profile_update_data', $data, $member_id );

		$can_update = apply_filters( 'anchor_can_update_profile', true, $member_id );
		if ( ! $can_update ) {
			return new \WP_Error( 'update_forbidden', __( 'You cannot update this profile.', 'anchor' ) );
		}

		do_action( 'anchor_before_profile_update', $member_id, $data );

		$update_data   = [];
		$update_format = [];

		// Email update
		if ( isset( $data['email'] ) && $data['email'] !== $member->email ) {
			$email = sanitize_email( $data['email'] );

			if ( ! is_email( $email ) ) {
				return new \WP_Error( 'invalid_email', __( 'Please enter a valid email address.', 'anchor' ) );
			}

			if ( $this->exists( $email, 'email' ) ) {
				return new \WP_Error( 'email_exists', __( 'This email address is already registered.', 'anchor' ) );
			}

			$update_data['email']          = $email;
			$update_data['email_verified'] = 0; // Require re-verification
			$update_format[]               = '%s';
			$update_format[]               = '%d';
		}

		// display name update
		if ( isset( $data['display_name'] ) ) {
			$update_data['display_name'] = sanitize_text_field( $data['display_name'] );
			$update_format[]             = '%s';
		}

		// Status update
		if ( isset( $data['status'] ) ) {
			$allowed_statuses = [ 'pending', 'active', 'suspended', 'deleted' ];
			if ( in_array( $data['status'], $allowed_statuses, true ) ) {
				$update_data['status'] = $data['status'];
				$update_format[]       = '%s';
			}
		}

		// Email verified update
		if ( isset( $data['email_verified'] ) ) {
			$update_data['email_verified'] = (int) $data['email_verified'];
			$update_format[]               = '%d';

			if ( $data['email_verified'] && empty( $member->verified_at ) ) {
				$update_data['verified_at'] = current_time( 'mysql' );
				$update_format[]            = '%s';
			}
		}

		// Password update
		if ( ! empty( $data['password'] ) ) {
			$password_error = $this->validate_password( $data['password'] );
			if ( is_wp_error( $password_error ) ) {
				return $password_error;
			}

			$update_data['password_hash'] = wp_hash_password( $data['password'] );
			$update_format[]              = '%s';
		}

		if ( empty( $update_data ) ) {
			return true; // Nothing to update
		}

		$result = $this->wpdb->update(
			$this->table,
			$update_data,
			[ 'ID' => $member_id ],
			$update_format,
			[ '%d' ]
		);

		if ( $result === false ) {
			return new \WP_Error( 'db_error', __( 'Could not update member.', 'anchor' ) );
		}

		// If password changed, invalidate all sessions now
		if ( isset( $update_data['password_hash'] ) ) {
			$session = new Session();
			$session->destroy_all_for_member( $member_id );
			do_action( 'anchor_password_changed', $member_id );
		}

		do_action( 'anchor_after_profile_update', $member_id, $data );

		return true;
	}

	/**
	* Delete a member.
	*
	* @param int $member_id Member ID.
	* @return bool True on success.
	*/
	public function delete( $member_id ) {
		// Delete member meta
		$this->wpdb->delete(
			$this->meta_table,
			[ 'member_id' => $member_id ],
			[ '%d' ]
		);

		// Role relationships
		$role = new Role();
		$role->remove_all_from_member( $member_id );

		// Sessions
		$session = new Session();
		$session->destroy_all_for_member( $member_id );

		// Tokens
		$token = new Token();
		$token->delete_all_for_member( $member_id );

		// Delete member
		$result = $this->wpdb->delete(
			$this->table,
			[ 'ID' => $member_id ],
			[ '%d' ]
		);

		do_action( 'anchor_account_deleted', $member_id );

		return $result !== false;
	}

	/**
	* Check if a member exists.
	*
	* @param string $value Value to check.
	* @param string $field Field to check (username or email).
	* @return bool True if exists.
	*/
	public function exists( $value, $field = 'email' ) {
		$allowed_fields = [ 'email', 'username' ];

		if ( ! in_array( $field, $allowed_fields, true ) ) {
			return false;
		}

		$result = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT ID FROM {$this->table} WHERE {$field} = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$value
			)
		);

		return ! is_null( $result );
	}

	/**
	* Validate username.
	*
	* @param string $username Username to validate.
	* @return true|\WP_Error True if valid, WP_Error otherwise.
	*/
	public function validate_username( $username ) {
		if ( strlen( $username ) < 3 ) {
			return new \WP_Error( 'username_too_short', __( 'Username must be at least 3 characters.', 'anchor' ) );
		}

		if ( strlen( $username ) > 60 ) {
			return new \WP_Error( 'username_too_long', __( 'Username cannot exceed 60 characters.', 'anchor' ) );
		}

		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $username ) ) {
			return new \WP_Error( 'username_invalid', __( 'Username can only contain letters, numbers, and underscores.', 'anchor' ) );
		}

		return true;
	}

	/**
	* Validate password.
	*
	* @param string $password Password to validate.
	* @return true|\WP_Error True if valid, WP_Error otherwise.
	*/
	public function validate_password( $password ) {
		$settings   = new Settings();
		$min_length = (int) $settings->get( 'password_min_length', 8 );

		$rules = apply_filters( 'anchor_password_requirements', [
			'min_length'        => $min_length,
			'require_uppercase' => false,
			'require_numbers'   => false,
			'require_special'   => false,
		] );

		if ( strlen( $password ) < $rules['min_length'] ) {
			return new \WP_Error(
				'password_too_short',
				sprintf(
					__( 'Password must be at least %d characters.', 'anchor' ),
					$rules['min_length']
				)
			);
		}

		if ( $rules['require_uppercase'] && ! preg_match( '/[A-Z]/', $password ) ) {
			return new \WP_Error( 'password_no_uppercase', __( 'Password must contain at least one uppercase letter.', 'anchor' ) );
		}

		if ( $rules['require_numbers'] && ! preg_match( '/[0-9]/', $password ) ) {
			return new \WP_Error( 'password_no_number', __( 'Password must contain at least one number.', 'anchor' ) );
		}

		if ( $rules['require_special'] && ! preg_match( '/[^a-zA-Z0-9]/', $password ) ) {
			return new \WP_Error( 'password_no_special', __( 'Password must contain at least one special character.', 'anchor' ) );
		}

		return true;
	}

	/**
	* Get member meta.
	*
	* @param int    $member_id Member ID.
	* @param string $key       Meta key.
	* @param bool   $single    Return single value or array.
	* @return mixed Meta value(s).
	*/
	public function get_meta( $member_id, $key = '', $single = true ) {
		if ( empty( $key ) ) {
			// Get all meta for member
			$results = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT meta_key, meta_value FROM {$this->meta_table} WHERE member_id = %d",
					$member_id
				),
				ARRAY_A
			);

			$meta = [];
			if ( $results ) {
				foreach ( $results as $row ) {
					$meta[ $row['meta_key'] ][] = maybe_unserialize( $row['meta_value'] );
				}
			}
			return $meta;
		}

		if ( $single ) {
			$value = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT meta_value FROM {$this->meta_table} WHERE member_id = %d AND meta_key = %s LIMIT 1",
					$member_id,
					$key
				)
			);

			return maybe_unserialize( $value );
		}

		$results = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT meta_value FROM {$this->meta_table} WHERE member_id = %d AND meta_key = %s",
				$member_id,
				$key
			)
		);

		return array_map( 'maybe_unserialize', $results );
	}

	/**
	* Update member meta.
	*
	* @param int    $member_id Member ID.
	* @param string $key       Meta key.
	* @param mixed  $value     Meta value.
	* @return bool True on success.
	*/
	public function update_meta( $member_id, $key, $value ) {
		$value = maybe_serialize( $value );

		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT meta_id FROM {$this->meta_table} WHERE member_id = %d AND meta_key = %s LIMIT 1",
				$member_id,
				$key
			)
		);

		if ( $exists ) {
			$result = $this->wpdb->update(
				$this->meta_table,
				[ 'meta_value' => $value ],
				[
					'member_id' => $member_id,
					'meta_key'  => $key,
				],
				[ '%s' ],
				[ '%d', '%s' ]
			);
		} else {
			$result = $this->wpdb->insert(
				$this->meta_table,
				[
					'member_id'  => $member_id,
					'meta_key'   => $key,
					'meta_value' => $value,
				],
				[ '%d', '%s', '%s' ]
			);
		}

		return $result !== false;
	}

	/**
	* Delete member meta.
	*
	* @param int    $member_id Member ID.
	* @param string $key       Meta key.
	* @return bool True on success.
	*/
	public function delete_meta( $member_id, $key ) {
		$result = $this->wpdb->delete(
			$this->meta_table,
			[
				'member_id' => $member_id,
				'meta_key'  => $key,
			],
			[ '%d', '%s' ]
		);

		return $result !== false;
	}

	/**
	* Update last login timestamp.
	*
	* @param int $member_id Member ID.
	*/
	public function update_last_login( $member_id ) {
		$this->wpdb->update(
			$this->table,
			[ 'last_login' => current_time( 'mysql' ) ],
			[ 'ID' => $member_id ],
			[ '%s' ],
			[ '%d' ]
		);
	}

	/**
	* Prepare member object with additional properties.
	*
	* @param object $member Raw member object from database.
	* @return object Prepared member object.
	*/
	private function prepare_member_object( $member ) {
		$member->ID          = (int) $member->ID; // ID must be an integer
		$member->is_verified = (bool) $member->email_verified;
		$member->is_active   = $member->status === 'active';

		return $member;
	}

	/**
	* Get all members with optional filtering.
	*
	* @param array $args Query arguments.
	* @return array Array of member objects.
	*/
	public function get_all( $args = [] ) {
		$defaults = [
			'number'  => 20,
			'offset'  => 0,
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'status'  => '',
			'search'  => '',
		];

		$args = wp_parse_args( $args, $defaults );
		$sql  = "SELECT * FROM {$this->table} WHERE 1=1";

		// Status filter
		if ( ! empty( $args['status'] ) ) {
			$sql .= $this->wpdb->prepare( ' AND status = %s', $args['status'] );
		}

		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$sql .= $this->wpdb->prepare(
				' AND (username LIKE %s OR email LIKE %s OR display_name LIKE %s)',
				$search,
				$search,
				$search
			);
		}

		// Order
		$allowed_orderby = [ 'ID', 'username', 'email', 'display_name', 'status', 'created_at', 'last_login' ];
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$sql .= " ORDER BY {$orderby} {$order}";

		// Limit
		$sql .= $this->wpdb->prepare( ' LIMIT %d OFFSET %d', $args['number'], $args['offset'] );

		$results = $this->wpdb->get_results( $sql );

		if ( ! $results ) {
			return [];
		}

		return array_map( [ $this, 'prepare_member_object' ], $results );
	}

	/**
	* Count members with optional filtering.
	*
	* @param array $args Query arguments (status, search).
	* @return int Count.
	*/
	public function count( $args = [] ) {
		// legacy usage (where $args might be a string (status))
		if ( is_string( $args ) ) {
			$args = [ 'status' => $args === 'all' ? '' : $args ];
		}

		$sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1";

		// Status filter
		if ( ! empty( $args['status'] ) ) {
			$sql .= $this->wpdb->prepare( ' AND status = %s', $args['status'] );
		}

		// Search filter
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$sql .= $this->wpdb->prepare(
				' AND (username LIKE %s OR email LIKE %s OR display_name LIKE %s)',
				$search,
				$search,
				$search
			);
		}

		return (int) $this->wpdb->get_var( $sql );
	}
}
