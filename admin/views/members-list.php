<?php
/**
* Members list admin view.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include ANCHOR_PLUGIN_DIR . 'admin/partials/header.php';
?>
<div class="wrap anchor-admin">
	<div class="anchor-page-header">
		<h1 class="anchor-page-title"><?php esc_html_e( 'Members', 'anchor' ); ?></h1>
		<p class="anchor-page-description"><?php esc_html_e( 'Manage your site members and their accounts.', 'anchor' ); ?></p>
	</div>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Member deleted successfully.', 'anchor' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Members updated successfully.', 'anchor' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'delete_failed' ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Failed to delete member.', 'anchor' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="anchor-stats">
		<?php
		$total     = \Anchor\Admin\Admin::get_member_count();
		$active    = \Anchor\Admin\Admin::get_member_count( 'active' );
		$pending   = \Anchor\Admin\Admin::get_member_count( 'pending' );
		$suspended = \Anchor\Admin\Admin::get_member_count( 'suspended' );
		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		?>
		<ul class="subsubsub">
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=anchor-members' ) ); ?>" <?php echo empty( $current_status ) ? 'class="current"' : ''; ?>>
					<?php esc_html_e( 'All', 'anchor' ); ?>
					<span class="count">(<?php echo esc_html( $total ); ?>)</span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=anchor-members&status=active' ) ); ?>" <?php echo ( $current_status === 'active' ) ? 'class="current"' : ''; ?>>
					<?php esc_html_e( 'Active', 'anchor' ); ?>
					<span class="count">(<?php echo esc_html( $active ); ?>)</span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=anchor-members&status=pending' ) ); ?>" <?php echo ( $current_status === 'pending' ) ? 'class="current"' : ''; ?>>
					<?php esc_html_e( 'Pending', 'anchor' ); ?>
					<span class="count">(<?php echo esc_html( $pending ); ?>)</span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=anchor-members&status=suspended' ) ); ?>" <?php echo ( $current_status === 'suspended' ) ? 'class="current"' : ''; ?>>
					<?php esc_html_e( 'Suspended', 'anchor' ); ?>
					<span class="count">(<?php echo esc_html( $suspended ); ?>)</span>
				</a>
			</li>
		</ul>
	</div>

	<form method="get">
		<input type="hidden" name="page" value="anchor-members">
		<?php if ( isset( $_GET['status'] ) ) : ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['status'] ) ) ); ?>">
		<?php endif; ?>
		<?php $list_table->search_box( __( 'Search Members', 'anchor' ), 'member' ); ?>
	</form>

	<form method="post">
		<?php $list_table->display(); ?>
	</form>
</div>
