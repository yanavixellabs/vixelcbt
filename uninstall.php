<?php
/**
 * Uninstall VixelCBT Plugin
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
$options_to_delete = array(
    'vixelcbt_kkm_default',
    'vixelcbt_attempts_default',
    'vixelcbt_timer_default',
    'vixelcbt_grace_period',
    'vixelcbt_max_upload',
    'vixelcbt_fullscreen_lock',
    'vixelcbt_anti_copy',
    'vixelcbt_blur_threshold',
    'vixelcbt_show_results',
    'vixelcbt_pengumuman_open',
    'vixelcbt_smtp_host',
    'vixelcbt_smtp_port',
    'vixelcbt_smtp_username',
    'vixelcbt_smtp_password',
    'vixelcbt_smtp_encryption',
    'vixelcbt_from_email',
    'vixelcbt_from_name',
);

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// Remove user roles and capabilities
$roles_to_remove = array('vixelcbt_admin', 'vixelcbt_proctor', 'vixelcbt_staff');

foreach ($roles_to_remove as $role) {
    remove_role($role);
}

// Optional: Drop database tables
// Uncomment if you want to remove all data on uninstall
/*
$tables_to_drop = array(
    $wpdb->prefix . 'vxl_users_ext',
    $wpdb->prefix . 'vxl_modul',
    $wpdb->prefix . 'vxl_sesi',
    $wpdb->prefix . 'vxl_soal',
    $wpdb->prefix . 'vxl_attempt',
    $wpdb->prefix . 'vxl_jawaban',
    $wpdb->prefix . 'vxl_kelulusan',
    $wpdb->prefix . 'vxl_tokens',
    $wpdb->prefix . 'vxl_email_logs',
    $wpdb->prefix . 'vxl_logs',
);

foreach ($tables_to_drop as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}
*/