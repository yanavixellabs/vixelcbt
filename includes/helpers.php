<?php
/**
 * Helper Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user IP address
 */
function vixelcbt_get_user_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Generate participant number
 */
function vixelcbt_generate_nomor_peserta($user_id) {
    $year = date('Y');
    $formatted_id = str_pad($user_id, 4, '0', STR_PAD_LEFT);
    return "CBT{$year}{$formatted_id}";
}

/**
 * Generate random token
 */
function vixelcbt_generate_token($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $token;
}

/**
 * Check if session is active
 */
function vixelcbt_is_session_active($sesi_id) {
    global $wpdb;
    
    $sesi = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vxl_sesi WHERE sesi_id = %d",
        $sesi_id
    ));
    
    if (!$sesi) {
        return false;
    }
    
    $now = current_time('mysql');
    $start_time = $sesi->tanggal . ' ' . $sesi->jam_mulai;
    $end_time = date('Y-m-d H:i:s', strtotime($start_time . ' +' . ($sesi->durasi_menit + $sesi->grace_menit) . ' minutes'));
    
    return ($now >= $start_time && $now <= $end_time);
}

/**
 * Sanitize file upload
 */
function vixelcbt_sanitize_file_upload($file) {
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx');
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return new WP_Error('invalid_file_type', __('File type not allowed.', 'vixelcbt'));
    }
    
    $max_size = wp_max_upload_size();
    if ($file['size'] > $max_size) {
        return new WP_Error('file_too_large', __('File size exceeds limit.', 'vixelcbt'));
    }
    
    return true;
}

/**
 * Format time duration
 */
function vixelcbt_format_duration($minutes) {
    if ($minutes < 60) {
        return sprintf(__('%d minutes', 'vixelcbt'), $minutes);
    }
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($mins == 0) {
        return sprintf(__('%d hours', 'vixelcbt'), $hours);
    }
    
    return sprintf(__('%d hours %d minutes', 'vixelcbt'), $hours, $mins);
}

/**
 * Get grade status
 */
function vixelcbt_get_grade_status($score, $kkm) {
    if ($score >= $kkm) {
        return array(
            'status' => 'lulus',
            'label' => __('PASSED', 'vixelcbt'),
            'class' => 'success'
        );
    } else {
        return array(
            'status' => 'tidak_lulus',
            'label' => __('NOT PASSED', 'vixelcbt'),
            'class' => 'danger'
        );
    }
}

/**
 * Clean input data
 */
function vixelcbt_clean_input($data) {
    if (is_array($data)) {
        return array_map('vixelcbt_clean_input', $data);
    }
    return sanitize_text_field($data);
}

/**
 * Verify nonce with error handling
 */
function vixelcbt_verify_nonce($nonce, $action) {
    if (!wp_verify_nonce($nonce, $action)) {
        wp_die(__('Security check failed. Please try again.', 'vixelcbt'));
    }
}

/**
 * Check user capability with error handling
 */
function vixelcbt_check_capability($capability) {
    if (!current_user_can($capability)) {
        wp_die(__('You do not have permission to perform this action.', 'vixelcbt'));
    }
}