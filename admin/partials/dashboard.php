<?php
/**
 * Dashboard Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get statistics
$total_participants = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vxl_users_ext");
$active_sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vxl_sesi WHERE status = 'active'");
$attempts_today = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}vxl_attempt WHERE DATE(start_at) = %s",
    current_time('Y-m-d')
));
$total_modules = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vxl_modul WHERE status = 'active'");

// Recent activities
$recent_logs = $wpdb->get_results(
    "SELECT l.*, u.display_name 
     FROM {$wpdb->prefix}vxl_logs l 
     LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
     ORDER BY l.created_at DESC 
     LIMIT 10"
);

?>
<div class="wrap">
    <h1><?php esc_html_e('VixelCBT Dashboard', 'vixelcbt'); ?></h1>
    
    <div class="vixelcbt-dashboard">
        <!-- Statistics Cards -->
        <div class="vixelcbt-stats-grid">
            <div class="vixelcbt-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($total_participants); ?></h3>
                    <p><?php esc_html_e('Total Peserta', 'vixelcbt'); ?></p>
                </div>
            </div>
            
            <div class="vixelcbt-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($active_sessions); ?></h3>
                    <p><?php esc_html_e('Sesi Aktif', 'vixelcbt'); ?></p>
                </div>
            </div>
            
            <div class="vixelcbt-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-edit"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($attempts_today); ?></h3>
                    <p><?php esc_html_e('Ujian Hari Ini', 'vixelcbt'); ?></p>
                </div>
            </div>
            
            <div class="vixelcbt-stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-book"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($total_modules); ?></h3>
                    <p><?php esc_html_e('Total Modul', 'vixelcbt'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="vixelcbt-quick-actions">
            <h2><?php esc_html_e('Aksi Cepat', 'vixelcbt'); ?></h2>
            <div class="quick-actions-grid">
                <a href="<?php echo admin_url('admin.php?page=vixelcbt-modules&action=add'); ?>" class="quick-action-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e('Buat Modul Baru', 'vixelcbt'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=vixelcbt-sessions&action=add'); ?>" class="quick-action-btn">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Buat Sesi Ujian', 'vixelcbt'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=vixelcbt-participants&action=import'); ?>" class="quick-action-btn">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Import Peserta', 'vixelcbt'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=vixelcbt-results'); ?>" class="quick-action-btn">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Lihat Hasil', 'vixelcbt'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=vixelcbt-guide&tab=generator'); ?>" class="quick-action-btn">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php esc_html_e('Generator Halaman', 'vixelcbt'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=vixelcbt-settings'); ?>" class="quick-action-btn">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Pengaturan', 'vixelcbt'); ?>
                </a>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="vixelcbt-recent-activities">
            <h2><?php esc_html_e('Aktivitas Terbaru', 'vixelcbt'); ?></h2>
            
            <?php if ($recent_logs): ?>
            <div class="activities-list">
                <?php foreach ($recent_logs as $log): ?>
                <div class="activity-item">
                    <div class="activity-content">
                        <strong><?php echo esc_html($log->display_name ?: 'System'); ?></strong>
                        <span><?php echo esc_html($log->aksi); ?></span>
                    </div>
                    <div class="activity-time">
                        <?php echo esc_html(human_time_diff(strtotime($log->created_at), current_time('timestamp'))); ?> 
                        <?php esc_html_e('ago', 'vixelcbt'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p><?php esc_html_e('Belum ada aktivitas terbaru.', 'vixelcbt'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- System Info -->
        <div class="vixelcbt-system-info">
            <h2><?php esc_html_e('Informasi Sistem', 'vixelcbt'); ?></h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Plugin Version', 'vixelcbt'); ?>:</strong></td>
                        <td><?php echo esc_html(VIXELCBT_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('WordPress Version', 'vixelcbt'); ?>:</strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('PHP Version', 'vixelcbt'); ?>:</strong></td>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Database', 'vixelcbt'); ?>:</strong></td>
                        <td><?php echo esc_html($wpdb->db_version()); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>