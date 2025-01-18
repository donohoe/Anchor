<?php
/**
* Public Profile template.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$username = get_query_var( 'anchor_username' );
$member   = anchor_get_member_by( 'username', $username );

// Are public profiles are enabled
$public_profiles_enabled = anchor_get_setting( 'enable_public_profiles', '0' );

get_header();
?>

<div class="anchor-page anchor-public-profile">
	<div class="anchor-container">
		<?php if ( ! $member ) : ?>

			<div class="anchor-message anchor-error">
				<?php esc_html_e( 'Member not found.', 'anchor' ); ?>
			</div>

		<?php elseif ( ! $public_profiles_enabled && ! anchor_is_member_logged_in() ) : ?>

			<div class="anchor-message anchor-error">
				<?php esc_html_e( 'Public profiles are not enabled.', 'anchor' ); ?>
			</div>

		<?php elseif ( $member->status !== 'active' ) : ?>

			<div class="anchor-message anchor-error">
				<?php esc_html_e( 'This profile is not available.', 'anchor' ); ?>
			</div>

		<?php else : ?>

			<div class="anchor-profile-header anchor-public">
				<div class="anchor-avatar">
					<img src="<?php echo esc_url( anchor_get_member_avatar_url( $member->ID, 150 ) ); ?>" alt="<?php echo esc_attr( $member->display_name ); ?>">
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

			<div class="anchor-public-profile-content">
				<?php
				do_action( 'anchor_public_profile_content', $member );
				?>
			</div>

		<?php endif; ?>
	</div>
</div>

<?php
get_footer();
