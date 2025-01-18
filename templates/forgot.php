<?php
/**
* Forgot Password template.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error   = '';
$success = false;

// Form submission
if ( isset( $_POST['anchor_action'] ) && $_POST['anchor_action'] === 'forgot' ) {
	if ( ! isset( $_POST['anchor_forgot_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_forgot_nonce'] ) ), 'anchor_forgot' ) ) {
		$error = __( 'Security check failed. Please try again.', 'anchor' );
	} else {
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		$auth   = new \Anchor\Auth();
		$result = $auth->request_password_reset( $email );

		if ( is_wp_error( $result ) ) {
			$error = $result->get_error_message();
		} else {
			$success = true;
		}
	}
}

get_header();
?>

<div class="anchor-page anchor-forgot">
	<div class="anchor-container">
		<div class="anchor-form-wrapper">
			<h1><?php esc_html_e( 'Forgot Password', 'anchor' ); ?></h1>

			<?php if ( $success ) : ?>
				<div class="anchor-message anchor-success">
					<p><?php esc_html_e( 'If an account exists with that email address, we have sent a password reset link.', 'anchor' ); ?></p>
					<p><?php esc_html_e( 'Please check your email and follow the instructions.', 'anchor' ); ?></p>
				</div>

				<div class="anchor-alt-action">
					<a href="<?php echo esc_url( anchor_get_login_url() ); ?>">
						<?php esc_html_e( 'Return to sign in', 'anchor' ); ?>
					</a>
				</div>

			<?php else : ?>

				<p class="anchor-description">
					<?php esc_html_e( "Enter your email address and we'll send you a link to reset your password.", 'anchor' ); ?>
				</p>

				<?php if ( $error ) : ?>
					<div class="anchor-message anchor-error">
						<?php echo esc_html( $error ); ?>
					</div>
				<?php endif; ?>

				<form method="post" class="anchor-form">
					<?php wp_nonce_field( 'anchor_forgot', 'anchor_forgot_nonce' ); ?>
					<input type="hidden" name="anchor_action" value="forgot">

					<div class="anchor-field">
						<label for="email"><?php esc_html_e( 'Email Address', 'anchor' ); ?></label>
						<input type="email" id="email" name="email" required autocomplete="email" value="<?php echo isset( $_POST['email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : ''; ?>">
					</div>

					<div class="anchor-field">
						<button type="submit" name="anchor_forgot_submit" value="1" class="anchor-button">
							<?php esc_html_e( 'Send Reset Link', 'anchor' ); ?>
						</button>
					</div>
				</form>

				<div class="anchor-alt-action">
					<?php esc_html_e( 'Remember your password?', 'anchor' ); ?>
					<a href="<?php echo esc_url( anchor_get_login_url() ); ?>">
						<?php esc_html_e( 'Sign in', 'anchor' ); ?>
					</a>
				</div>

			<?php endif; ?>
		</div>
	</div>
</div>

<?php
get_footer();
