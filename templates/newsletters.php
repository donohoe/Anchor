<?php
/**
* Newsletters page template.
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

<div class="anchor-page anchor-newsletters">
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
					<a href="<?php echo esc_url( \Anchor\Router::get_account_url() ); ?>">
						<?php esc_html_e( 'Account', 'anchor' ); ?>
					</a>
					<a href="<?php echo esc_url( \Anchor\Router::get_newsletters_url() ); ?>" class="active">
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
				<h1><?php esc_html_e( 'Newsletters', 'anchor' ); ?></h1>
				<p class="anchor-page-description"><?php esc_html_e( 'Manage your newsletter subscriptions and preferences.', 'anchor' ); ?></p>

				<div class="anchor-stub">
					<p><?php esc_html_e( 'Newsletter preferences will appear here.', 'anchor' ); ?></p>
				</div>

				<?php do_action( 'anchor_newsletters_page_content', $member ); ?>
			</main>
		</div>
	</div>
</div>

<?php
get_footer();
