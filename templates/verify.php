<?php
/**
* Email Verification template.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$token   = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
$error   = '';
$success = false;

if ( $token ) {
	$auth   = new \Anchor\Auth();
	$result = $auth->verify_email( $token );

	if ( is_wp_error( $result ) ) {
		$error = $result->get_error_message();
	} else {
		$success = true;
	}
}

get_header();
?>

<div class="anchor-page anchor-verify">
	<div class="anchor-container">
		<div class="anchor-form-wrapper">
			<h1><?php esc_html_e( 'Email Verification', 'anchor' ); ?></h1>

			<?php if ( $success ) : ?>

				<div class="anchor-message anchor-success">
					<p><strong><?php esc_html_e( 'Email verified!', 'anchor' ); ?></strong></p>
					<p><?php esc_html_e( 'Your email address has been verified successfully. You can now sign in to your account.', 'anchor' ); ?></p>
				</div>

				<div class="anchor-alt-action">
					<a href="<?php echo esc_url( anchor_get_login_url() ); ?>" class="anchor-button">
						<?php esc_html_e( 'Sign In', 'anchor' ); ?>
					</a>
				</div>

			<?php elseif ( $error ) : ?>

				<div class="anchor-message anchor-error">
					<?php echo esc_html( $error ); ?>
				</div>

				<p class="anchor-description">
					<?php esc_html_e( 'The verification link may have expired. You can request a new verification email.', 'anchor' ); ?>
				</p>

				<div class="anchor-alt-action">
					<a href="<?php echo esc_url( anchor_get_login_url() ); ?>">
						<?php esc_html_e( 'Return to sign in', 'anchor' ); ?>
					</a>
				</div>

			<?php else : ?>

				<div class="anchor-message anchor-error">
					<?php esc_html_e( 'No verification token provided.', 'anchor' ); ?>
				</div>

				<div class="anchor-alt-action">
					<a href="<?php echo esc_url( anchor_get_login_url() ); ?>">
						<?php esc_html_e( 'Return to sign in', 'anchor' ); ?>
					</a>
				</div>

			<?php endif; ?>
		</div>
	</div>
</div>

<?php
get_footer();
