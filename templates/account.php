<?php
/**
* Account page template.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$member = anchor_get_current_member();

if ( ! $member ) {
	wp_safe_redirect( anchor_get_login_url() );
	exit;
}

get_header();
?>

<div class="anchor-page anchor-account">
	<div class="anchor-container">
		<div class="anchor-account-layout">
			<aside class="anchor-account-sidebar">
				<div class="anchor-profile-header">
					<div class="anchor-avatar">
						<img src="<?php echo esc_url( anchor_get_member_avatar_url( $member->ID, 96 ) ); ?>" alt="<?php echo esc_attr( $member->display_name ); ?>">
					</div>
					<div class="anchor-profile-info">
						<h2><?php echo esc_html( $member->display_name ); ?></h2>
						<p class="anchor-username">@<?php echo esc_html( $member->username ); ?></p>
					</div>
				</div>

				<nav class="anchor-account-nav">
					<a href="<?php echo esc_url( \Anchor\Router::get_account_url() ); ?>" class="active">
						<?php esc_html_e( 'Account', 'anchor' ); ?>
					</a>
					<a href="<?php echo esc_url( \Anchor\Router::get_newsletters_url() ); ?>">
						<?php esc_html_e( 'Newsletters', 'anchor' ); ?>
					</a>
					<a href="<?php echo esc_url( \Anchor\Router::get_appearance_url() ); ?>">
						<?php esc_html_e( 'Appearance', 'anchor' ); ?>
					</a>
					<a href="<?php echo esc_url( anchor_get_settings_url() ); ?>">
						<?php esc_html_e( 'Settings', 'anchor' ); ?>
					</a>
					<div class="anchor-nav-signout">
						<a href="<?php echo esc_url( anchor_get_logout_url() ); ?>">
							<?php esc_html_e( 'Sign Out', 'anchor' ); ?>
						</a>
					</div>
				</nav>
			</aside>

			<main class="anchor-account-main">
				<h1><?php esc_html_e( 'Account', 'anchor' ); ?></h1>
				<p class="anchor-page-description"><?php esc_html_e( 'Manage your subscription and account details.', 'anchor' ); ?></p>

				<div class="anchor-dashboard-grid">
					<div class="anchor-dashboard-card">
						<h3><?php esc_html_e( 'Account Status', 'anchor' ); ?></h3>
						<p class="anchor-status anchor-status-<?php echo esc_attr( $member->status ); ?>">
							<?php
							$statuses = [
								'active'    => __( 'Active', 'anchor' ),
								'pending'   => __( 'Pending', 'anchor' ),
								'suspended' => __( 'Suspended', 'anchor' ),
							];
							echo esc_html( isset( $statuses[ $member->status ] ) ? $statuses[ $member->status ] : $member->status );
							?>
						</p>
					</div>

					<div class="anchor-dashboard-card">
						<h3><?php esc_html_e( 'Email', 'anchor' ); ?></h3>
						<p><?php echo esc_html( $member->email ); ?></p>
						<?php if ( $member->email_verified ) : ?>
							<span class="anchor-verified"><?php esc_html_e( 'Verified', 'anchor' ); ?></span>
						<?php else : ?>
							<span class="anchor-unverified"><?php esc_html_e( 'Not verified', 'anchor' ); ?></span>
						<?php endif; ?>
					</div>

					<div class="anchor-dashboard-card">
						<h3><?php esc_html_e( 'Last Login', 'anchor' ); ?></h3>
						<p>
							<?php
							if ( $member->last_login ) {
								echo esc_html( anchor_format_date( $member->last_login, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
							} else {
								esc_html_e( 'Never', 'anchor' );
							}
							?>
						</p>
					</div>

					<div class="anchor-dashboard-card">
						<h3><?php esc_html_e( 'Roles', 'anchor' ); ?></h3>
						<p>
							<?php
							$roles = anchor_get_member_roles( $member->ID );
							if ( $roles ) {
								$role_names = array_map( function( $role ) {
									return $role->role_name;
								}, $roles );
								echo esc_html( implode( ', ', $role_names ) );
							} else {
								esc_html_e( 'None', 'anchor' );
							}
							?>
						</p>
					</div>
				</div>

				<?php do_action( 'anchor_account_page_content', $member ); ?>
			</main>
		</div>
	</div>
</div>

<?php
get_footer();
