<?php
/**
 * Database Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_Database {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Users Extension Table
        $sql_users_ext = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_users_ext (
            user_id bigint(20) NOT NULL,
            nisn varchar(20) DEFAULT '',
            npsn varchar(20) DEFAULT '',
            sekolah varchar(255) DEFAULT '',
            jurusan varchar(255) DEFAULT '',
            kelas varchar(50) DEFAULT '',
            kota varchar(255) DEFAULT '',
            provinsi varchar(255) DEFAULT '',
            phone varchar(20) DEFAULT '',
            ktp_url varchar(500) DEFAULT '',
            kk_url varchar(500) DEFAULT '',
            kelompok varchar(10) DEFAULT 'A',
            nomor_peserta varchar(50) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            UNIQUE KEY nisn (nisn),
            INDEX idx_kelompok (kelompok),
            INDEX idx_nomor_peserta (nomor_peserta)
        ) $charset_collate;";
        
        // Modul Table
        $sql_modul = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_modul (
            modul_id bigint(20) NOT NULL AUTO_INCREMENT,
            nama varchar(255) NOT NULL,
            deskripsi text,
            pdf_url varchar(500) DEFAULT '',
            kkm int(3) DEFAULT 70,
            attempts_max int(3) DEFAULT 1,
            timer_on_pre tinyint(1) DEFAULT 1,
            timer_pre_min int(5) DEFAULT 90,
            timer_on_post tinyint(1) DEFAULT 1,
            timer_post_min int(5) DEFAULT 90,
            random_soal tinyint(1) DEFAULT 0,
            random_opsi tinyint(1) DEFAULT 0,
            use_same_questions_for_post tinyint(1) DEFAULT 1,
            status enum('draft','active','inactive') DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (modul_id),
            INDEX idx_status (status)
        ) $charset_collate;";
        
        // Sesi Table
        $sql_sesi = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_sesi (
            sesi_id bigint(20) NOT NULL AUTO_INCREMENT,
            modul_id bigint(20) NOT NULL,
            nama varchar(255) NOT NULL,
            tanggal date NOT NULL,
            jam_mulai time NOT NULL,
            durasi_menit int(5) NOT NULL DEFAULT 90,
            grace_menit int(3) NOT NULL DEFAULT 5,
            ruang varchar(100) DEFAULT '',
            kapasitas int(5) DEFAULT 0,
            token_on tinyint(1) DEFAULT 1,
            token_rotate_interval int(5) DEFAULT 30,
            whitelist_ip text,
            kelompok_izin text,
            paket_count int(3) DEFAULT 1,
            status enum('draft','active','completed','cancelled') DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (sesi_id),
            FOREIGN KEY (modul_id) REFERENCES {$wpdb->prefix}vxl_modul(modul_id) ON DELETE CASCADE,
            INDEX idx_modul_id (modul_id),
            INDEX idx_tanggal (tanggal),
            INDEX idx_status (status)
        ) $charset_collate;";
        
        // Soal Table
        $sql_soal = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_soal (
            soal_id bigint(20) NOT NULL AUTO_INCREMENT,
            modul_id bigint(20) NOT NULL,
            tipe enum('pg','checkbox','esai','audio','video','drag_drop','upload') NOT NULL DEFAULT 'pg',
            pertanyaan text NOT NULL,
            opsi text,
            media_url varchar(500) DEFAULT '',
            jawaban_benar text,
            bobot int(3) DEFAULT 1,
            scope enum('pre','post','both') DEFAULT 'both',
            urutan int(5) DEFAULT 0,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (soal_id),
            FOREIGN KEY (modul_id) REFERENCES {$wpdb->prefix}vxl_modul(modul_id) ON DELETE CASCADE,
            INDEX idx_modul_id (modul_id),
            INDEX idx_tipe (tipe),
            INDEX idx_scope (scope),
            INDEX idx_status (status)
        ) $charset_collate;";
        
        // Attempt Table
        $sql_attempt = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_attempt (
            attempt_id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            modul_id bigint(20) NOT NULL,
            sesi_id bigint(20) NOT NULL,
            paket int(3) DEFAULT 1,
            start_at datetime DEFAULT CURRENT_TIMESTAMP,
            end_at datetime NULL,
            status enum('running','submitted','autosubmitted','cancelled') DEFAULT 'running',
            skor_auto decimal(5,2) DEFAULT 0,
            skor_manual decimal(5,2) DEFAULT 0,
            total_skor decimal(5,2) DEFAULT 0,
            blur_count int(5) DEFAULT 0,
            meta text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (attempt_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (modul_id) REFERENCES {$wpdb->prefix}vxl_modul(modul_id) ON DELETE CASCADE,
            FOREIGN KEY (sesi_id) REFERENCES {$wpdb->prefix}vxl_sesi(sesi_id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_modul_id (modul_id),
            INDEX idx_sesi_id (sesi_id),
            INDEX idx_status (status)
        ) $charset_collate;";
        
        // Jawaban Table
        $sql_jawaban = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_jawaban (
            attempt_id bigint(20) NOT NULL,
            soal_id bigint(20) NOT NULL,
            jawaban text,
            nilai decimal(5,2) NULL,
            waktu_update datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (attempt_id, soal_id),
            FOREIGN KEY (attempt_id) REFERENCES {$wpdb->prefix}vxl_attempt(attempt_id) ON DELETE CASCADE,
            FOREIGN KEY (soal_id) REFERENCES {$wpdb->prefix}vxl_soal(soal_id) ON DELETE CASCADE,
            INDEX idx_attempt_id (attempt_id),
            INDEX idx_soal_id (soal_id)
        ) $charset_collate;";
        
        // Kelulusan Table
        $sql_kelulusan = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_kelulusan (
            user_id bigint(20) NOT NULL,
            modul_id bigint(20) NOT NULL,
            sesi_id bigint(20) NOT NULL,
            status enum('lulus','tidak_lulus','pending') DEFAULT 'pending',
            verified_by bigint(20) NULL,
            verified_at datetime NULL,
            catatan text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, modul_id, sesi_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
            FOREIGN KEY (modul_id) REFERENCES {$wpdb->prefix}vxl_modul(modul_id) ON DELETE CASCADE,
            FOREIGN KEY (sesi_id) REFERENCES {$wpdb->prefix}vxl_sesi(sesi_id) ON DELETE CASCADE,
            INDEX idx_status (status),
            INDEX idx_verified_by (verified_by)
        ) $charset_collate;";
        
        // Tokens Table
        $sql_tokens = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_tokens (
            sesi_id bigint(20) NOT NULL,
            token varchar(10) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expired_at datetime NOT NULL,
            PRIMARY KEY (sesi_id, token),
            FOREIGN KEY (sesi_id) REFERENCES {$wpdb->prefix}vxl_sesi(sesi_id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expired_at (expired_at)
        ) $charset_collate;";
        
        // Email Logs Table
        $sql_email_logs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_email_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            recipient varchar(255) NOT NULL,
            subject varchar(500) NOT NULL,
            status enum('sent','failed') DEFAULT 'sent',
            error_message text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_recipient (recipient),
            INDEX idx_status (status),
            INDEX idx_sent_at (sent_at)
        ) $charset_collate;";
        
        // Logs Table
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vxl_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NULL,
            aksi varchar(255) NOT NULL,
            context text,
            ip_address varchar(45) DEFAULT '',
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_user_id (user_id),
            INDEX idx_aksi (aksi),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_users_ext);
        dbDelta($sql_modul);
        dbDelta($sql_sesi);
        dbDelta($sql_soal);
        dbDelta($sql_attempt);
        dbDelta($sql_jawaban);
        dbDelta($sql_kelulusan);
        dbDelta($sql_tokens);
        dbDelta($sql_email_logs);
        dbDelta($sql_logs);
    }
    
    /**
     * Get user extended data
     */
    public function get_user_ext($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vxl_users_ext WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Save user extended data
     */
    public function save_user_ext($user_id, $data) {
        global $wpdb;
        
        $existing = $this->get_user_ext($user_id);
        
        if ($existing) {
            return $wpdb->update(
                $wpdb->prefix . 'vxl_users_ext',
                $data,
                array('user_id' => $user_id),
                '%s',
                '%d'
            );
        } else {
            $data['user_id'] = $user_id;
            return $wpdb->insert(
                $wpdb->prefix . 'vxl_users_ext',
                $data
            );
        }
    }
    
    /**
     * Log activity
     */
    public function log_activity($user_id, $action, $context = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vxl_logs',
            array(
                'user_id' => $user_id,
                'aksi' => $action,
                'context' => wp_json_encode($context),
                'ip_address' => vixelcbt_get_user_ip(),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            )
        );
    }
}