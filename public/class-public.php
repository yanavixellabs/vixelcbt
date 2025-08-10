<?php
/**
 * Public Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_Public {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize public functionality
     */
    public function init() {
        // Handle login redirect
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
        
        // Handle logout redirect
        add_action('wp_logout', array($this, 'logout_redirect'));
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'vixelcbt-public-style',
            VIXELCBT_PLUGIN_URL . 'public/css/public-style.css',
            array(),
            VIXELCBT_VERSION
        );
        
        wp_enqueue_script(
            'vixelcbt-public-script',
            VIXELCBT_PLUGIN_URL . 'public/js/public-script.js',
            array('jquery'),
            VIXELCBT_VERSION,
            true
        );
        
        wp_localize_script('vixelcbt-public-script', 'vixelcbt_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vixelcbt_public_nonce'),
            'messages' => array(
                'loading' => __('Loading...', 'vixelcbt'),
                'error' => __('An error occurred. Please try again.', 'vixelcbt'),
                'success' => __('Success!', 'vixelcbt'),
            ),
        ));
    }
    
    /**
     * Handle login redirect
     */
    public function login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('subscriber', $user->roles)) {
                return home_url('/dashboard');
            }
        }
        return $redirect_to;
    }
    
    /**
     * Handle logout redirect
     */
    public function logout_redirect() {
        wp_redirect(home_url('/login'));
        exit();
    }
}