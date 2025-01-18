<?php
/**
* Admin class for Anchor plugin.
*
* Handles admin menu registration and page routing.
*
* @package Anchor
*/

namespace Anchor\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Admin class.
*/
class Admin {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'handle_actions' ] );
	}

	/**
	* Register admin menu.
	*/
	public function register_menu() {
		// Main menu
		add_menu_page(
			__( 'Anchor', 'anchor' ),
			__( 'Anchor', 'anchor' ),
			'manage_options',
			'anchor-members',
			[ $this, 'render_members_page' ],
			'dashicons-groups',
			30
		);

		// Submenu: All Members
		add_submenu_page(
			'anchor-members',
			__( 'Members', 'anchor' ),
			__( 'Members', 'anchor' ),
			'manage_options',
			'anchor-members',
			[ $this, 'render_members_page' ]
		);

		// Submenu: Add New
		add_submenu_page(
			'anchor-members',
			__( 'Add New Member', 'anchor' ),
			__( 'Add New', 'anchor' ),
			'manage_options',
			'anchor-member-new',
			[ $this, 'render_member_edit_page' ]
		);

		// Submenu: Content Access
		add_submenu_page(
			'anchor-members',
			__( 'Content Access', 'anchor' ),
			__( 'Content Access', 'anchor' ),
			'manage_options',
			'anchor-content-access',
			[ $this, 'render_content_access_page' ]
		);

		// Submenu: Settings
		add_submenu_page(
			'anchor-members',
			__( 'Settings', 'anchor' ),
			__( 'Settings', 'anchor' ),
			'manage_options',
			'anchor-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	* Enqueue admin assets.
	*
	* @param string $hook The current admin page hook.
	*/
	public function enqueue_assets( $hook ) {

		if ( strpos( $hook, 'anchor' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'anchor-admin',
			ANCHOR_PLUGIN_URL . 'assets/css/admin.css', 
			[], ANCHOR_VERSION
		);

		wp_enqueue_script(
			'anchor-admin',
			ANCHOR_PLUGIN_URL . 'assets/js/admin.js', 
			[], ANCHOR_VERSION, true
		);

		wp_localize_script( 'anchor-admin', 'anchorAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'anchor_admin' ),
			'i18n'    => [
				'confirmDelete' => __( 'Are you sure you want to delete this member? This cannot be undone.', 'anchor' ),
				'confirmBulkDelete' => __( 'Are you sure you want to delete the selected members? This cannot be undone.', 'anchor' ),
			],
		] );
	}

	/**
	* Handle admin actions.
	*/
	public function handle_actions() {
		// Member deletion
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['member'] ) ) {
			$this->handle_delete_member();
		}

		// Settings save
		if ( isset( $_POST['anchor_save_settings'] ) ) {
			$this->handle_save_settings();
		}

		// Member save
		if ( isset( $_POST['anchor_save_member'] ) ) {
			$this->handle_save_member();
		}

		// Content access save
		if ( isset( $_POST['anchor_save_content_access'] ) ) {
			$this->handle_save_content_access();
		}
	}

	/**
	* Handle member deletion.
	*/
	private function handle_delete_member() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'anchor' ) );
		}

		$member_id = isset( $_GET['member'] ) ? absint( $_GET['member'] ) : 0;
		$nonce     = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'anchor_delete_member_' . $member_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'anchor' ) );
		}

		$member_handler = new \Anchor\Member();
		$result = $member_handler->delete( $member_id );

		$redirect_url = admin_url( 'admin.php?page=anchor-members' );

		if ( is_wp_error( $result ) ) {
			$redirect_url = add_query_arg( 'error', 'delete_failed', $redirect_url );
		} else {
			$redirect_url = add_query_arg( 'deleted', '1', $redirect_url );
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	* Handle settings save.
	*/
	private function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'anchor' ) );
		}

		if ( ! isset( $_POST['anchor_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_settings_nonce'] ) ), 'anchor_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'anchor' ) );
		}

		$settings = new \Anchor\Settings();

		// General settings
		$settings->update( 'require_email_verification', isset( $_POST['require_email_verification'] ) ? '1' : '0' );
		$settings->update( 'enable_public_profiles', isset( $_POST['enable_public_profiles'] ) ? '1' : '0' );
		$settings->update( 'session_expiry_days', isset( $_POST['session_expiry_days'] ) ? absint( $_POST['session_expiry_days'] ) : 7 );
		$settings->update( 'password_min_length', isset( $_POST['password_min_length'] ) ? absint( $_POST['password_min_length'] ) : 8 );

		// Redirect settings
		$settings->update( 'default_login_redirect_url', isset( $_POST['default_login_redirect_url'] ) ? sanitize_text_field( wp_unslash( $_POST['default_login_redirect_url'] ) ) : '/account/' );

		do_action( 'anchor_admin_settings_saved' );

		wp_safe_redirect( admin_url( 'admin.php?page=anchor-settings&updated=1' ) );
		exit;
	}

	/**
	* Handle member save.
	*/
	private function handle_save_member() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'anchor' ) );
		}

		if ( ! isset( $_POST['anchor_member_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_member_nonce'] ) ), 'anchor_member' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'anchor' ) );
		}

		$member_id = isset( $_POST['member_id'] ) ? absint( $_POST['member_id'] ) : 0;
		$is_new    = $member_id === 0;

		$data = [
			'username'     => isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '',
			'email'        => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'display_name' => isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '',
			'status'       => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'pending',
		];

		// Set password only if provided
		if ( ! empty( $_POST['password'] ) ) {
			$data['password'] = $_POST['password']; // Will be hashed by Member class
		}

		// Email verified
		$data['email_verified'] = isset( $_POST['email_verified'] ) ? 1 : 0;

		$member_handler = new \Anchor\Member();

		if ( $is_new ) {
			// Require password for new members
			if ( empty( $_POST['password'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=anchor-member-new&error=password_required' ) );
				exit;
			}
			$result = $member_handler->create( $data );
		} else {
			$result = $member_handler->update( $member_id, $data );
		}

		if ( is_wp_error( $result ) ) {
			$redirect_url = $is_new
				? admin_url( 'admin.php?page=anchor-member-new&error=' . urlencode( $result->get_error_code() ) )
				: admin_url( 'admin.php?page=anchor-members&action=edit&member=' . $member_id . '&error=' . urlencode( $result->get_error_code() ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$new_member_id = $is_new ? $result : $member_id;
		wp_safe_redirect( admin_url( 'admin.php?page=anchor-members&action=edit&member=' . $new_member_id . '&updated=1' ) );
		exit;
	}

	/**
	* Render members list page.
	*/
	public function render_members_page() {
		// Check for edit action
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['member'] ) ) {
			$this->render_member_edit_page();
			return;
		}

		// Include list table class
		require_once ANCHOR_PLUGIN_DIR . 'admin/class-members-list-table.php';

		$list_table = new Members_List_Table();
		$list_table->prepare_items();

		include ANCHOR_PLUGIN_DIR . 'admin/views/members-list.php';
	}

	/**
	* Render member edit page.
	*/
	public function render_member_edit_page() {
		$member_id = isset( $_GET['member'] ) ? absint( $_GET['member'] ) : 0;
		$member    = null;
		$is_new    = $member_id === 0;

		if ( ! $is_new ) {
			$member_handler = new \Anchor\Member();
			$member = $member_handler->get( $member_id );

			if ( ! $member ) {
				wp_die( esc_html__( 'Member not found.', 'anchor' ) );
			}
		}

		include ANCHOR_PLUGIN_DIR . 'admin/views/member-edit.php';
	}

	/**
	* Render settings page.
	*/
	public function render_settings_page() {
		$settings = new \Anchor\Settings();
		include ANCHOR_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	* Render content access page.
	*/
	public function render_content_access_page() {
		$settings = new \Anchor\Settings();
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'access-levels';
		include ANCHOR_PLUGIN_DIR . 'admin/views/content-access.php';
	}

	/**
	* Handle content access settings save.
	*/
	private function handle_save_content_access() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'anchor' ) );
		}

		if ( ! isset( $_POST['anchor_content_access_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_content_access_nonce'] ) ), 'anchor_content_access' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'anchor' ) );
		}

		$settings = new \Anchor\Settings();
		$tab = isset( $_POST['anchor_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['anchor_tab'] ) ) : 'access-levels';

		if ( $tab === 'access-levels' ) {
			// Access Levels
			$settings->update( 'enable_registered', isset( $_POST['enable_registered'] ) ? '1' : '0' );
			$settings->update( 'enable_subscriber', isset( $_POST['enable_subscriber'] ) ? '1' : '0' );
			$settings->update( 'default_access_level', isset( $_POST['default_access_level'] ) ? sanitize_text_field( wp_unslash( $_POST['default_access_level'] ) ) : 'public' );
		} elseif ( $tab === 'metering' ) {
			// Metering
			$settings->update( 'enable_metering', isset( $_POST['enable_metering'] ) ? '1' : '0' );
			$settings->update( 'meter_limit_anonymous', isset( $_POST['meter_limit_anonymous'] ) ? absint( $_POST['meter_limit_anonymous'] ) : 5 );
			$settings->update( 'meter_period_anonymous', isset( $_POST['meter_period_anonymous'] ) ? sanitize_text_field( wp_unslash( $_POST['meter_period_anonymous'] ) ) : 'monthly' );
			$settings->update( 'meter_week_end_day', isset( $_POST['meter_week_end_day'] ) ? sanitize_text_field( wp_unslash( $_POST['meter_week_end_day'] ) ) : 'sunday' );
			$settings->update( 'meter_timezone', isset( $_POST['meter_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['meter_timezone'] ) ) : 'UTC' );
			$settings->update( 'meter_action_anonymous', isset( $_POST['meter_action_anonymous'] ) ? sanitize_text_field( wp_unslash( $_POST['meter_action_anonymous'] ) ) : 'require_registration' );

			// Registered member metering
			$settings->update( 'meter_limit_registered', isset( $_POST['meter_limit_registered'] ) ? '1' : '0' );
			$settings->update( 'meter_limit_registered_count', isset( $_POST['meter_limit_registered_count'] ) ? absint( $_POST['meter_limit_registered_count'] ) : 10 );
			$settings->update( 'meter_period_registered', isset( $_POST['meter_period_registered'] ) ? sanitize_text_field( wp_unslash( $_POST['meter_period_registered'] ) ) : 'monthly' );
		} elseif ( $tab === 'counting-rules' ) {
			// Post types
			$post_types = isset( $_POST['meter_post_types'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['meter_post_types'] ) ) : [ 'post' ];
			$settings->update( 'meter_post_types', $post_types );

			// Exemption rules (stored as JSON)
			$exemption_rules = [];
			if ( isset( $_POST['exemption_taxonomy'] ) && is_array( $_POST['exemption_taxonomy'] ) ) {
				$taxonomies = array_map( 'sanitize_text_field', wp_unslash( $_POST['exemption_taxonomy'] ) );
				$operators = isset( $_POST['exemption_operator'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['exemption_operator'] ) ) : [];
				$terms = isset( $_POST['exemption_term'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['exemption_term'] ) ) : [];

				foreach ( $taxonomies as $i => $taxonomy ) {
					if ( ! empty( $taxonomy ) && isset( $terms[ $i ] ) && ! empty( $terms[ $i ] ) ) {
						$exemption_rules[] = [
							'taxonomy' => $taxonomy,
							'operator' => isset( $operators[ $i ] ) ? $operators[ $i ] : 'is',
							'term'     => $terms[ $i ],
						];
					}
				}
			}
			$settings->update( 'meter_exemption_rules', wp_json_encode( $exemption_rules ) );

			// Time-decay ftw
			$settings->update( 'enable_time_decay', isset( $_POST['enable_time_decay'] ) ? '1' : '0' );
			$settings->update( 'time_decay_days', isset( $_POST['time_decay_days'] ) ? absint( $_POST['time_decay_days'] ) : 30 );
		}

		do_action( 'anchor_content_access_settings_saved', $tab );

		wp_safe_redirect( admin_url( 'admin.php?page=anchor-content-access&tab=' . $tab . '&updated=1' ) );
		exit;
	}

	/**
	* Get member count by status.
	*
	* @param string $status Optional status to filter by.
	* @return int Member count.
	*/
	public static function get_member_count( $status = '' ) {
		global $wpdb;
		$table = ANCHOR_TABLE_PREFIX . 'members';

		if ( $status ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE status = %s",
					$status
				)
			);
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}
}
