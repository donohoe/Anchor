<?php
/**
* Sign In template.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error   = '';
$success = '';

if ( isset( $_POST['anchor_action'] ) && $_POST['anchor_action'] === 'login' ) {
	if ( ! isset( $_POST['anchor_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_login_nonce'] ) ), 'anchor_login' ) ) {
		$error = __( 'Security check failed. Please try again.', 'anchor' );
	} else {
		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? $_POST['password'] : '';
		$remember = isset( $_POST['remember'] ) && $_POST['remember'] === '1';

		$auth   = new \Anchor\Auth();
		$result = $auth->login( $username, $password, $remember );

		if ( is_wp_error( $result ) ) {
			$error = $result->get_error_message();
		} else {
			// Successful login - lets redirect
			$settings    = new \Anchor\Settings();
			$redirect_to = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) ) : '';

			if ( empty( $redirect_to ) ) {
				$redirect_to = $settings->get( 'default_login_redirect_url', '/account/' );
			}

			$redirect_to = apply_filters( 'anchor_login_redirect', $redirect_to, $result );

			wp_safe_redirect( home_url( $redirect_to ) );
			exit;
		}
	}
}

if ( isset( $_GET['password_reset'] ) && $_GET['password_reset'] === 'success' ) {
	$success = __( 'Your password has been reset. You can now sign in.', 'anchor' );
}

if ( isset( $_GET['verified'] ) && $_GET['verified'] === 'success' ) {
	$success = __( 'Your email has been verified. You can now sign in.', 'anchor' );
}

get_header();
?>

<div class="anchor-page anchor-sign-in">
	<div class="anchor-container">
		<div class="anchor-form-wrapper">
			<h1><?php esc_html_e( 'Sign In', 'anchor' ); ?></h1>

			<?php if ( $error ) : ?>
				<div class="anchor-message anchor-error">
					<?php echo esc_html( $error ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $success ) : ?>
				<div class="anchor-message anchor-success">
					<?php echo esc_html( $success ); ?>
				</div>
			<?php endif; ?>

			<form method="post" class="anchor-form">
				<?php wp_nonce_field( 'anchor_login', 'anchor_login_nonce' ); ?>
				<input type="hidden" name="anchor_action" value="login">

				<div class="anchor-field">
					<label for="username"><?php esc_html_e( 'Username or Email', 'anchor' ); ?></label>
					<input type="text" id="username" name="username" required autocomplete="username" value="<?php echo isset( $_POST['username'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['username'] ) ) ) : ''; ?>">
				</div>

				<div class="anchor-field">
					<label for="password"><?php esc_html_e( 'Password', 'anchor' ); ?></label>
					<input type="password" id="password" name="password" required autocomplete="current-password">
				</div>

				<div class="anchor-field anchor-checkbox">
					<label>
						<input type="checkbox" name="remember" value="1">
						<?php esc_html_e( 'Remember me', 'anchor' ); ?>
					</label>
				</div>

				<div class="anchor-field">
					<button type="submit" name="anchor_login_submit" value="1" class="anchor-button">
						<?php esc_html_e( 'Sign In', 'anchor' ); ?>
					</button>
				</div>
			</form>

			<div class="anchor-links">
				<a href="<?php echo esc_url( anchor_get_forgot_url() ); ?>">
					<?php esc_html_e( 'Forgot your password?', 'anchor' ); ?>
				</a>
			</div>

			<?php if ( anchor_get_setting( 'allow_registration', '1' ) ) : ?>
				<div class="anchor-alt-action">
					<?php esc_html_e( "Don't have an account?", 'anchor' ); ?>
					<a href="<?php echo esc_url( anchor_get_register_url() ); ?>">
						<?php esc_html_e( 'Create one', 'anchor' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
get_footer();
