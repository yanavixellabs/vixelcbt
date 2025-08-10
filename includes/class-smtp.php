<?php
/**
 * SMTP Email Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_SMTP {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('phpmailer_init', array($this, 'configure_smtp'));
        add_action('wp_ajax_vixelcbt_test_email', array($this, 'test_email'));
    }
    
    /**
     * Configure SMTP settings
     */
    public function configure_smtp($phpmailer) {
        // Only configure if VixelCBT SMTP is enabled
        $smtp_host = get_option('vixelcbt_smtp_host');
        if (empty($smtp_host)) {
            return;
        }
        
        $phpmailer->isSMTP();
        $phpmailer->Host = $smtp_host;
        $phpmailer->Port = get_option('vixelcbt_smtp_port', 587);
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = get_option('vixelcbt_smtp_username');
        $phpmailer->Password = $this->decrypt_password(get_option('vixelcbt_smtp_password'));
        
        $encryption = get_option('vixelcbt_smtp_encryption', 'tls');
        if ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $phpmailer->From = get_option('vixelcbt_from_email', get_option('admin_email'));
        $phpmailer->FromName = get_option('vixelcbt_from_name', get_bloginfo('name'));
    }
    
    /**
     * Send email with template
     */
    public function send_email($to, $subject, $template, $variables = array()) {
        $template_content = $this->get_email_template($template);
        if (!$template_content) {
            return false;
        }
        
        // Replace variables in template
        $content = $this->replace_template_variables($template_content, $variables);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $result = wp_mail($to, $subject, $content, $headers);
        
        // Log email
        $this->log_email($to, $subject, $result ? 'sent' : 'failed', $result ? '' : 'Unknown error');
        
        return $result;
    }
    
    /**
     * Get email template
     */
    private function get_email_template($template_name) {
        $templates = array(
            'registration' => $this->get_registration_template(),
            'exam_scheduled' => $this->get_exam_scheduled_template(),
            'exam_token' => $this->get_exam_token_template(),
            'exam_results' => $this->get_exam_results_template(),
            'remedial_notice' => $this->get_remedial_notice_template(),
        );
        
        return isset($templates[$template_name]) ? $templates[$template_name] : false;
    }
    
    /**
     * Registration email template
     */
    private function get_registration_template() {
        return '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Selamat Datang di VixelCBT</h1>
                </div>
                <div class="content">
                    <h2>Halo {{nama}},</h2>
                    <p>Selamat! Pendaftaran Anda telah berhasil diproses.</p>
                    <p><strong>Detail Akun:</strong></p>
                    <ul>
                        <li>Username: {{username}}</li>
                        <li>Email: {{email}}</li>
                        <li>Nomor Peserta: {{nomor_peserta}}</li>
                    </ul>
                    <p>Anda sekarang dapat login dan mengikuti ujian yang tersedia.</p>
                    <p><a href="{{login_url}}" class="button">Login Sekarang</a></p>
                </div>
                <div class="footer">
                    <p>&copy; {{site_name}} - Sistem CBT</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Exam scheduled email template
     */
    private function get_exam_scheduled_template() {
        return '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #14b8a6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .exam-details { background: white; padding: 15px; border-radius: 4px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Jadwal Ujian Baru</h1>
                </div>
                <div class="content">
                    <h2>Halo {{nama}},</h2>
                    <p>Anda telah dijadwalkan untuk mengikuti ujian berikut:</p>
                    <div class="exam-details">
                        <h3>{{modul_nama}}</h3>
                        <p><strong>Tanggal:</strong> {{tanggal}}</p>
                        <p><strong>Waktu:</strong> {{jam_mulai}}</p>
                        <p><strong>Durasi:</strong> {{durasi}} menit</p>
                        <p><strong>Ruang:</strong> {{ruang}}</p>
                        <p><strong>Sesi:</strong> {{sesi_nama}}</p>
                    </div>
                    <p>Pastikan Anda hadir tepat waktu dan membawa kartu peserta.</p>
                </div>
                <div class="footer">
                    <p>&copy; {{site_name}} - Sistem CBT</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Exam token email template
     */
    private function get_exam_token_template() {
        return '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f97316; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .token { font-size: 24px; font-weight: bold; text-align: center; background: white; padding: 20px; border-radius: 4px; letter-spacing: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Token Ujian</h1>
                </div>
                <div class="content">
                    <h2>Halo {{nama}},</h2>
                    <p>Token untuk ujian {{modul_nama}} telah tersedia:</p>
                    <div class="token">{{token}}</div>
                    <p><strong>Berlaku hingga:</strong> {{expired_at}}</p>
                    <p>Gunakan token ini untuk memulai ujian Anda.</p>
                </div>
                <div class="footer">
                    <p>&copy; {{site_name}} - Sistem CBT</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Exam results email template
     */
    private function get_exam_results_template() {
        return '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #10b981; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .result { background: white; padding: 15px; border-radius: 4px; margin: 15px 0; text-align: center; }
                .passed { border-left: 4px solid #10b981; }
                .failed { border-left: 4px solid #ef4444; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Hasil Ujian</h1>
                </div>
                <div class="content">
                    <h2>Halo {{nama}},</h2>
                    <p>Hasil ujian Anda telah diverifikasi:</p>
                    <div class="result {{status_class}}">
                        <h3>{{modul_nama}}</h3>
                        <p><strong>Skor:</strong> {{skor}}</p>
                        <p><strong>Status:</strong> {{status_text}}</p>
                        {{#if catatan}}
                        <p><strong>Catatan:</strong> {{catatan}}</p>
                        {{/if}}
                    </div>
                    <p>Anda dapat melihat detail hasil di dashboard peserta.</p>
                </div>
                <div class="footer">
                    <p>&copy; {{site_name}} - Sistem CBT</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Remedial notice email template
     */
    private function get_remedial_notice_template() {
        return '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f59e0b; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 12px 24px; background: #f59e0b; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Kesempatan Remedial</h1>
                </div>
                <div class="content">
                    <h2>Halo {{nama}},</h2>
                    <p>Anda mendapat kesempatan untuk mengikuti ujian remedial:</p>
                    <p><strong>Modul:</strong> {{modul_nama}}</p>
                    <p><strong>Attempt ke:</strong> {{attempt_number}}</p>
                    <p>Silakan login ke dashboard untuk mengikuti ujian remedial.</p>
                    <p><a href="{{dashboard_url}}" class="button">Ke Dashboard</a></p>
                </div>
                <div class="footer">
                    <p>&copy; {{site_name}} - Sistem CBT</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Replace template variables
     */
    private function replace_template_variables($template, $variables) {
        // Add default variables
        $default_variables = array(
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'login_url' => home_url('/login'),
            'dashboard_url' => home_url('/dashboard'),
        );
        
        $variables = array_merge($default_variables, $variables);
        
        // Simple template variable replacement
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        // Handle conditional blocks (basic implementation)
        $template = preg_replace('/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s', function($matches) use ($variables) {
            $condition = $matches[1];
            $content = $matches[2];
            return !empty($variables[$condition]) ? $content : '';
        }, $template);
        
        return $template;
    }
    
    /**
     * Test email functionality
     */
    public function test_email() {
        if (!current_user_can('vixelcbt_manage_settings')) {
            wp_die(__('You do not have permission to perform this action.', 'vixelcbt'));
        }
        
        vixelcbt_verify_nonce($_POST['nonce'], 'vixelcbt_admin_nonce');
        
        $test_email = sanitize_email($_POST['test_email']);
        if (!$test_email) {
            wp_send_json_error(array(
                'message' => __('Please provide a valid email address.', 'vixelcbt'),
            ));
        }
        
        $subject = __('VixelCBT Test Email', 'vixelcbt');
        $message = __('This is a test email from VixelCBT. If you receive this, your SMTP configuration is working correctly.', 'vixelcbt');
        
        $result = wp_mail($test_email, $subject, $message);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Test email sent successfully!', 'vixelcbt'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to send test email. Please check your SMTP settings.', 'vixelcbt'),
            ));
        }
    }
    
    /**
     * Encrypt password
     */
    public function encrypt_password($password) {
        if (empty($password)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt password
     */
    private function decrypt_password($encrypted_password) {
        if (empty($encrypted_password)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $data = base64_decode($encrypted_password);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Log email
     */
    private function log_email($recipient, $subject, $status, $error_message = '') {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vxl_email_logs',
            array(
                'recipient' => $recipient,
                'subject' => $subject,
                'status' => $status,
                'error_message' => $error_message,
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get email logs
     */
    public function get_email_logs($limit = 50, $offset = 0) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vxl_email_logs 
             ORDER BY sent_at DESC 
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    /**
     * Send bulk emails
     */
    public function send_bulk_emails($recipients, $subject, $template, $variables = array()) {
        $results = array(
            'sent' => 0,
            'failed' => 0,
            'errors' => array(),
        );
        
        foreach ($recipients as $recipient) {
            // Merge recipient-specific variables
            $recipient_variables = array_merge($variables, $recipient);
            
            $result = $this->send_email(
                $recipient['email'],
                $subject,
                $template,
                $recipient_variables
            );
            
            if ($result) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $recipient['email'];
            }
            
            // Add small delay to prevent overwhelming the SMTP server
            usleep(100000); // 0.1 second
        }
        
        return $results;
    }
}