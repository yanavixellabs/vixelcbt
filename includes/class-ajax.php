<?php
/**
 * AJAX Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_Ajax {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('wp_ajax_vixelcbt_register_user', array($this, 'register_user'));
        add_action('wp_ajax_nopriv_vixelcbt_register_user', array($this, 'register_user'));
        
        add_action('wp_ajax_vixelcbt_login_user', array($this, 'login_user'));
        add_action('wp_ajax_nopriv_vixelcbt_login_user', array($this, 'login_user'));
        
        add_action('wp_ajax_vixelcbt_check_graduation', array($this, 'check_graduation'));
        add_action('wp_ajax_nopriv_vixelcbt_check_graduation', array($this, 'check_graduation'));
        
        add_action('wp_ajax_vixelcbt_exam_start', array($this, 'exam_start'));
        add_action('wp_ajax_vixelcbt_exam_save_answer', array($this, 'exam_save_answer'));
        add_action('wp_ajax_vixelcbt_exam_submit', array($this, 'exam_submit'));
        add_action('wp_ajax_vixelcbt_exam_state', array($this, 'exam_state'));
        
        add_action('wp_ajax_vixelcbt_verify_token', array($this, 'verify_token'));
    }
    
    /**
     * Register new user
     */
    public function register_user() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['vixelcbt_register_nonce'], 'vixelcbt_register')) {
                throw new Exception(__('Security check failed.', 'vixelcbt'));
            }
            
            // Validate required fields
            $username = sanitize_user($_POST['username']);
            $email = sanitize_email($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $nama_lengkap = sanitize_text_field($_POST['nama_lengkap']);
            
            if (empty($username) || empty($email) || empty($password) || empty($nama_lengkap)) {
                throw new Exception(__('All required fields must be filled.', 'vixelcbt'));
            }
            
            if ($password !== $confirm_password) {
                throw new Exception(__('Password confirmation does not match.', 'vixelcbt'));
            }
            
            // Check if username or email already exists
            if (username_exists($username)) {
                throw new Exception(__('Username already exists.', 'vixelcbt'));
            }
            
            if (email_exists($email)) {
                throw new Exception(__('Email already exists.', 'vixelcbt'));
            }
            
            // Create user
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                throw new Exception($user_id->get_error_message());
            }
            
            // Update user display name
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $nama_lengkap,
                'first_name' => $nama_lengkap,
            ));
            
            // Set user role to subscriber (participant)
            $user = new WP_User($user_id);
            $user->set_role('subscriber');
            
            // Save extended user data
            $user_ext_data = array();
            
            if (!empty($_POST['nisn'])) {
                $user_ext_data['nisn'] = sanitize_text_field($_POST['nisn']);
            }
            
            if (!empty($_POST['npsn'])) {
                $user_ext_data['npsn'] = sanitize_text_field($_POST['npsn']);
            }
            
            if (!empty($_POST['sekolah'])) {
                $user_ext_data['sekolah'] = sanitize_text_field($_POST['sekolah']);
            }
            
            if (!empty($_POST['jurusan'])) {
                $user_ext_data['jurusan'] = sanitize_text_field($_POST['jurusan']);
            }
            
            if (!empty($_POST['kelas'])) {
                $user_ext_data['kelas'] = sanitize_text_field($_POST['kelas']);
            }
            
            if (!empty($_POST['phone'])) {
                $user_ext_data['phone'] = sanitize_text_field($_POST['phone']);
            }
            
            // Generate nomor peserta
            $user_ext_data['nomor_peserta'] = vixelcbt_generate_nomor_peserta($user_id);
            $user_ext_data['kelompok'] = 'A'; // Default group
            
            // Handle file uploads
            if (!empty($_FILES['ktp_file']['name'])) {
                $upload_result = $this->handle_file_upload($_FILES['ktp_file'], $user_id, 'ktp');
                if (!is_wp_error($upload_result)) {
                    $user_ext_data['ktp_url'] = $upload_result['url'];
                }
            }
            
            if (!empty($_FILES['kk_file']['name'])) {
                $upload_result = $this->handle_file_upload($_FILES['kk_file'], $user_id, 'kk');
                if (!is_wp_error($upload_result)) {
                    $user_ext_data['kk_url'] = $upload_result['url'];
                }
            }
            
            // Save extended data
            VixelCBT_Database::instance()->save_user_ext($user_id, $user_ext_data);
            
            // Log activity
            VixelCBT_Database::instance()->log_activity($user_id, 'user_registered', array(
                'username' => $username,
                'email' => $email,
            ));
            
            wp_send_json_success(array(
                'message' => __('Registration successful! You can now login.', 'vixelcbt'),
                'user_id' => $user_id,
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Login user
     */
    public function login_user() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['vixelcbt_login_nonce'], 'vixelcbt_login')) {
                throw new Exception(__('Security check failed.', 'vixelcbt'));
            }
            
            $username = sanitize_user($_POST['username']);
            $password = $_POST['password'];
            $remember = !empty($_POST['remember']);
            $redirect_to = sanitize_url($_POST['redirect_to'] ?? '/dashboard');
            
            if (empty($username) || empty($password)) {
                throw new Exception(__('Username and password are required.', 'vixelcbt'));
            }
            
            // Attempt login
            $user = wp_authenticate($username, $password);
            
            if (is_wp_error($user)) {
                throw new Exception($user->get_error_message());
            }
            
            // Set authentication cookie
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, $remember);
            
            // Log activity
            VixelCBT_Database::instance()->log_activity($user->ID, 'user_login', array(
                'username' => $username,
            ));
            
            wp_send_json_success(array(
                'message' => __('Login successful!', 'vixelcbt'),
                'redirect' => $redirect_to,
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Check graduation status
     */
    public function check_graduation() {
        try {
            // Rate limiting check
            $ip = vixelcbt_get_user_ip();
            $rate_limit_key = 'vixelcbt_graduation_check_' . md5($ip);
            $attempts = get_transient($rate_limit_key);
            
            if ($attempts && $attempts >= 5) {
                throw new Exception(__('Too many requests. Please try again later.', 'vixelcbt'));
            }
            
            // Verify nonce
            if (!wp_verify_nonce($_POST['vixelcbt_cek_kelulusan_nonce'], 'vixelcbt_cek_kelulusan')) {
                throw new Exception(__('Security check failed.', 'vixelcbt'));
            }
            
            $search_key = sanitize_text_field($_POST['search_key']);
            $method = sanitize_text_field($_POST['method']);
            $show = sanitize_text_field($_POST['show']);
            $protect = sanitize_text_field($_POST['protect']);
            
            if (empty($search_key)) {
                throw new Exception(__('Search key is required.', 'vixelcbt'));
            }
            
            // Check if announcements are open
            if (!get_option('vixelcbt_pengumuman_open', 0)) {
                throw new Exception(__('Graduation results are not yet available.', 'vixelcbt'));
            }
            
            global $wpdb;
            
            // Search user based on method
            if ($method === 'nisn') {
                $user_query = $wpdb->prepare(
                    "SELECT u.*, ue.* FROM {$wpdb->users} u 
                     JOIN {$wpdb->prefix}vxl_users_ext ue ON u.ID = ue.user_id 
                     WHERE ue.nisn = %s",
                    $search_key
                );
            } else {
                $user_query = $wpdb->prepare(
                    "SELECT u.*, ue.* FROM {$wpdb->users} u 
                     JOIN {$wpdb->prefix}vxl_users_ext ue ON u.ID = ue.user_id 
                     WHERE ue.nomor_peserta = %s",
                    $search_key
                );
            }
            
            $user_data = $wpdb->get_row($user_query);
            
            if (!$user_data) {
                throw new Exception(__('Data not found.', 'vixelcbt'));
            }
            
            // Get graduation data
            $graduations = $wpdb->get_results($wpdb->prepare(
                "SELECT k.*, m.nama as modul_nama, s.nama as sesi_nama, a.total_skor
                 FROM {$wpdb->prefix}vxl_kelulusan k
                 JOIN {$wpdb->prefix}vxl_modul m ON k.modul_id = m.modul_id
                 JOIN {$wpdb->prefix}vxl_sesi s ON k.sesi_id = s.sesi_id
                 LEFT JOIN {$wpdb->prefix}vxl_attempt a ON k.user_id = a.user_id AND k.modul_id = a.modul_id AND k.sesi_id = a.sesi_id AND a.status IN ('submitted', 'autosubmitted')
                 WHERE k.user_id = %d AND k.status IN ('lulus', 'tidak_lulus')
                 ORDER BY k.verified_at DESC",
                $user_data->ID
            ));
            
            if (!$graduations) {
                throw new Exception(__('No graduation results found.', 'vixelcbt'));
            }
            
            // Generate HTML output
            ob_start();
            ?>
            <div class="graduation-results">
                <div class="participant-info">
                    <h3><?php echo esc_html($user_data->display_name); ?></h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label><?php esc_html_e('NISN:', 'vixelcbt'); ?></label>
                            <span><?php echo esc_html($user_data->nisn); ?></span>
                        </div>
                        <div class="info-item">
                            <label><?php esc_html_e('Nomor Peserta:', 'vixelcbt'); ?></label>
                            <span><?php echo esc_html($user_data->nomor_peserta); ?></span>
                        </div>
                        <div class="info-item">
                            <label><?php esc_html_e('Sekolah:', 'vixelcbt'); ?></label>
                            <span><?php echo esc_html($user_data->sekolah); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="graduation-list">
                    <h4><?php esc_html_e('Hasil Ujian:', 'vixelcbt'); ?></h4>
                    <?php foreach ($graduations as $graduation): ?>
                    <div class="graduation-item status-<?php echo esc_attr($graduation->status); ?>">
                        <div class="exam-info">
                            <h5><?php echo esc_html($graduation->modul_nama); ?></h5>
                            <p><?php esc_html_e('Sesi:', 'vixelcbt'); ?> <?php echo esc_html($graduation->sesi_nama); ?></p>
                            <?php if ($graduation->verified_at): ?>
                                <p><?php esc_html_e('Tanggal Verifikasi:', 'vixelcbt'); ?> <?php echo esc_html(date('d/m/Y', strtotime($graduation->verified_at))); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="result-info">
                            <?php
                            $status_class = $graduation->status === 'lulus' ? 'success' : 'danger';
                            $status_text = $graduation->status === 'lulus' ? __('LULUS', 'vixelcbt') : __('TIDAK LULUS', 'vixelcbt');
                            ?>
                            <div class="status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </div>
                            
                            <?php if ($show === 'detail' && $protect !== 'on' && $graduation->total_skor): ?>
                                <div class="score-info">
                                    <span><?php esc_html_e('Skor:', 'vixelcbt'); ?> <?php echo esc_html($graduation->total_skor); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($show === 'detail'): ?>
                <div class="additional-info">
                    <p><small><?php esc_html_e('Hasil ini telah diverifikasi oleh tim penilai.', 'vixelcbt'); ?></small></p>
                </div>
                <?php endif; ?>
            </div>
            <?php
            $html = ob_get_clean();
            
            // Update rate limit
            $new_attempts = $attempts ? $attempts + 1 : 1;
            set_transient($rate_limit_key, $new_attempts, 300); // 5 minutes
            
            wp_send_json_success(array(
                'html' => $html,
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Verify exam token
     */
    public function verify_token() {
        try {
            if (!is_user_logged_in()) {
                throw new Exception(__('You must be logged in.', 'vixelcbt'));
            }
            
            $token = sanitize_text_field($_POST['token']);
            $sesi_id = intval($_POST['sesi_id']);
            
            if (empty($token) || empty($sesi_id)) {
                throw new Exception(__('Token and session ID are required.', 'vixelcbt'));
            }
            
            global $wpdb;
            
            // Check if token is valid
            $valid_token = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vxl_tokens 
                 WHERE sesi_id = %d AND token = %s AND expired_at > NOW()",
                $sesi_id,
                $token
            ));
            
            if (!$valid_token) {
                throw new Exception(__('Invalid or expired token.', 'vixelcbt'));
            }
            
            wp_send_json_success(array(
                'message' => __('Token verified successfully.', 'vixelcbt'),
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Start exam
     */
    public function exam_start() {
        try {
            if (!is_user_logged_in()) {
                throw new Exception(__('You must be logged in.', 'vixelcbt'));
            }
            
            $user_id = get_current_user_id();
            $modul_id = intval($_POST['modul_id']);
            $sesi_id = intval($_POST['sesi_id']);
            
            // Additional exam start logic would go here
            // Including validation, session checks, question loading, etc.
            
            wp_send_json_success(array(
                'message' => __('Exam started successfully.', 'vixelcbt'),
                'questions' => array(), // Would contain actual questions
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Save exam answer
     */
    public function exam_save_answer() {
        try {
            if (!is_user_logged_in()) {
                throw new Exception(__('You must be logged in.', 'vixelcbt'));
            }
            
            // Implementation for saving individual answers
            wp_send_json_success(array(
                'message' => __('Answer saved.', 'vixelcbt'),
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Submit exam
     */
    public function exam_submit() {
        try {
            if (!is_user_logged_in()) {
                throw new Exception(__('You must be logged in.', 'vixelcbt'));
            }
            
            // Implementation for exam submission
            wp_send_json_success(array(
                'message' => __('Exam submitted successfully.', 'vixelcbt'),
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Get exam state
     */
    public function exam_state() {
        try {
            if (!is_user_logged_in()) {
                throw new Exception(__('You must be logged in.', 'vixelcbt'));
            }
            
            // Implementation for getting exam state
            wp_send_json_success(array(
                'status' => 'running',
                'remaining_time' => 3600,
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Handle file upload
     */
    private function handle_file_upload($file, $user_id, $type) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $validation = vixelcbt_sanitize_file_upload($file);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) use ($user_id, $type) {
                return "vixelcbt_{$type}_{$user_id}_{$name}";
            }
        );
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            return $movefile;
        } else {
            return new WP_Error('upload_error', $movefile['error']);
        }
    }
}