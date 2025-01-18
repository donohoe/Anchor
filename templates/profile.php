<?php
/**
* Member Profile/Dashboard template.
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

<div class="anchor-page anchor-profile">
	<div class="anchor-container">
		<div class="anchor-profile-header">
			<div class="anchor-avatar">
				<img src="<?php echo esc_url( anchor_get_member_avatar_url( $member->ID, 120 ) ); ?>" alt="<?php echo esc_attr( $member->display_name ); ?>">
			</div>
			<div class="anchor-profile-info">
				<h1><?php echo esc_html( $member->display_name ); ?></h1>
				<p class="anchor-username">@<?php echo esc_html( $member->username ); ?></p>
				<p class="anchor-member-since">
					<?php
					printf(
						esc_html__( 'Member since %s', 'anchor' ),
						esc_html( anchor_format_date( $member->created_at ) )
					);
					?>
				</p>
			</div>
		</div>

		<div class="anchor-profile-nav">
			<a href="<?php echo esc_url( anchor_get_profile_url() ); ?>" class="active">
				<?php esc_html_e( 'Dashboard', 'anchor' ); ?>
			</a>
			<a href="<?php echo esc_url( anchor_get_settings_url() ); ?>">
				<?php esc_html_e( 'Settings', 'anchor' ); ?>
			</a>
			<a href="<?php echo esc_url( anchor_get_logout_url() ); ?>">
				<?php esc_html_e( 'Sign Out', 'anchor' ); ?>
			</a>
		</div>

		<div class="anchor-profile-content">
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

			<?php do_action( 'anchor_after_profile_dashboard', $member ); ?>
		</div>
	</div>
</div>

<?php
get_footer();
