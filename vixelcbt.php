<?php
/**
 * Plugin Name: VixelCBT
 * Plugin URI: https://vixelcbt.com
 * Description: Sistem CBT (Computer Based Test) lengkap ala Indonesia dengan fitur admin panel, front-end shortcode, keamanan, dan dokumentasi.
 * Version: 1.0.0
 * Author: VixelCBT Team
 * Author URI: https://vixelcbt.com
 * License: GPLv3
 * Text Domain: vixelcbt
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('VIXELCBT_VERSION', '1.0.0');
define('VIXELCBT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIXELCBT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VIXELCBT_TEXT_DOMAIN', 'vixelcbt');

/**
 * Main VixelCBT Class
 */
class VixelCBT {
    
    /**
     * Single instance of the class
     */
    private static $_instance = null;
    
    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        $this->includes();
        $this->init_classes();
        $this->add_user_roles();
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(VIXELCBT_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once VIXELCBT_PLUGIN_PATH . 'includes/class-database.php';
        require_once VIXELCBT_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once VIXELCBT_PLUGIN_PATH . 'includes/class-rest.php';
        require_once VIXELCBT_PLUGIN_PATH . 'includes/class-smtp.php';
        require_once VIXELCBT_PLUGIN_PATH . 'includes/class-pdf.php';
        require_once VIXELCBT_PLUGIN_PATH . 'includes/class-export.php';
        require_once VIXELCBT_PLUGIN_PATH . 'includes/class-auth.php';
        require_once VIXELCBT_PLUGIN_PATH . 'includes/helpers.php';
        require_once VIXELCBT_PLUGIN_PATH . 'includes/roles.php';
        
        // Admin
        if (is_admin()) {
            require_once VIXELCBT_PLUGIN_PATH . 'admin/class-admin.php';
        }
        
        // Public
        if (!is_admin() || wp_doing_ajax()) {
            require_once VIXELCBT_PLUGIN_PATH . 'public/class-public.php';
            require_once VIXELCBT_PLUGIN_PATH . 'public/shortcodes/register.php';
        }
    }
    
    /**
     * Initialize classes
     */
    private function init_classes() {
        VixelCBT_Database::instance();
        VixelCBT_Ajax::instance();
        VixelCBT_REST::instance();
        
        if (is_admin()) {
            VixelCBT_Admin::instance();
        }
        
        if (!is_admin() || wp_doing_ajax()) {
            VixelCBT_Public::instance();
            VixelCBT_Shortcodes::instance();
        }
    }
    
    /**
     * Add custom user roles
     */
    private function add_user_roles() {
        vixelcbt_add_user_roles();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        VixelCBT_Database::create_tables();
        
        // Add user roles
        vixelcbt_add_user_roles();
        
        // Add default settings
        $this->add_default_settings();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Add default settings
     */
    private function add_default_settings() {
        $defaults = array(
            'vixelcbt_kkm_default' => 70,
            'vixelcbt_attempts_default' => 1,
            'vixelcbt_timer_default' => 90,
            'vixelcbt_grace_period' => 5,
            'vixelcbt_max_upload' => '5MB',
            'vixelcbt_fullscreen_lock' => 1,
            'vixelcbt_anti_copy' => 1,
            'vixelcbt_blur_threshold' => 3,
            'vixelcbt_show_results' => 0,
            'vixelcbt_pengumuman_open' => 0,
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

/**
 * Initialize plugin
 */
function vixelcbt() {
    return VixelCBT::instance();
}

// Start the plugin
vixelcbt();