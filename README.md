# Anchor

You publish great content, and your readers value it. But you're reaching them through platforms you don't control—social media feeds, search algorithms, email services. **Anchor** changes that.

Anchor is a membership plugin for news and content publishers. Decide which stories are free, which require registration, and which need a paid subscription. Set article limits to encourage readers to create accounts, like *"Read 5 free articles, then register."* Own your audience data and build a direct relationship with people who value your work, no middleman platforms required.

Anchor is designed to start simple and scale with your business—from basic registration walls to full subscription management.

## Key Features

- **Flexible Content Access** - Set different access levels for your content: public, registered members only, or paid subscribers only

- **Smart Metering** - Offer free article limits (daily, weekly, or monthly) that encourage visitors to register or subscribe

- **First-Party Data** - Build your own audience database with complete ownership—no dependency on external platforms

- **Member Management** - Simple dashboard to view, manage, and understand your registered members and subscribers

- **Built for Growth** - Start with a basic registration wall and expand to paid subscriptions when you're ready—all without switching platforms

- **Developer-Friendly** - Extensible architecture with hooks, filters, and a clean API—customize every aspect or integrate with your existing tools and workflows

## Overview

Anchor creates a standalone membership system with its own database tables, authentication flow, roles, and frontend pages. Members exist entirely separate from WordPress users and have no access to wp-admin.

**Key Features:**
- Custom database tables (`ap_members`, `ap_member_meta`, etc.)
- Token-based session authentication
- Email verification and password reset flows
- Role and capability system
- Client-side content metering (CDN-compatible)
- Theme-overridable templates

## Background

Built for news publishers and content sites that need:
- Member accounts separate from WordPress admin users
- Scalable architecture (optimized for 50,000+ members)
- Flexible content access controls with metering
- CDN-friendly client-side access evaluation

## Installation

1. Upload the `anchor` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Visit **Anchor > Settings** to configure

The plugin automatically creates all required database tables on activation.

## Configuration

### Settings (Anchor > Settings)
- **Registration**: Enable/disable, default role, email verification
- **Security**: Password requirements, login lockout settings
- **Redirects**: Post-login and post-logout URLs

### Content Access (Anchor > Content Access)
- **Access Levels**: Enable Public, Registered, and/or Subscriber tiers
- **Metering**: Limit free articles per period for anonymous/registered users
- **Counting Rules**: Which post types count, exemptions, time decay

## Usage

### Frontend URLs
| URL | Purpose |
|-----|---------|
| `/account/register` | Registration |
| `/account/sign-in` | Login |
| `/account/forgot` | Password reset request |
| `/account/` | Member dashboard |
| `/account/settings` | Account settings |

### Template Overrides
Copy templates from `anchor/templates/` to your theme's `anchor/` directory to customize.

### Helper Functions
```php
// Check if member is logged in
if ( anchor_is_member_logged_in() ) {
    $member = anchor_get_current_member();
}

// Get/set post access level
$level = anchor_get_post_access_level( $post_id ); // 'public', 'registered', 'subscriber'
anchor_set_post_access_level( $post_id, 'subscriber' );

// Check capabilities
if ( anchor_member_can( $member_id, 'subscriber_access' ) ) {
    // Premium content
}
```

## Action & Filter Hooks

Anchor provides hooks for extending functionality.

### Example Actions
```php
// After successful registration
add_action( 'anchor_after_register', function( $member_id, $data ) {
    // Send to CRM, trigger welcome sequence, etc.
}, 10, 2 );

// After successful login
add_action( 'anchor_login_success', function( $member_id, $remember ) {
    // Log activity, update stats, etc.
}, 10, 2 );
```

### Example Filters
```php
// Customize login redirect
add_filter( 'anchor_login_redirect', function( $redirect, $member_id ) {
    return '/welcome/';
}, 10, 2 );

// Add custom password validation
add_filter( 'anchor_registration_errors', function( $errors, $data ) {
    if ( strlen( $data['password'] ) < 12 ) {
        $errors->add( 'weak_password', 'Password must be 12+ characters.' );
    }
    return $errors;
}, 10, 2 );
```

For the complete list of available hooks, see [includes/hooks.php](includes/hooks.php).

## License

GPL v2 or later
