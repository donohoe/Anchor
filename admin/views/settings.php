<?php
/**
* Settings admin view.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include ANCHOR_PLUGIN_DIR . 'admin/partials/header.php';
?>
<div class="wrap anchor-admin">
	<div class="anchor-page-header">
		<h1 class="anchor-page-title"><?php esc_html_e( 'Settings', 'anchor' ); ?></h1>
		<p class="anchor-page-description"><?php esc_html_e( 'Configure your member system settings.', 'anchor' ); ?></p>
	</div>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully.', 'anchor' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=anchor-settings' ) ); ?>">
		<?php wp_nonce_field( 'anchor_settings', 'anchor_settings_nonce' ); ?>

		<div class="anchor-box">
			<div class="anchor-form-section">
				<h3 class="anchor-form-section-title"><?php esc_html_e( 'General', 'anchor' ); ?></h3>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email Verification', 'anchor' ); ?></th>
							<td>
								<label for="require_email_verification">
									<input type="checkbox" name="require_email_verification" id="require_email_verification" value="1"
										<?php checked( $settings->get( 'require_email_verification', '1' ), '1' ); ?>>
									<?php esc_html_e( 'Require email verification for new members', 'anchor' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Members will receive a verification email and must verify before they can log in.', 'anchor' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Public Profiles', 'anchor' ); ?></th>
							<td>
								<label for="enable_public_profiles">
									<input type="checkbox" name="enable_public_profiles" id="enable_public_profiles" value="1"
										<?php checked( $settings->get( 'enable_public_profiles', '0' ), '1' ); ?>>
									<?php esc_html_e( 'Enable public member profiles', 'anchor' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Allow member profiles to be viewed at /account/{username}.', 'anchor' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="anchor-form-section">
				<h3 class="anchor-form-section-title"><?php esc_html_e( 'Security', 'anchor' ); ?></h3>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="session_expiry_days"><?php esc_html_e( 'Session Duration', 'anchor' ); ?></label>
							</th>
							<td>
								<input type="number" name="session_expiry_days" id="session_expiry_days" class="small-text" min="1" max="365"
									value="<?php echo esc_attr( $settings->get( 'session_expiry_days', '7' ) ); ?>">
								<?php esc_html_e( 'days', 'anchor' ); ?>
								<p class="description"><?php esc_html_e( 'How long member sessions remain valid before they need to log in again.', 'anchor' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="password_min_length"><?php esc_html_e( 'Minimum Password Length', 'anchor' ); ?></label>
							</th>
							<td>
								<input type="number" name="password_min_length" id="password_min_length" class="small-text" min="6" max="128"
									value="<?php echo esc_attr( $settings->get( 'password_min_length', '8' ) ); ?>">
								<?php esc_html_e( 'characters', 'anchor' ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="anchor-form-section">
				<h3 class="anchor-form-section-title"><?php esc_html_e( 'Redirects', 'anchor' ); ?></h3>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="default_login_redirect_url"><?php esc_html_e( 'After Login Redirect', 'anchor' ); ?></label>
							</th>
							<td>
								<input type="text" name="default_login_redirect_url" id="default_login_redirect_url" class="regular-text"
									value="<?php echo esc_attr( $settings->get( 'default_login_redirect_url', '/account/' ) ); ?>">
								<p class="description"><?php esc_html_e( 'Where to redirect members after they log in. Use a relative path starting with /.', 'anchor' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<?php do_action( 'anchor_admin_settings_form', $settings ); ?>

			<p class="submit">
				<input type="submit" name="anchor_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'anchor' ); ?>">
			</p>
		</div>
	</form>
</div>
