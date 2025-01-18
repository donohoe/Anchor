<?php
/**
* Admin header partial.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current page
$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
$current_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

// Define navigation items
$nav_items = [
	[
		'slug'  => 'anchor-members',
		'label' => __( 'Members', 'anchor' ),
		'url'   => admin_url( 'admin.php?page=anchor-members' ),
	],
	[
		'slug'  => 'anchor-member-new',
		'label' => __( 'Add New', 'anchor' ),
		'url'   => admin_url( 'admin.php?page=anchor-member-new' ),
	],
	[
		'slug'  => 'anchor-content-access',
		'label' => __( 'Content Access', 'anchor' ),
		'url'   => admin_url( 'admin.php?page=anchor-content-access' ),
	],
	[
		'slug'  => 'anchor-settings',
		'label' => __( 'Settings', 'anchor' ),
		'url'   => admin_url( 'admin.php?page=anchor-settings' ),
	],
];

// Check if editing a member (treat as members page)
$is_editing = $current_page === 'anchor-members' && $current_action === 'edit';
?>
<div class="anchor-admin-header">
	<div class="anchor-admin-header-inner">
		<div class="anchor-admin-brand">
			<span class="anchor-admin-logo">
				<span class="dashicons dashicons-groups"></span>
			</span>
			<span class="anchor-admin-title"><?php esc_html_e( 'Anchor', 'anchor' ); ?></span>
		</div>

		<nav class="anchor-admin-nav">
			<?php foreach ( $nav_items as $item ) : ?>
				<?php
				$is_active = $current_page === $item['slug'];
				// Special case: editing member should highlight Members tab
				if ( $is_editing && $item['slug'] === 'anchor-members' ) {
					$is_active = true;
				}
				if ( $is_editing && $item['slug'] === 'anchor-member-new' ) {
					$is_active = false;
				}
				?>
				<a href="<?php echo esc_url( $item['url'] ); ?>" class="anchor-admin-nav-item <?php echo $is_active ? 'active' : ''; ?>">
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="anchor-admin-meta">
			<span class="anchor-admin-version">v<?php echo esc_html( ANCHOR_VERSION ); ?></span>
		</div>
	</div>
</div>
