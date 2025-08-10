<?php
/**
 * Authentication and Security Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_Auth {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('wp_login', array($this, 'handle_login'), 10, 2);
        add_action('wp_logout', array($this, 'handle_logout'));
        add_action('wp_login_failed', array($this, 'handle_login_failed'));
        add_action('init', array($this, 'check_session_security'));
        
        // Rate limiting
        add_action('wp_ajax_nopriv_vixelcbt_login_user', array($this, 'check_login_rate_limit'), 1);
        add_action('wp_ajax_nopriv_vixelcbt_check_graduation', array($this, 'check_graduation_rate_limit'), 1);
    }
    
    /**
     * Handle successful login
     */
    public function handle_login($user_login, $user) {
        // Log successful login
        VixelCBT_Database::instance()->log_activity($user->ID, 'login_success', array(
            'username' => $user_login,
            'ip_address' => vixelcbt_get_user_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ));
        
        // Clear failed login attempts
        $this->clear_failed_attempts(vixelcbt_get_user_ip());
        
        // Set last login time
        update_user_meta($user->ID, 'vixelcbt_last_login', current_time('mysql'));
        
        // Check for concurrent sessions if needed
        $this->handle_concurrent_sessions($user->ID);
    }
    
    /**
     * Handle logout
     */
    public function handle_logout() {
        $user_id = get_current_user_id();
        if ($user_id) {
            VixelCBT_Database::instance()->log_activity($user_id, 'logout', array(
                'ip_address' => vixelcbt_get_user_ip(),
            ));
        }
    }
    
    /**
     * Handle failed login
     */
    public function handle_login_failed($username) {
        $ip = vixelcbt_get_user_ip();
        
        // Log failed attempt
        VixelCBT_Database::instance()->log_activity(null, 'login_failed', array(
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ));
        
        // Increment failed attempts
        $this->increment_failed_attempts($ip);
    }
    
    /**
     * Check login rate limiting
     */
    public function check_login_rate_limit() {
        $ip = vixelcbt_get_user_ip();
        $failed_attempts = $this->get_failed_attempts($ip);
        
        if ($failed_attempts >= 5) {
            $lockout_time = get_transient('vixelcbt_lockout_' . md5($ip));
            if ($lockout_time) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Too many failed login attempts. Please try again in %d minutes.', 'vixelcbt'),
                        ceil(($lockout_time - time()) / 60)
                    ),
                ));
            }
        }
    }
    
    /**
     * Check graduation check rate limiting
     */
    public function check_graduation_rate_limit() {
        $ip = vixelcbt_get_user_ip();
        $rate_limit_key = 'vixelcbt_graduation_check_' . md5($ip);
        $attempts = get_transient($rate_limit_key);
        
        if ($attempts && $attempts >= 5) {
            wp_send_json_error(array(
                'message' => __('Too many requests. Please try again later.', 'vixelcbt'),
            ));
        }
    }
    
    /**
     * Check session security
     */
    public function check_session_security() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check for session hijacking
        $this->check_session_hijacking($user_id);
        
        // Check for concurrent exam sessions
        $this->check_concurrent_exam_sessions($user_id);
    }
    
    /**
     * Check for session hijacking
     */
    private function check_session_hijacking($user_id) {
        $current_ip = vixelcbt_get_user_ip();
        $current_user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        $stored_ip = get_user_meta($user_id, 'vixelcbt_session_ip', true);
        $stored_user_agent = get_user_meta($user_id, 'vixelcbt_session_user_agent', true);
        
        if (empty($stored_ip)) {
            // First time, store the session info
            update_user_meta($user_id, 'vixelcbt_session_ip', $current_ip);
            update_user_meta($user_id, 'vixelcbt_session_user_agent', $current_user_agent);
            return;
        }
        
        // Check for suspicious changes
        if ($stored_ip !== $current_ip || $stored_user_agent !== $current_user_agent) {
            // Log suspicious activity
            VixelCBT_Database::instance()->log_activity($user_id, 'suspicious_session', array(
                'old_ip' => $stored_ip,
                'new_ip' => $current_ip,
                'old_user_agent' => $stored_user_agent,
                'new_user_agent' => $current_user_agent,
            ));
            
            // For now, just update the stored values
            // In a more strict implementation, you might force logout
            update_user_meta($user_id, 'vixelcbt_session_ip', $current_ip);
            update_user_meta($user_id, 'vixelcbt_session_user_agent', $current_user_agent);
        }
    }
    
    /**
     * Check for concurrent exam sessions
     */
    private function check_concurrent_exam_sessions($user_id) {
        global $wpdb;
        
        // Check if user has running exam attempts
        $running_attempts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vxl_attempt 
             WHERE user_id = %d AND status = 'running'",
            $user_id
        ));
        
        if (count($running_attempts) > 1) {
            // Multiple running attempts detected
            VixelCBT_Database::instance()->log_activity($user_id, 'concurrent_exam_sessions', array(
                'attempt_count' => count($running_attempts),
                'attempt_ids' => wp_list_pluck($running_attempts, 'attempt_id'),
            ));
            
            // Cancel all but the most recent attempt
            $latest_attempt = array_pop($running_attempts);
            foreach ($running_attempts as $attempt) {
                $wpdb->update(
                    $wpdb->prefix . 'vxl_attempt',
                    array('status' => 'cancelled'),
                    array('attempt_id' => $attempt->attempt_id),
                    array('%s'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * Handle concurrent sessions
     */
    private function handle_concurrent_sessions($user_id) {
        // Get current session token
        $current_token = wp_get_session_token();
        
        // Store session info
        update_user_meta($user_id, 'vixelcbt_current_session', $current_token);
        update_user_meta($user_id, 'vixelcbt_session_start', current_time('mysql'));
    }
    
    /**
     * Increment failed login attempts
     */
    private function increment_failed_attempts($ip) {
        $key = 'vixelcbt_failed_attempts_' . md5($ip);
        $attempts = get_transient($key);
        $attempts = $attempts ? $attempts + 1 : 1;
        
        // Store for 15 minutes
        set_transient($key, $attempts, 900);
        
        // If too many attempts, set lockout
        if ($attempts >= 5) {
            $lockout_key = 'vixelcbt_lockout_' . md5($ip);
            set_transient($lockout_key, time() + 1800, 1800); // 30 minutes lockout
        }
    }
    
    /**
     * Get failed login attempts
     */
    private function get_failed_attempts($ip) {
        $key = 'vixelcbt_failed_attempts_' . md5($ip);
        return get_transient($key) ?: 0;
    }
    
    /**
     * Clear failed login attempts
     */
    private function clear_failed_attempts($ip) {
        $key = 'vixelcbt_failed_attempts_' . md5($ip);
        delete_transient($key);
        
        $lockout_key = 'vixelcbt_lockout_' . md5($ip);
        delete_transient($lockout_key);
    }
    
    /**
     * Validate exam access
     */
    public function validate_exam_access($user_id, $sesi_id) {
        global $wpdb;
        
        // Check if user exists and is active
        $user = get_user_by('ID', $user_id);
        if (!$user || $user->user_status != 0) {
            return new WP_Error('invalid_user', __('Invalid user account.', 'vixelcbt'));
        }
        
        // Get user extended data
        $user_ext = VixelCBT_Database::instance()->get_user_ext($user_id);
        if (!$user_ext) {
            return new WP_Error('incomplete_profile', __('Please complete your profile first.', 'vixelcbt'));
        }
        
        // Get session data
        $sesi = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vxl_sesi WHERE sesi_id = %d",
            $sesi_id
        ));
        
        if (!$sesi || $sesi->status !== 'active') {
            return new WP_Error('invalid_session', __('Session not found or inactive.', 'vixelcbt'));
        }
        
        // Check time window
        if (!vixelcbt_is_session_active($sesi_id)) {
            return new WP_Error('session_closed', __('Session is not currently active.', 'vixelcbt'));
        }
        
        // Check group permissions
        if ($sesi->kelompok_izin) {
            $allowed_groups = json_decode($sesi->kelompok_izin, true);
            if (!in_array($user_ext->kelompok, $allowed_groups)) {
                return new WP_Error('group_not_allowed', __('Your group is not allowed for this session.', 'vixelcbt'));
            }
        }
        
        // Check IP whitelist
        if ($sesi->whitelist_ip) {
            $allowed_ips = json_decode($sesi->whitelist_ip, true);
            $user_ip = vixelcbt_get_user_ip();
            
            if (!in_array($user_ip, $allowed_ips)) {
                return new WP_Error('ip_not_allowed', __('Your IP address is not allowed for this session.', 'vixelcbt'));
            }
        }
        
        // Check capacity
        if ($sesi->kapasitas > 0) {
            $current_participants = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}vxl_attempt 
                 WHERE sesi_id = %d AND status = 'running'",
                $sesi_id
            ));
            
            if ($current_participants >= $sesi->kapasitas) {
                return new WP_Error('session_full', __('Session is full.', 'vixelcbt'));
            }
        }
        
        // Check attempt limits
        $modul = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vxl_modul WHERE modul_id = %d",
            $sesi->modul_id
        ));
        
        $attempt_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vxl_attempt 
             WHERE user_id = %d AND modul_id = %d AND status IN ('submitted', 'autosubmitted')",
            $user_id,
            $sesi->modul_id
        ));
        
        if ($attempt_count >= $modul->attempts_max) {
            return new WP_Error('max_attempts_reached', __('Maximum attempts reached for this module.', 'vixelcbt'));
        }
        
        return true;
    }
    
    /**
     * Generate secure token
     */
    public function generate_secure_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Validate CSRF token
     */
    public function validate_csrf_token($token, $action) {
        return wp_verify_nonce($token, $action);
    }
    
    /**
     * Sanitize and validate input
     */
    public function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'int':
                return intval($input);
            case 'float':
                return floatval($input);
            case 'html':
                return wp_kses_post($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            default:
                return sanitize_text_field($input);
        }
    }
    
    /**
     * Check user capability with context
     */
    public function check_capability($capability, $context = array()) {
        if (!current_user_can($capability)) {
            VixelCBT_Database::instance()->log_activity(
                get_current_user_id(),
                'capability_check_failed',
                array_merge($context, array('capability' => $capability))
            );
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Get security headers
     */
    public function get_security_headers() {
        return array(
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';",
        );
    }
    
    /**
     * Apply security headers
     */
    public function apply_security_headers() {
        $headers = $this->get_security_headers();
        
        foreach ($headers as $header => $value) {
            header($header . ': ' . $value);
        }
    }
}