<?php
/**
* Helper functions for Anchor plugin.
*
* Public API functions for theme and plugin developers.
*
* @package Anchor
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Check if a member is logged in.
*
* @return bool True if logged in.
*/
function anchor_is_member_logged_in() {
	$session = new \Anchor\Session();
	return $session->is_logged_in();
}

/**
* Get the current member ID.
*
* @return int|null Member ID or null.
*/
function anchor_get_current_member_id() {
	$session = new \Anchor\Session();
	return $session->get_current_member_id();
}

/**
* Get the current member object.
*
* @return object|null Member object or null.
*/
function anchor_get_current_member() {
	$session = new \Anchor\Session();
	return $session->get_current_member();
}

/**
* Get a member by ID.
*
* @param int $member_id Member ID.
* @return object|null Member object or null.
*/
function anchor_get_member( $member_id ) {
	$member = new \Anchor\Member();
	return $member->get( $member_id );
}

/**
* Get a member by field.
*
* @param string $field Field name (email, username, ID).
* @param mixed  $value Field value.
* @return object|null Member object or null.
*/
function anchor_get_member_by( $field, $value ) {
	$member = new \Anchor\Member();
	return $member->get_by( $field, $value );
}

/**
* Check if a member exists.
*
* @param string $value Value to check.
* @param string $field Field to check (username or email).
* @return bool True if exists.
*/
function anchor_member_exists( $value, $field = 'email' ) {
	$member = new \Anchor\Member();
	return $member->exists( $value, $field );
}

/**
* Get member meta.
*
* @param int    $member_id Member ID.
* @param string $key       Meta key.
* @param bool   $single    Return single value or array.
* @return mixed Meta value(s).
*/
function anchor_get_member_meta( $member_id, $key = '', $single = true ) {
	$member = new \Anchor\Member();
	return $member->get_meta( $member_id, $key, $single );
}

/**
* Update member meta.
*
* @param int    $member_id Member ID.
* @param string $key       Meta key.
* @param mixed  $value     Meta value.
* @return bool True on success.
*/
function anchor_update_member_meta( $member_id, $key, $value ) {
	$member = new \Anchor\Member();
	return $member->update_meta( $member_id, $key, $value );
}

/**
* Delete member meta.
*
* @param int    $member_id Member ID.
* @param string $key       Meta key.
* @return bool True on success.
*/
function anchor_delete_member_meta( $member_id, $key ) {
	$member = new \Anchor\Member();
	return $member->delete_meta( $member_id, $key );
}

/**
* Check if member has a capability.
*
* @param int    $member_id  Member ID (or null for current member).
* @param string $capability Capability to check.
* @return bool True if has capability.
*/
function anchor_member_can( $member_id, $capability ) {
	if ( is_null( $member_id ) ) {
		$member_id = anchor_get_current_member_id();
	}

	if ( ! $member_id ) {
		return false;
	}

	$role = new \Anchor\Role();
	return $role->member_can( $member_id, $capability );
}

/**
* Check if member has a role.
*
* @param int    $member_id Member ID.
* @param string $role_slug Role slug.
* @return bool True if has role.
*/
function anchor_member_has_role( $member_id, $role_slug ) {
	$role = new \Anchor\Role();
	return $role->member_has_role( $member_id, $role_slug );
}

/**
* Get roles for a member.
*
* @param int $member_id Member ID.
* @return array Array of role objects.
*/
function anchor_get_member_roles( $member_id ) {
	$role = new \Anchor\Role();
	return $role->get_member_roles( $member_id );
}

/**
* Assign a role to a member.
*
* @param int    $member_id Member ID.
* @param string $role_slug Role slug.
* @return bool|\WP_Error True on success, WP_Error on failure.
*/
function anchor_assign_member_role( $member_id, $role_slug ) {
	$role = new \Anchor\Role();
	return $role->assign_to_member( $member_id, $role_slug );
}

/**
* Remove a role from a member.
*
* @param int    $member_id Member ID.
* @param string $role_slug Role slug.
* @return bool True on success.
*/
function anchor_remove_member_role( $member_id, $role_slug ) {
	$role = new \Anchor\Role();
	return $role->remove_from_member( $member_id, $role_slug );
}

/**
* Get a role by slug.
*
* @param string $slug Role slug.
* @return object|null Role object or null.
*/
function anchor_get_role( $slug ) {
	$role = new \Anchor\Role();
	return $role->get_by_slug( $slug );
}

/**
* Get all roles.
*
* @return array Array of role objects.
*/
function anchor_get_all_roles() {
	$role = new \Anchor\Role();
	return $role->get_all();
}

/**
* Get a setting value.
*
* @param string $key     Setting key.
* @param mixed  $default Default value.
* @return mixed Setting value.
*/
function anchor_get_setting( $key, $default = null ) {
	$settings = new \Anchor\Settings();
	return $settings->get( $key, $default );
}

/**
* Update a setting value.
*
* @param string $key   Setting key.
* @param mixed  $value Setting value.
* @return bool True on success.
*/
function anchor_update_setting( $key, $value ) {
	$settings = new \Anchor\Settings();
	return $settings->update( $key, $value );
}

/**
* Get the login URL.
*
* @param string $redirect_to URL to redirect to after login.
* @return string Login URL.
*/
function anchor_get_login_url( $redirect_to = '' ) {
	return \Anchor\Router::get_login_url( $redirect_to );
}

/**
* Get the registration URL.
*
* @return string Registration URL.
*/
function anchor_get_register_url() {
	return \Anchor\Router::get_register_url();
}

/**
* Get the forgot password URL.
*
* @return string Forgot password URL.
*/
function anchor_get_forgot_url() {
	return \Anchor\Router::get_forgot_url();
}

/**
* Get the profile URL.
*
* @return string Profile URL.
*/
function anchor_get_profile_url() {
	return \Anchor\Router::get_profile_url();
}

/**
* Get the settings URL.
*
* @return string Settings URL.
*/
function anchor_get_settings_url() {
	return \Anchor\Router::get_settings_url();
}

/**
* Get the logout URL.
*
* @param string $redirect_to URL to redirect to after logout.
* @return string Logout URL.
*/
function anchor_get_logout_url( $redirect_to = '' ) {
	$auth = new \Anchor\Auth();
	return $auth->get_logout_url( $redirect_to );
}

/**
* Get a member's public profile URL.
*
* @param int|string $member_id_or_username Member ID or username.
* @return string Profile URL.
*/
function anchor_get_public_profile_url( $member_id_or_username ) {
	return \Anchor\Router::get_public_profile_url( $member_id_or_username );
}

/**
* Get member avatar URL.
*
* @param int $member_id Member ID.
* @param int $size      Avatar size in pixels.
* @return string Avatar URL.
*/
function anchor_get_member_avatar_url( $member_id, $size = 96 ) {
	$member = anchor_get_member( $member_id );

	if ( ! $member ) {
		return '';
	}

	// Check for custom avatar in meta
	$custom_avatar = anchor_get_member_meta( $member_id, 'avatar_url', true );

	if ( $custom_avatar ) {
		return $custom_avatar;
	}

	// Fallback (Gravatar)
	$hash = md5( strtolower( trim( $member->email ) ) );
	return sprintf( 'https://www.gravatar.com/avatar/%s?s=%d&d=mp', $hash, $size );
}

/**
* Format a date for display.
*
* @param string $date   Date string.
* @param string $format Date format (default: WordPress date format).
* @return string Formatted date.
*/
function anchor_format_date( $date, $format = '' ) {
	if ( empty( $format ) ) {
		$format = get_option( 'date_format' );
	}

	$timestamp = strtotime( $date );

	if ( ! $timestamp ) {
		return '';
	}

	return date_i18n( $format, $timestamp );
}

/**
* Load a template part.
*
* @param string $template_name Template name.
* @param array  $args          Arguments to pass to template.
*/
function anchor_get_template_part( $template_name, $args = [] ) {
	$router   = new \Anchor\Router();
	$template = $router->get_template( $template_name );

	if ( $template ) {
		if ( ! empty( $args ) ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		include $template;
	}
}

/**
* Display login/logout link based on member status.
*
* @param array $args Optional arguments.
*/
function anchor_login_link( $args = [] ) {
	$defaults = [
		'login_text'  => __( 'Sign In', 'anchor' ),
		'logout_text' => __( 'Sign Out', 'anchor' ),
		'class'       => 'anchor-auth-link',
	];

	$args = wp_parse_args( $args, $defaults );

	if ( anchor_is_member_logged_in() ) {
		$url  = anchor_get_logout_url();
		$text = $args['logout_text'];
	} else {
		$url  = anchor_get_login_url();
		$text = $args['login_text'];
	}

	printf(
		'<a href="%s" class="%s">%s</a>',
		esc_url( $url ),
		esc_attr( $args['class'] ),
		esc_html( $text )
	);
}

/**
* Get the account URL.
*
* @return string Account URL.
*/
function anchor_get_account_url() {
	return \Anchor\Router::get_account_url();
}

/**
* Get the newsletters URL.
*
* @return string Newsletters URL.
*/
function anchor_get_newsletters_url() {
	return \Anchor\Router::get_newsletters_url();
}

/**
* Get the appearance URL.
*
* @return string Appearance URL.
*/
function anchor_get_appearance_url() {
	return \Anchor\Router::get_appearance_url();
}

/**
* Get the access level required for a post.
*
* @param int|\WP_Post|null $post Post ID or object.
* @return string Access level: 'public', 'registered', or 'subscriber'.
*/
function anchor_get_post_access_level( $post = null ) {
	$content_access = new \Anchor\Content_Access();
	return $content_access->get_post_access_level( $post );
}

/**
* Set the access level for a post.
*
* @param int    $post_id      Post ID.
* @param string $access_level Access level: 'public', 'registered', or 'subscriber'.
* @return bool True on success.
*/
function anchor_set_post_access_level( $post_id, $access_level ) {
	if ( ! in_array( $access_level, [ 'public', 'registered', 'subscriber' ], true ) ) {
		return false;
	}
	return update_post_meta( $post_id, '_anchor_access_level', $access_level );
}

/**
* Check if a post is exempt from metering.
*
* @param int|\WP_Post|null $post Post ID or object.
* @return bool True if exempt.
*/
function anchor_is_post_meter_exempt( $post = null ) {
	$content_access = new \Anchor\Content_Access();
	return ! $content_access->post_counts_toward_meter( $post );
}

/**
* Set whether a post is exempt from metering.
*
* @param int  $post_id Post ID.
* @param bool $exempt  Whether exempt.
* @return bool True on success.
*/
function anchor_set_post_meter_exempt( $post_id, $exempt = true ) {
	return update_post_meta( $post_id, '_anchor_meter_exempt', $exempt ? '1' : '0' );
}

/**
* Check if metering is enabled.
*
* @return bool True if metering is enabled.
*/
function anchor_is_metering_enabled() {
	$content_access = new \Anchor\Content_Access();
	return $content_access->is_metering_enabled();
}
