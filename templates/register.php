<?php
/**
* Register template.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error           = '';
$success         = false;
$require_verify  = anchor_get_setting( 'require_email_verification', '1' );

if ( isset( $_POST['anchor_action'] ) && $_POST['anchor_action'] === 'register' ) {
	if ( ! isset( $_POST['anchor_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_register_nonce'] ) ), 'anchor_register' ) ) {
		$error = __( 'Security check failed. Please try again.', 'anchor' );
	} else {
		$data = [
			'username' => isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '',
			'email'    => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'password' => isset( $_POST['password'] ) ? $_POST['password'] : '',
		];

		// Confirm password
		$password_confirm = isset( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';

		if ( $data['password'] !== $password_confirm ) {
			$error = __( 'Passwords do not match.', 'anchor' );
		} else {
			$auth   = new \Anchor\Auth();
			$result = $auth->register( $data );

			if ( is_wp_error( $result ) ) {
				$error = $result->get_error_message();
			} else {
				$success = true;

				// Auto-login if verification not required
				if ( ! $require_verify ) {
					$auth->login( $data['username'], $data['password'] );
					wp_safe_redirect( home_url( '/account/' ) );
					exit;
				}
			}
		}
	}
}

get_header();
?>

<div class="anchor-page anchor-register">
	<div class="anchor-container">
		<div class="anchor-form-wrapper">
			<h1><?php esc_html_e( 'Create Account', 'anchor' ); ?></h1>

			<?php if ( $success ) : ?>
				<div class="anchor-message anchor-success">
					<?php if ( $require_verify ) : ?>
						<p><strong><?php esc_html_e( 'Check your email!', 'anchor' ); ?></strong></p>
						<p><?php esc_html_e( "We've sent a verification link to your email address. Please click the link to verify your account.", 'anchor' ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'Your account has been created successfully!', 'anchor' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="anchor-alt-action">
					<a href="<?php echo esc_url( anchor_get_login_url() ); ?>">
						<?php esc_html_e( 'Sign in to your account', 'anchor' ); ?>
					</a>
				</div>

			<?php else : ?>

				<?php if ( $error ) : ?>
					<div class="anchor-message anchor-error">
						<?php echo esc_html( $error ); ?>
					</div>
				<?php endif; ?>

				<form method="post" class="anchor-form">
					<?php wp_nonce_field( 'anchor_register', 'anchor_register_nonce' ); ?>
					<input type="hidden" name="anchor_action" value="register">

					<div class="anchor-field">
						<label for="username"><?php esc_html_e( 'Username', 'anchor' ); ?></label>
						<input type="text" id="username" name="username" required autocomplete="username" value="<?php echo isset( $_POST['username'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['username'] ) ) ) : ''; ?>">
						<span class="anchor-hint"><?php esc_html_e( 'Letters, numbers, and underscores only.', 'anchor' ); ?></span>
					</div>

					<div class="anchor-field">
						<label for="email"><?php esc_html_e( 'Email Address', 'anchor' ); ?></label>
						<input type="email" id="email" name="email" required autocomplete="email" value="<?php echo isset( $_POST['email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : ''; ?>">
					</div>

					<div class="anchor-field">
						<label for="password"><?php esc_html_e( 'Password', 'anchor' ); ?></label>
						<input type="password" id="password" name="password" required autocomplete="new-password" minlength="<?php echo esc_attr( anchor_get_setting( 'password_min_length', '8' ) ); ?>">
						<span class="anchor-hint">
							<?php
							printf(
								/* translators: %d: minimum password length */
								esc_html__( 'At least %d characters.', 'anchor' ),
								(int) anchor_get_setting( 'password_min_length', '8' )
							);
							?>
						</span>
					</div>

					<div class="anchor-field">
						<label for="password_confirm"><?php esc_html_e( 'Confirm Password', 'anchor' ); ?></label>
						<input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
					</div>

					<div class="anchor-field">
						<button type="submit" name="anchor_register_submit" value="1" class="anchor-button">
							<?php esc_html_e( 'Create Account', 'anchor' ); ?>
						</button>
					</div>
				</form>

				<div class="anchor-alt-action">
					<?php esc_html_e( 'Already have an account?', 'anchor' ); ?>
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
