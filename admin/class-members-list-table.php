<?php
/**
* Members List Table class.
*
* Extends WP_List_Table to display members in admin.
*
* @package Anchor
*/

namespace Anchor\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not already loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
* Members List Table class.
*/
class Members_List_Table extends \WP_List_Table {

	public function __construct() {
		parent::__construct( [
			'singular' => 'member',
			'plural'   => 'members',
			'ajax'     => false,
		] );
	}

	/**
	* Get table columns.
	*
	* @return array Columns array.
	*/
	public function get_columns() {
		return [
			'cb'            => '<input type="checkbox" />',
			'username'      => __( 'Username', 'anchor' ),
			'email'         => __( 'Email', 'anchor' ),
			'display_name'  => __( 'Display Name', 'anchor' ),
			'status'        => __( 'Status', 'anchor' ),
			'email_verified'=> __( 'Verified', 'anchor' ),
			'created_at'    => __( 'Registered', 'anchor' ),
		];
	}

	/**
	* Get sortable columns.
	*
	* @return array Sortable columns.
	*/
	public function get_sortable_columns() {
		return [
			'username'   => [ 'username', false ],
			'email'      => [ 'email', false ],
			'status'     => [ 'status', false ],
			'created_at' => [ 'created_at', true ],
		];
	}

	/**
	* Get bulk actions.
	*
	* @return array Bulk actions.
	*/
	public function get_bulk_actions() {
		return [
			'delete'    => __( 'Delete', 'anchor' ),
			'activate'  => __( 'Activate', 'anchor' ),
			'suspend'   => __( 'Suspend', 'anchor' ),
			'verify'    => __( 'Mark as Verified', 'anchor' ),
		];
	}

	/**
	* Process bulk actions.
	*/
	public function process_bulk_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		// Verify the nonce
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bulk-members' ) ) {
			return;
		}

		$member_ids = isset( $_REQUEST['member'] ) ? array_map( 'absint', (array) $_REQUEST['member'] ) : [];

		if ( empty( $member_ids ) ) {
			return;
		}

		$member_handler = new \Anchor\Member();

		switch ( $action ) {
			case 'delete':
				foreach ( $member_ids as $member_id ) {
					$member_handler->delete( $member_id );
				}
				break;

			case 'activate':
				foreach ( $member_ids as $member_id ) {
					$member_handler->update( $member_id, [ 'status' => 'active' ] );
				}
				break;

			case 'suspend':
				foreach ( $member_ids as $member_id ) {
					$member_handler->update( $member_id, [ 'status' => 'suspended' ] );
				}
				break;

			case 'verify':
				foreach ( $member_ids as $member_id ) {
					$member_handler->update( $member_id, [ 'email_verified' => 1 ] );
				}
				break;
		}

		// Redirect that removes action parameters
		wp_safe_redirect( admin_url( 'admin.php?page=anchor-members&updated=1' ) );
		exit;
	}

	/**
	* Prepare items for display.
	*/
	public function prepare_items() {

		$this->process_bulk_action();

		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// Get filter parameters
		$status = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		// Get sort parameters
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';

		// Get members
		$args = [
			'number'  => $per_page,
			'offset'  => ( $current_page - 1 ) * $per_page,
			'orderby' => $orderby,
			'order'   => $order,
		];

		if ( $status ) {
			$args['status'] = $status;
		}

		if ( $search ) {
			$args['search'] = $search;
		}

		$member_handler = new \Anchor\Member();
		$this->items    = $member_handler->get_all( $args );
		$total_items    = $member_handler->count( $args );

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		] );
	}

	/**
	* Display when no items found.
	*/
	public function no_items() {
		esc_html_e( 'No members found.', 'anchor' );
	}

	/**
	* Checkbox column.
	*
	* @param object $item Member object.
	* @return string Checkbox HTML.
	*/
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="member[]" value="%d" />',
			$item->ID
		);
	}

	/**
	* Username column.
	*
	* @param object $item Member object.
	* @return string Username with actions.
	*/
	public function column_username( $item ) {
		$edit_url   = admin_url( 'admin.php?page=anchor-members&action=edit&member=' . $item->ID );
		$delete_url = wp_nonce_url(
			admin_url( 'admin.php?page=anchor-members&action=delete&member=' . $item->ID ),
			'anchor_delete_member_' . $item->ID
		);

		$actions = [
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'anchor' ) ),
			'delete' => sprintf(
				'<a href="%s" class="anchor-delete-member" data-member-id="%d">%s</a>',
				esc_url( $delete_url ),
				$item->ID,
				esc_html__( 'Delete', 'anchor' )
			),
		];

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $item->username ),
			$this->row_actions( $actions )
		);
	}

	/**
	* Email column.
	*
	* @param object $item Member object.
	* @return string Email.
	*/
	public function column_email( $item ) {
		return esc_html( $item->email );
	}

	/**
	* Display name column.
	*
	* @param object $item Member object.
	* @return string Display name.
	*/
	public function column_display_name( $item ) {
		return esc_html( $item->display_name ?: '—' );
	}

	/**
	* Status column.
	*
	* @param object $item Member object.
	* @return string Status badge.
	*/
	public function column_status( $item ) {
		$statuses = [
			'active'    => __( 'Active', 'anchor' ),
			'pending'   => __( 'Pending', 'anchor' ),
			'suspended' => __( 'Suspended', 'anchor' ),
		];

		$label = isset( $statuses[ $item->status ] ) ? $statuses[ $item->status ] : $item->status;

		return sprintf(
			'<span class="anchor-status anchor-status-%s">%s</span>',
			esc_attr( $item->status ),
			esc_html( $label )
		);
	}

	/**
	* Email verified column.
	*
	* @param object $item Member object.
	* @return string Verified status.
	*/
	public function column_email_verified( $item ) {
		if ( $item->email_verified ) {
			return '<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="' . esc_attr__( 'Verified', 'anchor' ) . '"></span>';
		}
		return '<span class="dashicons dashicons-marker" style="color: #dc3232;" title="' . esc_attr__( 'Not verified', 'anchor' ) . '"></span>';
	}

	/**
	* Created at column.
	*
	* @param object $item Member object.
	* @return string Formatted date.
	*/
	public function column_created_at( $item ) {
		return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->created_at ) ) );
	}

	/**
	* Default column handler.
	*
	* @param object $item Member object.
	* @param string $column_name Column name.
	* @return string Column content.
	*/
	public function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '—';
	}

	/**
	* Extra table navigation (filters).
	*
	* @param string $which Top or bottom.
	*/
	protected function extra_tablenav( $which ) {
		if ( $which !== 'top' ) {
			return;
		}

		$current_status = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';
		?>
		<div class="alignleft actions">
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'anchor' ); ?></option>
				<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'anchor' ); ?></option>
				<option value="pending" <?php selected( $current_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'anchor' ); ?></option>
				<option value="suspended" <?php selected( $current_status, 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'anchor' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'anchor' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}
}
