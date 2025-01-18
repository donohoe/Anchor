<?php
/**
* Content Access admin view.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


include ANCHOR_PLUGIN_DIR . 'admin/partials/header.php';

// Get all public post types
$post_types = get_post_types( [ 'public' => true ], 'objects' );
unset( $post_types['attachment'] );

// Get all taxonomies
$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

// Current settings
$enable_registered            = $settings->get( 'enable_registered', '0' );
$enable_subscriber            = $settings->get( 'enable_subscriber', '0' );
$default_access_level         = $settings->get( 'default_access_level', 'public' );
$enable_metering              = $settings->get( 'enable_metering', '0' );
$meter_limit_anonymous        = $settings->get( 'meter_limit_anonymous', '5' );
$meter_period_anonymous       = $settings->get( 'meter_period_anonymous', 'monthly' );
$meter_week_end_day           = $settings->get( 'meter_week_end_day', 'sunday' );
$meter_timezone               = $settings->get( 'meter_timezone', 'UTC' );
$meter_action_anonymous       = $settings->get( 'meter_action_anonymous', 'require_registration' );
$meter_limit_registered       = $settings->get( 'meter_limit_registered', '0' );
$meter_limit_registered_count = $settings->get( 'meter_limit_registered_count', '10' );
$meter_period_registered      = $settings->get( 'meter_period_registered', 'monthly' );

// Counting rules settings
$meter_post_types = $settings->get( 'meter_post_types', [ 'post' ] );
if ( ! is_array( $meter_post_types ) ) {
	$meter_post_types = [ 'post' ];
}
$exemption_rules_json = $settings->get( 'meter_exemption_rules', '[]' );
$exemption_rules = json_decode( $exemption_rules_json, true );
if ( ! is_array( $exemption_rules ) ) {
	$exemption_rules = [];
}
$enable_time_decay = $settings->get( 'enable_time_decay', '0' );
$time_decay_days   = $settings->get( 'time_decay_days', '30' );

// Timezones list
$timezones = timezone_identifiers_list();

// Days of the week
$days_of_week = [
	'sunday'    => __( 'Sunday', 'anchor' ),
	'monday'    => __( 'Monday', 'anchor' ),
	'tuesday'   => __( 'Tuesday', 'anchor' ),
	'wednesday' => __( 'Wednesday', 'anchor' ),
	'thursday'  => __( 'Thursday', 'anchor' ),
	'friday'    => __( 'Friday', 'anchor' ),
	'saturday'  => __( 'Saturday', 'anchor' ),
];
?>
<div class="wrap anchor-admin">
	<div class="anchor-page-header">
		<h1 class="anchor-page-title"><?php esc_html_e( 'Content Access', 'anchor' ); ?></h1>
		<p class="anchor-page-description"><?php esc_html_e( 'Configure content access levels and metering rules.', 'anchor' ); ?></p>
	</div>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully.', 'anchor' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="anchor-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=anchor-content-access&tab=access-levels' ) ); ?>"
		class="anchor-tab <?php echo $current_tab === 'access-levels' ? 'active' : ''; ?>">
			<?php esc_html_e( 'Access Levels', 'anchor' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=anchor-content-access&tab=metering' ) ); ?>"
		class="anchor-tab <?php echo $current_tab === 'metering' ? 'active' : ''; ?>">
			<?php esc_html_e( 'Metering', 'anchor' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=anchor-content-access&tab=counting-rules' ) ); ?>"
		class="anchor-tab <?php echo $current_tab === 'counting-rules' ? 'active' : ''; ?>">
			<?php esc_html_e( 'Counting Rules', 'anchor' ); ?>
		</a>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=anchor-content-access' ) ); ?>" id="anchor-content-access-form">
		<?php wp_nonce_field( 'anchor_content_access', 'anchor_content_access_nonce' ); ?>
		<input type="hidden" name="anchor_tab" value="<?php echo esc_attr( $current_tab ); ?>">

		<?php if ( $current_tab === 'access-levels' ) : ?>
			<!-- Tab 1: Access Levels -->
			<div class="anchor-box">
				<div class="anchor-form-section">
					<h3 class="anchor-form-section-title"><?php esc_html_e( 'Access Levels', 'anchor' ); ?></h3>
					<p class="anchor-section-description"><?php esc_html_e( 'Enable the access levels your site needs. Subscribers are automatically registered members.', 'anchor' ); ?></p>

					<div class="anchor-checkbox-group">
						<label class="anchor-checkbox-label disabled">
							<input type="checkbox" checked disabled>
							<span class="anchor-checkbox-text">
								<strong><?php esc_html_e( 'Public', 'anchor' ); ?></strong>
								<span class="anchor-checkbox-description"><?php esc_html_e( 'No restrictions, anyone can view', 'anchor' ); ?></span>
							</span>
						</label>

						<label class="anchor-checkbox-label">
							<input type="checkbox" name="enable_registered" id="enable_registered" value="1" <?php checked( $enable_registered, '1' ); ?>>
							<span class="anchor-checkbox-text">
								<strong><?php esc_html_e( 'Registered', 'anchor' ); ?></strong>
								<span class="anchor-checkbox-description"><?php esc_html_e( 'Free account required', 'anchor' ); ?></span>
							</span>
						</label>

						<label class="anchor-checkbox-label">
							<input type="checkbox" name="enable_subscriber" id="enable_subscriber" value="1" <?php checked( $enable_subscriber, '1' ); ?>>
							<span class="anchor-checkbox-text">
								<strong><?php esc_html_e( 'Subscriber', 'anchor' ); ?></strong>
								<span class="anchor-checkbox-description"><?php esc_html_e( 'Paid subscription required', 'anchor' ); ?></span>
							</span>
						</label>
					</div>
				</div>

				<div class="anchor-form-section">
					<h3 class="anchor-form-section-title"><?php esc_html_e( 'Default Content Access', 'anchor' ); ?></h3>
					<p class="anchor-section-description"><?php esc_html_e( 'What access level is required by default for all content?', 'anchor' ); ?></p>

					<div class="anchor-radio-group" id="default-access-options">
						<label class="anchor-radio-label">
							<input type="radio" name="default_access_level" value="public" <?php checked( $default_access_level, 'public' ); ?>>
							<span><?php esc_html_e( 'Public', 'anchor' ); ?></span>
						</label>

						<label class="anchor-radio-label" data-requires="registered">
							<input type="radio" name="default_access_level" value="registered" <?php checked( $default_access_level, 'registered' ); ?> <?php disabled( $enable_registered, '0' ); ?>>
							<span><?php esc_html_e( 'Registered', 'anchor' ); ?></span>
						</label>

						<label class="anchor-radio-label" data-requires="subscriber">
							<input type="radio" name="default_access_level" value="subscriber" <?php checked( $default_access_level, 'subscriber' ); ?> <?php disabled( $enable_subscriber, '0' ); ?>>
							<span><?php esc_html_e( 'Subscriber', 'anchor' ); ?></span>
						</label>
					</div>
				</div>

				<p class="submit">
					<input type="submit" name="anchor_save_content_access" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'anchor' ); ?>">
				</p>
			</div>

		<?php elseif ( $current_tab === 'metering' ) : ?>
			<!-- Tab 2: Metering -->
			<div class="anchor-box">
				<div class="anchor-form-section">
					<h3 class="anchor-form-section-title"><?php esc_html_e( 'Article Limits', 'anchor' ); ?></h3>

					<div class="anchor-checkbox-group">
						<label class="anchor-checkbox-label">
							<input type="checkbox" name="enable_metering" id="enable_metering" value="1" <?php checked( $enable_metering, '1' ); ?>>
							<span class="anchor-checkbox-text">
								<strong><?php esc_html_e( 'Limit free articles for visitors', 'anchor' ); ?></strong>
							</span>
						</label>
					</div>

					<div class="anchor-metering-settings" id="metering-settings" style="<?php echo $enable_metering !== '1' ? 'display: none;' : ''; ?>">
						<div class="anchor-subsection">
							<h4 class="anchor-subsection-title"><?php esc_html_e( 'Anonymous Visitors (Not Logged In)', 'anchor' ); ?></h4>

							<div class="anchor-inline-field">
								<label><?php esc_html_e( 'Free articles:', 'anchor' ); ?></label>
								<input type="number" name="meter_limit_anonymous" id="meter_limit_anonymous"
									value="<?php echo esc_attr( $meter_limit_anonymous ); ?>" min="1" max="100" class="small-text">
								<span><?php esc_html_e( 'articles per', 'anchor' ); ?></span>
								<select name="meter_period_anonymous" id="meter_period_anonymous">
									<option value="daily" <?php selected( $meter_period_anonymous, 'daily' ); ?>><?php esc_html_e( 'Day', 'anchor' ); ?></option>
									<option value="weekly" <?php selected( $meter_period_anonymous, 'weekly' ); ?>><?php esc_html_e( 'Week', 'anchor' ); ?></option>
									<option value="monthly" <?php selected( $meter_period_anonymous, 'monthly' ); ?>><?php esc_html_e( 'Month', 'anchor' ); ?></option>
								</select>
							</div>

							<div class="anchor-conditional-field" id="week-end-day-field" style="<?php echo $meter_period_anonymous !== 'weekly' ? 'display: none;' : ''; ?>">
								<label><?php esc_html_e( 'Week ends on:', 'anchor' ); ?></label>
								<select name="meter_week_end_day" id="meter_week_end_day">
									<?php foreach ( $days_of_week as $day_value => $day_label ) : ?>
										<option value="<?php echo esc_attr( $day_value ); ?>" <?php selected( $meter_week_end_day, $day_value ); ?>>
											<?php echo esc_html( $day_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="anchor-conditional-field" id="timezone-field" style="<?php echo $meter_period_anonymous !== 'daily' ? 'display: none;' : ''; ?>">
								<label><?php esc_html_e( 'Timezone:', 'anchor' ); ?></label>
								<select name="meter_timezone" id="meter_timezone">
									<?php foreach ( $timezones as $tz ) : ?>
										<option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $meter_timezone, $tz ); ?>>
											<?php echo esc_html( $tz ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="anchor-inline-field">
								<label><?php esc_html_e( 'After limit reached:', 'anchor' ); ?></label>
								<select name="meter_action_anonymous" id="meter_action_anonymous">
									<option value="require_registration" <?php selected( $meter_action_anonymous, 'require_registration' ); ?>>
										<?php esc_html_e( 'Require Registration', 'anchor' ); ?>
									</option>
									<option value="require_subscription" <?php selected( $meter_action_anonymous, 'require_subscription' ); ?>
											<?php disabled( $enable_subscriber, '0' ); ?> data-requires="subscriber">
										<?php esc_html_e( 'Require Subscription', 'anchor' ); ?>
									</option>
								</select>
							</div>
						</div>

						<div class="anchor-subsection" id="registered-metering-section" style="<?php echo ( $enable_registered !== '1' || $enable_subscriber !== '1' ) ? 'display: none;' : ''; ?>">
							<h4 class="anchor-subsection-title"><?php esc_html_e( 'Registered Members (Free Accounts)', 'anchor' ); ?></h4>

							<div class="anchor-checkbox-group">
								<label class="anchor-checkbox-label">
									<input type="checkbox" name="meter_limit_registered" id="meter_limit_registered" value="1" <?php checked( $meter_limit_registered, '1' ); ?>>
									<span class="anchor-checkbox-text">
										<strong><?php esc_html_e( 'Also limit registered members', 'anchor' ); ?></strong>
									</span>
								</label>
							</div>

							<div class="anchor-registered-metering-fields" id="registered-metering-fields" style="<?php echo $meter_limit_registered !== '1' ? 'display: none;' : ''; ?>">
								<div class="anchor-inline-field">
									<label><?php esc_html_e( 'Free articles:', 'anchor' ); ?></label>
									<input type="number" name="meter_limit_registered_count" id="meter_limit_registered_count"
										value="<?php echo esc_attr( $meter_limit_registered_count ); ?>" min="1" max="100" class="small-text">
									<span><?php esc_html_e( 'articles per', 'anchor' ); ?></span>
									<select name="meter_period_registered" id="meter_period_registered">
										<option value="daily" <?php selected( $meter_period_registered, 'daily' ); ?>><?php esc_html_e( 'Day', 'anchor' ); ?></option>
										<option value="weekly" <?php selected( $meter_period_registered, 'weekly' ); ?>><?php esc_html_e( 'Week', 'anchor' ); ?></option>
										<option value="monthly" <?php selected( $meter_period_registered, 'monthly' ); ?>><?php esc_html_e( 'Month', 'anchor' ); ?></option>
									</select>
								</div>
								<p class="anchor-field-note"><?php esc_html_e( 'After limit reached: Require Subscription', 'anchor' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<p class="submit">
					<input type="submit" name="anchor_save_content_access" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'anchor' ); ?>">
				</p>
			</div>

		<?php else : ?>
			<!-- Tab 3: Counting Rules -->
			<div class="anchor-box <?php echo $enable_metering !== '1' ? 'anchor-box-disabled' : ''; ?>">
				<?php if ( $enable_metering !== '1' ) : ?>
					<div class="anchor-disabled-notice">
						<p><?php esc_html_e( 'Enable metering in the "Metering" tab to configure counting rules.', 'anchor' ); ?></p>
					</div>
				<?php endif; ?>

				<fieldset <?php disabled( $enable_metering, '0' ); ?>>
					<div class="anchor-form-section">
						<h3 class="anchor-form-section-title"><?php esc_html_e( 'What Counts Toward Meter', 'anchor' ); ?></h3>
						<p class="anchor-section-description"><?php esc_html_e( 'Select which content types count toward article limits.', 'anchor' ); ?></p>

						<div class="anchor-checkbox-group anchor-checkbox-grid">
							<?php foreach ( $post_types as $post_type ) : ?>
								<label class="anchor-checkbox-label">
									<input type="checkbox" name="meter_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>"
										<?php checked( in_array( $post_type->name, $meter_post_types, true ) ); ?>>
									<span class="anchor-checkbox-text"><?php echo esc_html( $post_type->labels->name ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="anchor-form-section">
						<h3 class="anchor-form-section-title"><?php esc_html_e( 'Exemptions', 'anchor' ); ?></h3>
						<p class="anchor-section-description"><?php esc_html_e( "Articles matching these rules won't count toward meter limits.", 'anchor' ); ?></p>

						<div class="anchor-exemption-rules" id="exemption-rules">
							<?php if ( ! empty( $exemption_rules ) ) : ?>
								<?php foreach ( $exemption_rules as $index => $rule ) : ?>
									<div class="anchor-exemption-rule">
										<select name="exemption_taxonomy[]" class="exemption-taxonomy">
											<?php foreach ( $taxonomies as $taxonomy ) : ?>
												<option value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php selected( $rule['taxonomy'], $taxonomy->name ); ?>>
													<?php echo esc_html( $taxonomy->labels->singular_name ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<select name="exemption_operator[]" class="exemption-operator">
											<option value="is" <?php selected( $rule['operator'], 'is' ); ?>><?php esc_html_e( 'is', 'anchor' ); ?></option>
											<option value="is_not" <?php selected( $rule['operator'], 'is_not' ); ?>><?php esc_html_e( 'is not', 'anchor' ); ?></option>
										</select>
										<input type="text" name="exemption_term[]" value="<?php echo esc_attr( $rule['term'] ); ?>"
											placeholder="<?php esc_attr_e( 'Term name or slug', 'anchor' ); ?>" class="exemption-term">
										<button type="button" class="button anchor-remove-rule"><?php esc_html_e( 'Remove', 'anchor' ); ?></button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<div class="anchor-add-rule-wrapper">
							<button type="button" class="button" id="add-exemption-rule">
								<?php esc_html_e( '+ Add Exemption Rule', 'anchor' ); ?>
							</button>
						</div>

						<template id="exemption-rule-template">
							<div class="anchor-exemption-rule">
								<select name="exemption_taxonomy[]" class="exemption-taxonomy">
									<?php foreach ( $taxonomies as $taxonomy ) : ?>
										<option value="<?php echo esc_attr( $taxonomy->name ); ?>">
											<?php echo esc_html( $taxonomy->labels->singular_name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<select name="exemption_operator[]" class="exemption-operator">
									<option value="is"><?php esc_html_e( 'is', 'anchor' ); ?></option>
									<option value="is_not"><?php esc_html_e( 'is not', 'anchor' ); ?></option>
								</select>
								<input type="text" name="exemption_term[]" placeholder="<?php esc_attr_e( 'Term name or slug', 'anchor' ); ?>" class="exemption-term">
								<button type="button" class="button anchor-remove-rule"><?php esc_html_e( 'Remove', 'anchor' ); ?></button>
							</div>
						</template>
					</div>

					<div class="anchor-form-section">
						<h3 class="anchor-form-section-title"><?php esc_html_e( 'Time-Based Access', 'anchor' ); ?></h3>

						<div class="anchor-checkbox-group">
							<label class="anchor-checkbox-label">
								<input type="checkbox" name="enable_time_decay" id="enable_time_decay" value="1" <?php checked( $enable_time_decay, '1' ); ?>>
								<span class="anchor-checkbox-text">
									<strong><?php esc_html_e( 'Make old content public automatically', 'anchor' ); ?></strong>
								</span>
							</label>
						</div>

						<div class="anchor-time-decay-settings" id="time-decay-settings" style="<?php echo $enable_time_decay !== '1' ? 'display: none;' : ''; ?>">
							<div class="anchor-inline-field">
								<label><?php esc_html_e( 'Content older than', 'anchor' ); ?></label>
								<input type="number" name="time_decay_days" id="time_decay_days"
									value="<?php echo esc_attr( $time_decay_days ); ?>" min="1" max="365" class="small-text">
								<span><?php esc_html_e( 'days becomes public', 'anchor' ); ?></span>
							</div>
							<p class="anchor-field-note"><?php esc_html_e( "Overrides all access level settings. A 'Subscriber only' post becomes public after X days.", 'anchor' ); ?></p>
						</div>
					</div>

					<p class="submit">
						<input type="submit" name="anchor_save_content_access" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'anchor' ); ?>" <?php disabled( $enable_metering, '0' ); ?>>
					</p>
				</fieldset>
			</div>
		<?php endif; ?>
	</form>
</div>
