<?php
/**
 * Plugin Name: Anchor
 * Plugin URI: https://donohoe.dev/wordpress/anchor
 * Description: A separate member authentication and management system for WordPress, completely isolated from wp_users.
 * Version: 1.0.0
 * Author: Michael Donohoe
 * Author URI: https://donohoe.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: anchor
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

namespace Anchor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'ANCHOR_VERSION', '1.0.0' );
define( 'ANCHOR_PLUGIN_FILE', __FILE__ );
define( 'ANCHOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANCHOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ANCHOR_TABLE_PREFIX', 'ap_' );

/**
 * Autoloader for Anchor classes.
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register( function( $class ) {
    // Only autoload Anchor namespace classes
    if ( strpos( $class, 'Anchor\\' ) !== 0 ) {
        return;
    }

    // Remove namespace prefix
    $relative_class = substr( $class, strlen( 'Anchor\\' ) );

    // Convert namespace separators to directory separators
    $relative_class = str_replace( '\\', '/', $relative_class );

    // Convert to lowercase and add class- prefix for WordPress style
    $file_name = 'class-' . strtolower( str_replace( '_', '-', basename( $relative_class ) ) ) . '.php';

    // Check for Admin namespace
    if ( strpos( $relative_class, 'Admin/' ) === 0 ) {
        $file = ANCHOR_PLUGIN_DIR . 'admin/' . $file_name;
    } else {
        $file = ANCHOR_PLUGIN_DIR . 'includes/' . $file_name;
    }

    // Load the file if it exists
    if ( file_exists( $file ) ) {
        require_once $file;
    }
});

/**
 * Main plugin class.
 */
final class Anchor {

    /**
     * Plugin instance.
     *
     * @var Anchor
     */
    private static $instance = null;

    /**
     * Database instance.
     *
     * @var Database
     */
    public $database;

    /**
     * Settings instance.
     *
     * @var Settings
     */
    public $settings;

    /**
     * Member instance.
     *
     * @var Member
     */
    public $member;

    /**
     * Role instance.
     *
     * @var Role
     */
    public $role;

    /**
     * Session instance.
     *
     * @var Session
     */
    public $session;

    /**
     * Token instance.
     *
     * @var Token
     */
    public $token;

    /**
     * Auth instance.
     *
     * @var Auth
     */
    public $auth;

    /**
     * Email instance.
     *
     * @var Email
     */
    public $email;

    /**
     * Router instance.
     *
     * @var Router
     */
    public $router;

    /**
     * Content Access instance.
     *
     * @var Content_Access
     */
    public $content_access;

    /**
     * Admin instance.
     *
     * @var Admin\Admin
     */
    public $admin;

    /**
     * Get the singleton instance.
     *
     * @return Anchor
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files.
     */
    private function load_dependencies() {
        // Load functions file
        require_once ANCHOR_PLUGIN_DIR . 'includes/functions.php';

        // Load hooks definitions
        require_once ANCHOR_PLUGIN_DIR . 'includes/hooks.php';
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Activation/deactivation hooks
        register_activation_hook( ANCHOR_PLUGIN_FILE, [ $this, 'activate' ] );
        register_deactivation_hook( ANCHOR_PLUGIN_FILE, [ $this, 'deactivate' ] );

        // Initialize plugin on plugins_loaded
        add_action( 'plugins_loaded', [ $this, 'init' ], 0 );
    }

    /**
     * Initialize the plugin.
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain( 'anchor', false, dirname( plugin_basename( ANCHOR_PLUGIN_FILE ) ) . '/languages' );

        // Initialize components
        $this->database = new Database();
        $this->settings = new Settings();
        $this->role     = new Role();
        $this->member   = new Member();
        $this->session  = new Session();
        $this->token    = new Token();
        $this->email    = new Email();
        $this->auth     = new Auth();
        $this->router   = new Router();

        // Initialize content access (frontend only)
        if ( ! is_admin() ) {
            $this->content_access = new Content_Access();
        }

        // Initialize admin
        if ( is_admin() ) {
            $this->admin = new Admin\Admin();
        }

        // Fire action after plugin is fully loaded
        do_action( 'anchor_loaded', $this );
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Create database tables
        $database = new Database();
        $database->create_tables();

        // Insert default roles
        $role = new Role();
        $role->create_default_roles();

        // Insert default settings
        $settings = new Settings();
        $settings->create_default_settings();

        // Flush rewrite rules
        $router = new Router();
        $router->register_rewrites();
        flush_rewrite_rules();

        // Set activation flag
        update_option( 'anchor_activated', true );
        update_option( 'anchor_version', ANCHOR_VERSION );

        do_action( 'anchor_activated' );
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        do_action( 'anchor_deactivated' );
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserializing.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }
}

/**
 * Returns the main plugin instance.
 *
 * @return Anchor
 */
function anchor() {
    return Anchor::instance();
}

// Initialize the plugin
anchor();
