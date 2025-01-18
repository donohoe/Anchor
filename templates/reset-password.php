<?php
/**
* Reset Password template.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error       = '';
$token       = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
$valid_token = false;

// Validate token
if ( $token ) {
	$token_handler = new \Anchor\Token();
	$member_id     = $token_handler->validate( $token, \Anchor\Token::TYPE_RESET_PASSWORD );
	$valid_token   = (bool) $member_id;
}

if ( isset( $_POST['anchor_action'] ) && $_POST['anchor_action'] === 'reset' && $valid_token ) {
	if ( ! isset( $_POST['anchor_reset_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_reset_nonce'] ) ), 'anchor_reset' ) ) {
		$error = __( 'Security check failed. Please try again.', 'anchor' );
	} else {
		$password         = isset( $_POST['password'] ) ? $_POST['password'] : '';
		$password_confirm = isset( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';

		if ( $password !== $password_confirm ) {
			$error = __( 'Passwords do not match.', 'anchor' );
		} else {
			$auth   = new \Anchor\Auth();
			$result = $auth->reset_password( $token, $password );

			if ( is_wp_error( $result ) ) {
				$error = $result->get_error_message();
			} else {
				// Redirect to login with success message
				wp_safe_redirect( anchor_get_login_url() . '?password_reset=success' );
				exit;
			}
		}
	}
}

get_header();
?>

<div class="anchor-page anchor-reset-password">
	<div class="anchor-container">
		<div class="anchor-form-wrapper">
			<h1><?php esc_html_e( 'Reset Password', 'anchor' ); ?></h1>

			<?php if ( ! $token || ! $valid_token ) : ?>

				<div class="anchor-message anchor-error">
					<p><?php esc_html_e( 'This password reset link is invalid or has expired.', 'anchor' ); ?></p>
				</div>

				<div class="anchor-alt-action">
					<a href="<?php echo esc_url( anchor_get_forgot_url() ); ?>">
						<?php esc_html_e( 'Request a new reset link', 'anchor' ); ?>
					</a>
				</div>

			<?php else : ?>

				<p class="anchor-description">
					<?php esc_html_e( 'Enter your new password below.', 'anchor' ); ?>
				</p>

				<?php if ( $error ) : ?>
					<div class="anchor-message anchor-error">
						<?php echo esc_html( $error ); ?>
					</div>
				<?php endif; ?>

				<form method="post" class="anchor-form">
					<?php wp_nonce_field( 'anchor_reset', 'anchor_reset_nonce' ); ?>
					<input type="hidden" name="anchor_action" value="reset">

					<div class="anchor-field">
						<label for="password"><?php esc_html_e( 'New Password', 'anchor' ); ?></label>
						<input type="password" id="password" name="password" required autocomplete="new-password" minlength="<?php echo esc_attr( anchor_get_setting( 'password_min_length', '8' ) ); ?>">
						<span class="anchor-hint">
							<?php
							printf(
								esc_html__( 'At least %d characters.', 'anchor' ),
								(int) anchor_get_setting( 'password_min_length', '8' )
							);
							?>
						</span>
					</div>

					<div class="anchor-field">
						<label for="password_confirm"><?php esc_html_e( 'Confirm New Password', 'anchor' ); ?></label>
						<input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
					</div>

					<div class="anchor-field">
						<button type="submit" name="anchor_reset_submit" value="1" class="anchor-button">
							<?php esc_html_e( 'Reset Password', 'anchor' ); ?>
						</button>
					</div>
				</form>

			<?php endif; ?>
		</div>
	</div>
</div>

<?php
get_footer();
