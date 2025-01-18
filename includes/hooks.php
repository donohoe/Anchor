<?php
/**
* Hooks documentation for Anchor plugin.
*
* This file documents all available actions and filters in the Anchor plugin.
* Developers can use these hooks to extend or modify plugin behavior.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* =============================================================================
* ACTIONS
* =============================================================================
*/

/**
* Fires when the plugin is fully loaded.
*
* @param \Anchor\Anchor $anchor Main plugin instance.
*/
// do_action( 'anchor_loaded', $anchor );

/**
* Fires when the plugin is activated.
*/
// do_action( 'anchor_activated' );

/**
* Fires when the plugin is deactivated.
*/
// do_action( 'anchor_deactivated' );

/**
* Fires when database tables are created.
*/
// do_action( 'anchor_tables_created' );

/**
* Fires when database tables are dropped (uninstall).
*/
// do_action( 'anchor_tables_dropped' );

/**
* =============================================================================
* REGISTRATION HOOKS
* =============================================================================
*/

/**
* Fires before a member registration is processed.
*
* @param array $data Registration data (username, email, password).
*/
// do_action( 'anchor_before_register', $data );

/**
* Fires after a member is successfully registered.
*
* @param int   $member_id The new member's ID.
* @param array $data      Registration data.
*/
// do_action( 'anchor_after_register', $member_id, $data );

/**
* Fires when registration fails.
*
* @param string $error_message Error message.
* @param array  $data          Registration data.
*/
// do_action( 'anchor_registration_failed', $error_message, $data );

/**
* Fires when a member's email is verified.
*
* @param int $member_id Member ID.
*/
// do_action( 'anchor_email_verified', $member_id );

/**
* =============================================================================
* LOGIN/LOGOUT HOOKS
* =============================================================================
*/

/**
* Fires before a login attempt is processed.
*
* @param string $username Username or email being attempted.
*/
// do_action( 'anchor_before_login_attempt', $username );

/**
* Fires after a successful login.
*
* @param int  $member_id Member ID.
* @param bool $remember  Whether "remember me" was checked.
*/
// do_action( 'anchor_login_success', $member_id, $remember );

/**
* Fires after a failed login attempt.
*
* @param string $username   Username attempted.
* @param string $error_code Error code (invalid_credentials, account_suspended, email_not_verified, login_prevented).
*/
// do_action( 'anchor_login_failed', $username, $error_code );

/**
* Fires when a member logs out.
*
* @param int $member_id Member ID.
*/
// do_action( 'anchor_logout', $member_id );

/**
* Fires when a member's session expires.
*
* @param int $member_id Member ID.
*/
// do_action( 'anchor_session_expired', $member_id );

/**
* =============================================================================
* PROFILE HOOKS
* =============================================================================
*/

/**
* Fires before a member profile is updated.
*
* @param int   $member_id Member ID.
* @param array $data      Update data.
*/
// do_action( 'anchor_before_profile_update', $member_id, $data );

/**
* Fires after a member profile is updated.
*
* @param int   $member_id Member ID.
* @param array $data      Update data.
*/
// do_action( 'anchor_after_profile_update', $member_id, $data );

/**
* Fires when a member's password is changed.
*
* @param int $member_id Member ID.
*/
// do_action( 'anchor_password_changed', $member_id );

/**
* Fires when a member account is deleted.
*
* @param int $member_id Member ID.
*/
// do_action( 'anchor_account_deleted', $member_id );

/**
* =============================================================================
* PASSWORD RESET HOOKS
* =============================================================================
*/

/**
* Fires when a password reset is requested.
*
* @param int    $member_id Member ID.
* @param string $email     Member email.
*/
// do_action( 'anchor_password_reset_requested', $member_id, $email );

/**
* Fires when a password is successfully reset.
*
* @param int $member_id Member ID.
*/
// do_action( 'anchor_password_reset_success', $member_id );

/**
* Fires when a password reset fails.
*
* @param string $email      Email attempted.
* @param string $error_code Error code.
*/
// do_action( 'anchor_password_reset_failed', $email, $error_code );

/**
* =============================================================================
* ROLE HOOKS
* =============================================================================
*/

/**
* Fires when a role is assigned to a member.
*
* @param int    $member_id Member ID.
* @param string $role_slug Role slug.
*/
// do_action( 'anchor_role_assigned', $member_id, $role_slug );

/**
* Fires when a role is removed from a member.
*
* @param int    $member_id Member ID.
* @param string $role_slug Role slug.
*/
// do_action( 'anchor_role_removed', $member_id, $role_slug );

/**
* =============================================================================
* TEMPLATE HOOKS
* =============================================================================
*/

/**
* Fires before a template is loaded.
*
* @param string $template_name Template name.
*/
// do_action( 'anchor_before_template', $template_name );

/**
* Fires after a template is loaded.
*
* @param string $template_name Template name.
*/
// do_action( 'anchor_after_template', $template_name );

/**
* =============================================================================
* EMAIL HOOKS
* =============================================================================
*/

/**
* Fires before an email is sent.
*
* @param string $to      Recipient email.
* @param string $subject Email subject.
* @param string $message Email message.
* @param string $type    Email type (verification, password_reset, welcome).
*/
// do_action( 'anchor_before_send_email', $to, $subject, $message, $type );

/**
* Fires after an email is sent.
*
* @param string $to      Recipient email.
* @param string $subject Email subject.
* @param bool   $success Whether the email was sent.
* @param string $type    Email type.
*/
// do_action( 'anchor_after_send_email', $to, $subject, $success, $type );

/**
* =============================================================================
* FILTERS
* =============================================================================
*/

/**
* Filter registration data before processing.
*
* @param array $data Registration data.
* @return array Modified data.
*/
// apply_filters( 'anchor_registration_data', $data );

/**
* Filter registration errors.
*
* @param \WP_Error $errors Existing errors.
* @param array     $data   Registration data.
* @return \WP_Error Modified errors.
*/
// apply_filters( 'anchor_registration_errors', $errors, $data );

/**
* Filter password requirements.
*
* @param array $rules Password rules.
* @return array Modified rules.
*/
// apply_filters( 'anchor_password_requirements', $rules );

/**
* Filter login redirect URL.
*
* @param string $redirect  Redirect URL.
* @param int    $member_id Member ID.
* @return string Modified URL.
*/
// apply_filters( 'anchor_login_redirect', $redirect, $member_id );

/**
* Filter logout redirect URL.
*
* @param string $redirect Redirect URL.
* @return string Modified URL.
*/
// apply_filters( 'anchor_logout_redirect', $redirect );

/**
* Filter whether a member can log in.
*
* @param bool   $can_login Whether login is allowed.
* @param int    $member_id Member ID.
* @param object $member    Member object.
* @return bool Modified value.
*/
// apply_filters( 'anchor_can_login', $can_login, $member_id, $member );

/**
* Filter profile update data.
*
* @param array $data      Update data.
* @param int   $member_id Member ID.
* @return array Modified data.
*/
// apply_filters( 'anchor_profile_update_data', $data, $member_id );

/**
* Filter whether a member can update their profile.
*
* @param bool $can_update Whether update is allowed.
* @param int  $member_id  Member ID.
* @return bool Modified value.
*/
// apply_filters( 'anchor_can_update_profile', $can_update, $member_id );

/**
* Filter available roles list.
*
* @param array $roles Role objects.
* @return array Modified roles.
*/
// apply_filters( 'anchor_available_roles', $roles );

/**
* Filter member capabilities.
*
* @param array $capabilities Member capabilities.
* @param int   $member_id    Member ID.
* @return array Modified capabilities.
*/
// apply_filters( 'anchor_get_capabilities', $capabilities, $member_id );

/**
* Filter capability check result.
*
* @param bool   $can        Whether member has capability.
* @param int    $member_id  Member ID.
* @param string $capability Capability being checked.
* @return bool Modified value.
*/
// apply_filters( 'anchor_user_can', $can, $member_id, $capability );

/**
* Filter template path.
*
* @param string $template      Template path.
* @param string $template_name Template name.
* @return string Modified path.
*/
// apply_filters( 'anchor_template_path', $template, $template_name );

/**
* Filter setting value.
*
* @param mixed  $value Setting value.
* @param string $key   Setting key.
* @return mixed Modified value.
*/
// apply_filters( 'anchor_get_setting', $value, $key );

/**
* Filter specific setting value.
*
* @param mixed $value Setting value.
* @return mixed Modified value.
*/
// apply_filters( 'anchor_get_setting_{$key}', $value );

/**
* Filter default settings.
*
* @param array $settings Default settings.
* @return array Modified settings.
*/
// apply_filters( 'anchor_default_settings', $settings );

/**
* Filter email subject.
*
* @param string $subject   Email subject.
* @param string $type      Email type.
* @param int    $member_id Member ID.
* @return string Modified subject.
*/
// apply_filters( 'anchor_email_subject', $subject, $type, $member_id );

/**
* Filter email message.
*
* @param string $message   Email message.
* @param string $type      Email type.
* @param int    $member_id Member ID.
* @return string Modified message.
*/
// apply_filters( 'anchor_email_message', $message, $type, $member_id );

/**
* Filter email headers.
*
* @param array  $headers Email headers.
* @param string $type    Email type.
* @return array Modified headers.
*/
// apply_filters( 'anchor_email_headers', $headers, $type );

/**
* Filter from email address.
*
* @param string $email From email.
* @return string Modified email.
*/
// apply_filters( 'anchor_email_from', $email );

/**
* Filter from name.
*
* @param string $name From name.
* @return string Modified name.
*/
// apply_filters( 'anchor_email_from_name', $name );
