<?php
/**
* Member edit admin view.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title = $is_new ? __( 'Add New Member', 'anchor' ) : __( 'Edit Member', 'anchor' );
$page_description = $is_new
	? __( 'Create a new member account.', 'anchor' )
	: sprintf(
		__( 'Editing member: %s', 'anchor' ),
		$member->username
	);


include ANCHOR_PLUGIN_DIR . 'admin/partials/header.php';
?>
<div class="wrap anchor-admin">
	<div class="anchor-page-header">
		<h1 class="anchor-page-title"><?php echo esc_html( $page_title ); ?></h1>
		<p class="anchor-page-description"><?php echo esc_html( $page_description ); ?></p>
	</div>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Member saved successfully.', 'anchor' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['error'] ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				$error_code = sanitize_text_field( wp_unslash( $_GET['error'] ) );
				switch ( $error_code ) {
					case 'password_required':
						esc_html_e( 'Password is required for new members.', 'anchor' );
						break;
					case 'username_exists':
						esc_html_e( 'Username already exists.', 'anchor' );
						break;
					case 'email_exists':
						esc_html_e( 'Email already exists.', 'anchor' );
						break;
					case 'invalid_email':
						esc_html_e( 'Invalid email address.', 'anchor' );
						break;
					default:
						esc_html_e( 'An error occurred while saving the member.', 'anchor' );
				}
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="anchor-box">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=anchor-members' ) ); ?>">
			<?php wp_nonce_field( 'anchor_member', 'anchor_member_nonce' ); ?>
			<input type="hidden" name="member_id" value="<?php echo esc_attr( $is_new ? 0 : $member->ID ); ?>">

			<div class="anchor-form-section">
				<h3 class="anchor-form-section-title"><?php esc_html_e( 'Account Details', 'anchor' ); ?></h3>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="username"><?php esc_html_e( 'Username', 'anchor' ); ?></label>
							</th>
							<td>
								<?php if ( $is_new ) : ?>
									<input type="text" name="username" id="username" class="regular-text" required
										value="<?php echo isset( $_POST['username'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['username'] ) ) ) : ''; ?>">
									<p class="description"><?php esc_html_e( 'Letters, numbers, and underscores only.', 'anchor' ); ?></p>
								<?php else : ?>
									<input type="text" class="regular-text" value="<?php echo esc_attr( $member->username ); ?>" disabled>
									<input type="hidden" name="username" value="<?php echo esc_attr( $member->username ); ?>">
									<p class="description"><?php esc_html_e( 'Username cannot be changed.', 'anchor' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="email"><?php esc_html_e( 'Email', 'anchor' ); ?></label>
							</th>
							<td>
								<input type="email" name="email" id="email" class="regular-text" required
									value="<?php echo esc_attr( $is_new ? '' : $member->email ); ?>">
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="display_name"><?php esc_html_e( 'Display Name', 'anchor' ); ?></label>
							</th>
							<td>
								<input type="text" name="display_name" id="display_name" class="regular-text"
									value="<?php echo esc_attr( $is_new ? '' : $member->display_name ); ?>">
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="password"><?php esc_html_e( 'Password', 'anchor' ); ?></label>
							</th>
							<td>
								<input type="password" name="password" id="password" class="regular-text" autocomplete="new-password"
									<?php echo $is_new ? 'required' : ''; ?>>
								<?php if ( ! $is_new ) : ?>
									<p class="description"><?php esc_html_e( 'Leave blank to keep current password.', 'anchor' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="anchor-form-section">
				<h3 class="anchor-form-section-title"><?php esc_html_e( 'Status', 'anchor' ); ?></h3>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="status"><?php esc_html_e( 'Account Status', 'anchor' ); ?></label>
							</th>
							<td>
								<select name="status" id="status">
									<option value="pending" <?php selected( $is_new ? 'pending' : $member->status, 'pending' ); ?>>
										<?php esc_html_e( 'Pending', 'anchor' ); ?>
									</option>
									<option value="active" <?php selected( $is_new ? '' : $member->status, 'active' ); ?>>
										<?php esc_html_e( 'Active', 'anchor' ); ?>
									</option>
									<option value="suspended" <?php selected( $is_new ? '' : $member->status, 'suspended' ); ?>>
										<?php esc_html_e( 'Suspended', 'anchor' ); ?>
									</option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Email Verified', 'anchor' ); ?></th>
							<td>
								<label for="email_verified">
									<input type="checkbox" name="email_verified" id="email_verified" value="1"
										<?php checked( ! $is_new && $member->email_verified ); ?>>
									<?php esc_html_e( 'Email address has been verified', 'anchor' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<?php if ( ! $is_new ) : ?>
				<div class="anchor-form-section">
					<h3 class="anchor-form-section-title"><?php esc_html_e( 'Information', 'anchor' ); ?></h3>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Registered', 'anchor' ); ?></th>
								<td>
									<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $member->created_at ) ) ); ?>
								</td>
							</tr>

							<?php if ( $member->last_login ) : ?>
								<tr>
									<th scope="row"><?php esc_html_e( 'Last Login', 'anchor' ); ?></th>
									<td>
										<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $member->last_login ) ) ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<?php do_action( 'anchor_admin_member_edit_form', $member, $is_new ); ?>

			<p class="submit">
				<input type="submit" name="anchor_save_member" class="button button-primary" value="<?php echo esc_attr( $is_new ? __( 'Add Member', 'anchor' ) : __( 'Update Member', 'anchor' ) ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=anchor-members' ) ); ?>" class="button">
					<?php esc_html_e( 'Cancel', 'anchor' ); ?>
				</a>
			</p>
		</form>
	</div>

	<?php if ( ! $is_new ) : ?>
		<div class="anchor-box">
			<div class="anchor-member-actions">
				<h2><?php esc_html_e( 'Danger Zone', 'anchor' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Permanently delete this member and all associated data.', 'anchor' ); ?></p>
				<p style="margin-top: 12px;">
					<?php
					$delete_url = wp_nonce_url(
						admin_url( 'admin.php?page=anchor-members&action=delete&member=' . $member->ID ),
						'anchor_delete_member_' . $member->ID
					);
					?>
					<a href="<?php echo esc_url( $delete_url ); ?>" class="button anchor-delete-member" data-member-id="<?php echo esc_attr( $member->ID ); ?>">
						<?php esc_html_e( 'Delete Member', 'anchor' ); ?>
					</a>
				</p>
			</div>
		</div>
	<?php endif; ?>
</div>
