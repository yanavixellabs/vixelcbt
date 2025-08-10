<?php
/**
 * User Roles and Capabilities
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom user roles
 */
function vixelcbt_add_user_roles() {
    
    // VixelCBT Admin - Full access
    add_role('vixelcbt_admin', __('VixelCBT Admin', 'vixelcbt'), array(
        'read' => true,
        'vixelcbt_manage_all' => true,
        'vixelcbt_manage_modules' => true,
        'vixelcbt_manage_questions' => true,
        'vixelcbt_manage_participants' => true,
        'vixelcbt_manage_sessions' => true,
        'vixelcbt_manage_results' => true,
        'vixelcbt_manage_settings' => true,
        'vixelcbt_export_data' => true,
        'vixelcbt_view_logs' => true,
    ));
    
    // VixelCBT Proctor - Session management and monitoring
    add_role('vixelcbt_proctor', __('VixelCBT Proctor', 'vixelcbt'), array(
        'read' => true,
        'vixelcbt_manage_sessions' => true,
        'vixelcbt_monitor_exams' => true,
        'vixelcbt_verify_results' => true,
        'vixelcbt_manage_tokens' => true,
        'vixelcbt_print_documents' => true,
    ));
    
    // VixelCBT Staff - Data entry and reports
    add_role('vixelcbt_staff', __('VixelCBT Staff', 'vixelcbt'), array(
        'read' => true,
        'vixelcbt_manage_participants' => true,
        'vixelcbt_export_data' => true,
        'vixelcbt_print_documents' => true,
        'vixelcbt_view_results' => true,
    ));
    
    // Add capabilities to existing roles
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('vixelcbt_manage_all');
        $admin->add_cap('vixelcbt_manage_modules');
        $admin->add_cap('vixelcbt_manage_questions');
        $admin->add_cap('vixelcbt_manage_participants');
        $admin->add_cap('vixelcbt_manage_sessions');
        $admin->add_cap('vixelcbt_manage_results');
        $admin->add_cap('vixelcbt_manage_settings');
        $admin->add_cap('vixelcbt_export_data');
        $admin->add_cap('vixelcbt_view_logs');
        $admin->add_cap('vixelcbt_monitor_exams');
        $admin->add_cap('vixelcbt_verify_results');
        $admin->add_cap('vixelcbt_manage_tokens');
        $admin->add_cap('vixelcbt_print_documents');
        $admin->add_cap('vixelcbt_view_results');
    }
}

/**
 * Check if user has VixelCBT capability
 */
function vixelcbt_user_can($capability) {
    return current_user_can($capability) || current_user_can('vixelcbt_manage_all');
}

/**
 * Get user role display name
 */
function vixelcbt_get_role_name($role) {
    $roles = array(
        'vixelcbt_admin' => __('VixelCBT Admin', 'vixelcbt'),
        'vixelcbt_proctor' => __('Proctor', 'vixelcbt'),
        'vixelcbt_staff' => __('Staff', 'vixelcbt'),
        'subscriber' => __('Participant', 'vixelcbt'),
    );
    
    return $roles[$role] ?? $role;
}