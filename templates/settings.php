<?php
/**
* Settings page template.
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

$error   = '';
$success = '';

// Profile update
if ( isset( $_POST['anchor_action'] ) && $_POST['anchor_action'] === 'settings' ) {
	if ( ! isset( $_POST['anchor_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_settings_nonce'] ) ), 'anchor_settings' ) ) {
		$error = __( 'Security check failed. Please try again.', 'anchor' );
	} else {
		$data = [];

		// Display name
		if ( isset( $_POST['display_name'] ) ) {
			$data['display_name'] = sanitize_text_field( wp_unslash( $_POST['display_name'] ) );
		}

		// Email
		if ( isset( $_POST['email'] ) ) {
			$data['email'] = sanitize_email( wp_unslash( $_POST['email'] ) );
		}

		$member_handler = new \Anchor\Member();
		$result         = $member_handler->update( $member->ID, $data );

		if ( is_wp_error( $result ) ) {
			$error = $result->get_error_message();
		} else {
			$success = __( 'Your settings have been updated.', 'anchor' );
			// Refresh data
			$member = anchor_get_member( $member->ID );
		}
	}
}

// Password change
if ( isset( $_POST['anchor_action'] ) && $_POST['anchor_action'] === 'password' ) {
	if ( ! isset( $_POST['anchor_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anchor_password_nonce'] ) ), 'anchor_password' ) ) {
		$error = __( 'Security check failed. Please try again.', 'anchor' );
	} else {
		$current_password = isset( $_POST['current_password'] ) ? $_POST['current_password'] : '';
		$new_password     = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
		$confirm_password = isset( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

		// Verify current password
		if ( ! wp_check_password( $current_password, $member->password_hash, $member->ID ) ) {
			$error = __( 'Current password is incorrect.', 'anchor' );
		} elseif ( $new_password !== $confirm_password ) {
			$error = __( 'New passwords do not match.', 'anchor' );
		} else {
			$member_handler = new \Anchor\Member();
			$result         = $member_handler->update( $member->ID, [ 'password' => $new_password ] );

			if ( is_wp_error( $result ) ) {
				$error = $result->get_error_message();
			} else {
				$success = __( 'Your password has been changed.', 'anchor' );
			}
		}
	}
}

get_header();
?>

<div class="anchor-page anchor-settings">
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
					<a href="<?php echo esc_url( \Anchor\Router::get_newsletters_url() ); ?>">
						<?php esc_html_e( 'Newsletters', 'anchor' ); ?>
					</a>
					<a href="<?php echo esc_url( \Anchor\Router::get_appearance_url() ); ?>">
						<?php esc_html_e( 'Appearance', 'anchor' ); ?>
					</a>
					<a href="<?php echo esc_url( anchor_get_settings_url() ); ?>" class="active">
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
				<h1><?php esc_html_e( 'Settings', 'anchor' ); ?></h1>
				<p class="anchor-page-description"><?php esc_html_e( 'Update your profile information and password.', 'anchor' ); ?></p>

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

				<div class="anchor-settings-section">
					<h2><?php esc_html_e( 'Profile', 'anchor' ); ?></h2>

					<form method="post" class="anchor-form">
						<?php wp_nonce_field( 'anchor_settings', 'anchor_settings_nonce' ); ?>
						<input type="hidden" name="anchor_action" value="settings">

						<div class="anchor-field">
							<label for="display_name"><?php esc_html_e( 'Display Name', 'anchor' ); ?></label>
							<input type="text" id="display_name" name="display_name" value="<?php echo esc_attr( $member->display_name ); ?>">
						</div>

						<div class="anchor-field">
							<label for="email"><?php esc_html_e( 'Email Address', 'anchor' ); ?></label>
							<input type="email" id="email" name="email" value="<?php echo esc_attr( $member->email ); ?>" required>
							<?php if ( ! $member->email_verified ) : ?>
								<span class="anchor-hint anchor-warning"><?php esc_html_e( 'Your email is not verified.', 'anchor' ); ?></span>
							<?php endif; ?>
						</div>

						<div class="anchor-field">
							<button type="submit" class="anchor-button">
								<?php esc_html_e( 'Save Changes', 'anchor' ); ?>
							</button>
						</div>
					</form>
				</div>

				<div class="anchor-settings-section">
					<h2><?php esc_html_e( 'Password', 'anchor' ); ?></h2>

					<form method="post" class="anchor-form">
						<?php wp_nonce_field( 'anchor_password', 'anchor_password_nonce' ); ?>
						<input type="hidden" name="anchor_action" value="password">

						<div class="anchor-field">
							<label for="current_password"><?php esc_html_e( 'Current Password', 'anchor' ); ?></label>
							<input type="password" id="current_password" name="current_password" required autocomplete="current-password">
						</div>

						<div class="anchor-field">
							<label for="new_password"><?php esc_html_e( 'New Password', 'anchor' ); ?></label>
							<input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="<?php echo esc_attr( anchor_get_setting( 'password_min_length', '8' ) ); ?>">
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
							<label for="confirm_password"><?php esc_html_e( 'Confirm New Password', 'anchor' ); ?></label>
							<input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
						</div>

						<div class="anchor-field">
							<button type="submit" class="anchor-button">
								<?php esc_html_e( 'Change Password', 'anchor' ); ?>
							</button>
						</div>
					</form>
				</div>

				<?php do_action( 'anchor_after_settings_forms', $member ); ?>
			</main>
		</div>
	</div>
</div>

<?php
get_footer();
