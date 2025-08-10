<?php
/**
 * Admin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_Admin {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('VixelCBT', 'vixelcbt'),
            __('VixelCBT', 'vixelcbt'),
            'vixelcbt_manage_all',
            'vixelcbt',
            array($this, 'dashboard_page'),
            'dashicons-clipboard',
            30
        );
        
        // Submenus
        add_submenu_page(
            'vixelcbt',
            __('Dashboard', 'vixelcbt'),
            __('Dashboard', 'vixelcbt'),
            'vixelcbt_manage_all',
            'vixelcbt',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'vixelcbt',
            __('Modul', 'vixelcbt'),
            __('Modul', 'vixelcbt'),
            'vixelcbt_manage_modules',
            'vixelcbt-modules',
            array($this, 'modules_page')
        );
        
        add_submenu_page(
            'vixelcbt',
            __('Bank Soal', 'vixelcbt'),
            __('Bank Soal', 'vixelcbt'),
            'vixelcbt_manage_questions',
            'vixelcbt-questions',
            array($this, 'questions_page')
        );
        
        add_submenu_page(
            'vixelcbt',
            __('Peserta', 'vixelcbt'),
            __('Peserta', 'vixelcbt'),
            'vixelcbt_manage_participants',
            'vixelcbt-participants',
            array($this, 'participants_page')
        );
        
        add_submenu_page(
            'vixelcbt',
            __('Kelompok & Sesi', 'vixelcbt'),
            __('Kelompok & Sesi', 'vixelcbt'),
            'vixelcbt_manage_sessions',
            'vixelcbt-sessions',
            array($this, 'sessions_page')
        );
        
        add_submenu_page(
            'vixelcbt',
            __('Hasil Ujian', 'vixelcbt'),
            __('Hasil Ujian', 'vixelcbt'),
            'vixelcbt_manage_results',
            'vixelcbt-results',
            array($this, 'results_page')
        );
        
        add_submenu_page(
            'vixelcbt',
            __('Pengaturan', 'vixelcbt'),
            __('Pengaturan', 'vixelcbt'),
            'vixelcbt_manage_settings',
            'vixelcbt-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'vixelcbt',
            __('Logs', 'vixelcbt'),
            __('Logs', 'vixelcbt'),
            'vixelcbt_view_logs',
            'vixelcbt-logs',
            array($this, 'logs_page')
        );
        
        add_submenu_page(
            'vixelcbt',
            __('Panduan', 'vixelcbt'),
            __('Panduan', 'vixelcbt'),
            'read',
            'vixelcbt-guide',
            array($this, 'guide_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'vixelcbt') === false) {
            return;
        }
        
        wp_enqueue_style(
            'vixelcbt-admin-style',
            VIXELCBT_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            VIXELCBT_VERSION
        );
        
        wp_enqueue_script(
            'vixelcbt-admin-script',
            VIXELCBT_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery'),
            VIXELCBT_VERSION,
            true
        );
        
        wp_localize_script('vixelcbt-admin-script', 'vixelcbt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vixelcbt_admin_nonce'),
        ));
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('vixelcbt_settings', 'vixelcbt_kkm_default');
        register_setting('vixelcbt_settings', 'vixelcbt_attempts_default');
        register_setting('vixelcbt_settings', 'vixelcbt_timer_default');
        register_setting('vixelcbt_settings', 'vixelcbt_grace_period');
        register_setting('vixelcbt_settings', 'vixelcbt_max_upload');
        register_setting('vixelcbt_settings', 'vixelcbt_fullscreen_lock');
        register_setting('vixelcbt_settings', 'vixelcbt_anti_copy');
        register_setting('vixelcbt_settings', 'vixelcbt_blur_threshold');
        register_setting('vixelcbt_settings', 'vixelcbt_show_results');
        register_setting('vixelcbt_settings', 'vixelcbt_pengumuman_open');
        
        // SMTP settings
        register_setting('vixelcbt_smtp', 'vixelcbt_smtp_host');
        register_setting('vixelcbt_smtp', 'vixelcbt_smtp_port');
        register_setting('vixelcbt_smtp', 'vixelcbt_smtp_username');
        register_setting('vixelcbt_smtp', 'vixelcbt_smtp_password');
        register_setting('vixelcbt_smtp', 'vixelcbt_smtp_encryption');
        register_setting('vixelcbt_smtp', 'vixelcbt_from_email');
        register_setting('vixelcbt_smtp', 'vixelcbt_from_name');
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/dashboard.php';
    }
    
    /**
     * Modules page
     */
    public function modules_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/modul.php';
    }
    
    /**
     * Questions page
     */
    public function questions_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/bank-soal.php';
    }
    
    /**
     * Participants page
     */
    public function participants_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/peserta.php';
    }
    
    /**
     * Sessions page
     */
    public function sessions_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/kelompok-sesi.php';
    }
    
    /**
     * Results page
     */
    public function results_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/hasil.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/settings.php';
    }
    
    /**
     * Logs page
     */
    public function logs_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/logs.php';
    }
    
    /**
     * Guide page
     */
    public function guide_page() {
        include VIXELCBT_PLUGIN_PATH . 'admin/partials/panduan.php';
    }
}