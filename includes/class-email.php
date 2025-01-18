<?php
/**
 * Email class for Anchor plugin.
 *
 * Handles sending member-related emails.
 *
 * @package Anchor
 */

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Email {

    /**
     * Get the from email address.
     *
     * @return string From email.
     */
    private function get_from_email() {
        $settings   = new Settings();
        $from_email = $settings->get( 'from_email' );

        if ( empty( $from_email ) ) {
            $from_email = get_option( 'admin_email' );
        }

        return apply_filters( 'anchor_email_from', $from_email );
    }

    /**
     * Get the from name.
     *
     * @return string From name.
     */
    private function get_from_name() {
        $settings  = new Settings();
        $from_name = $settings->get( 'from_name' );

        if ( empty( $from_name ) ) {
            $from_name = get_option( 'blogname' );
        }

        return apply_filters( 'anchor_email_from_name', $from_name );
    }

    /**
     * Get email headers.
     *
     * @param string $type Email type.
     * @return array Email headers.
     */
    private function get_headers( $type = '' ) {
        $from_email = $this->get_from_email();
        $from_name  = $this->get_from_name();

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $from_name, $from_email ),
        ];

        return apply_filters( 'anchor_email_headers', $headers, $type );
    }

    /**
     * Send an email.
     *
	 * TODO: Consider MailGun, Twilio, etc. service support
	 *
     * @param string $to      Recipient email.
     * @param string $subject Email subject.
     * @param string $message Email message (HTML).
     * @param string $type    Email type for hooks.
     * @return bool True if sent successfully.
     */
    private function send( $to, $subject, $message, $type = '' ) {
        $headers = $this->get_headers( $type );

        do_action( 'anchor_before_send_email', $to, $subject, $message, $type );

        // Wrap in basic HTML structure
        $html_message = $this->wrap_html( $message, $subject );

        $sent = wp_mail( $to, $subject, $html_message, $headers );

        do_action( 'anchor_after_send_email', $to, $subject, $sent, $type );

        return $sent;
    }

    /**
     * Wrap message content in HTML structure.
     *
     * @param string $content Email content.
     * @param string $title   Email title.
     * @return string HTML email.
     */
    private function wrap_html( $content, $title = '' ) {
        $site_name = get_option( 'blogname' );

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html( $title ) . '</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #333;
            color: #fff !important;
            padding: 12px 24px;
            text-decoration: none;
            margin: 20px 0;
        }
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <strong>' . esc_html( $site_name ) . '</strong>
    </div>
    <div class="content">
        ' . $content . '
    </div>
    <div class="footer">
        <p>' . esc_html( $site_name ) . '</p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Send verification email.
     *
     * @param int    $member_id Member ID.
     * @param string $token     Verification token.
     * @return bool True if sent successfully.
     */
    public function send_verification( $member_id, $token ) {
        $member_handler = new Member();
        $member         = $member_handler->get( $member_id );

        if ( ! $member ) {
            return false;
        }

        $verify_url = add_query_arg( 'token', $token, home_url( '/account/verify' ) );
        $site_name  = get_option( 'blogname' );

        $subject = sprintf(
            __( 'Verify your email address - %s', 'anchor' ),
            $site_name
        );

        $message = sprintf(
            '<p>' . __( 'Hello %s,', 'anchor' ) . '</p>',
            esc_html( $member->display_name )
        );

        $message .= '<p>' . __( 'Thank you for registering. Please click the button below to verify your email address:', 'anchor' ) . '</p>';

        $message .= '<p><a href="' . esc_url( $verify_url ) . '" class="button">' . __( 'Verify Email Address', 'anchor' ) . '</a></p>';

        $message .= '<p>' . __( 'Or copy and paste this link into your browser:', 'anchor' ) . '</p>';
        $message .= '<p><a href="' . esc_url( $verify_url ) . '">' . esc_html( $verify_url ) . '</a></p>';

        $message .= '<p>' . __( 'This link will expire in 24 hours.', 'anchor' ) . '</p>';

        $message .= '<p>' . __( 'If you did not create an account, you can ignore this email.', 'anchor' ) . '</p>';

        $subject = apply_filters( 'anchor_email_subject', $subject, 'verification', $member_id );
        $message = apply_filters( 'anchor_email_message', $message, 'verification', $member_id );

        return $this->send( $member->email, $subject, $message, 'verification' );
    }

    /**
     * Send password reset email.
     *
     * @param int    $member_id Member ID.
     * @param string $token     Reset token.
     * @return bool True if sent successfully.
     */
    public function send_password_reset( $member_id, $token ) {
        $member_handler = new Member();
        $member         = $member_handler->get( $member_id );

        if ( ! $member ) {
            return false;
        }

        $reset_url = add_query_arg( 'token', $token, home_url( '/account/reset-password' ) );
        $site_name = get_option( 'blogname' );

        $subject = sprintf(
            __( 'Reset your password - %s', 'anchor' ),
            $site_name
        );

        $message = sprintf(
            '<p>' . __( 'Hello %s,', 'anchor' ) . '</p>',
            esc_html( $member->display_name )
        );

        $message .= '<p>' . __( 'We received a request to reset your password. Click the button below to choose a new password:', 'anchor' ) . '</p>';

        $message .= '<p><a href="' . esc_url( $reset_url ) . '" class="button">' . __( 'Reset Password', 'anchor' ) . '</a></p>';

        $message .= '<p>' . __( 'Or copy and paste this link into your browser:', 'anchor' ) . '</p>';
        $message .= '<p><a href="' . esc_url( $reset_url ) . '">' . esc_html( $reset_url ) . '</a></p>';

        $message .= '<p>' . __( 'This link will expire in 1 hour.', 'anchor' ) . '</p>';

        $message .= '<p>' . __( 'If you did not request a password reset, you can ignore this email. Your password will remain unchanged.', 'anchor' ) . '</p>';

        $subject = apply_filters( 'anchor_email_subject', $subject, 'password_reset', $member_id );
        $message = apply_filters( 'anchor_email_message', $message, 'password_reset', $member_id );

        return $this->send( $member->email, $subject, $message, 'password_reset' );
    }

    /**
     * Send welcome email.
     *
     * @param int $member_id Member ID.
     * @return bool True if sent successfully.
     */
    public function send_welcome( $member_id ) {
        $member_handler = new Member();
        $member         = $member_handler->get( $member_id );

        if ( ! $member ) {
            return false;
        }

        $login_url = home_url( '/account/sign-in' );
        $site_name = get_option( 'blogname' );

        $subject = sprintf(
            __( 'Welcome to %s', 'anchor' ),
            $site_name
        );

        $message = sprintf(
            '<p>' . __( 'Hello %s,', 'anchor' ) . '</p>',
            esc_html( $member->display_name )
        );

        $message .= sprintf(
            '<p>' . __( 'Welcome to %s! Your account has been created successfully.', 'anchor' ) . '</p>',
            esc_html( $site_name )
        );

        $message .= '<p>' . __( 'You can now sign in to your account:', 'anchor' ) . '</p>';

        $message .= '<p><a href="' . esc_url( $login_url ) . '" class="button">' . __( 'Sign In', 'anchor' ) . '</a></p>';

        $subject = apply_filters( 'anchor_email_subject', $subject, 'welcome', $member_id );
        $message = apply_filters( 'anchor_email_message', $message, 'welcome', $member_id );

        return $this->send( $member->email, $subject, $message, 'welcome' );
    }
}
