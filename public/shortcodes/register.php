<?php
/**
 * Shortcodes Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_Shortcodes {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('vixelcbt_register', array($this, 'register_shortcode'));
        add_shortcode('vixelcbt_login', array($this, 'login_shortcode'));
        add_shortcode('vixelcbt_user_dashboard', array($this, 'user_dashboard_shortcode'));
        add_shortcode('vixelcbt_exam', array($this, 'exam_shortcode'));
        add_shortcode('vixelcbt_jadwal', array($this, 'jadwal_shortcode'));
        add_shortcode('vixelcbt_cek_kelulusan', array($this, 'cek_kelulusan_shortcode'));
        add_shortcode('vixelcbt_pengumuman', array($this, 'pengumuman_shortcode'));
    }
    
    /**
     * Registration form shortcode
     */
    public function register_shortcode($atts) {
        $atts = shortcode_atts(array(
            'fields' => 'nisn,sekolah,jurusan,kelas,phone',
        ), $atts, 'vixelcbt_register');
        
        if (is_user_logged_in()) {
            return '<div class="vixelcbt-notice">' . __('Anda sudah terdaftar dan login.', 'vixelcbt') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="vixelcbt-register-form">
            <form id="vixelcbt-register" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('vixelcbt_register', 'vixelcbt_register_nonce'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username"><?php esc_html_e('Username', 'vixelcbt'); ?> <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><?php esc_html_e('Email', 'vixelcbt'); ?> <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password"><?php esc_html_e('Password', 'vixelcbt'); ?> <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password"><?php esc_html_e('Confirm Password', 'vixelcbt'); ?> <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_lengkap"><?php esc_html_e('Nama Lengkap', 'vixelcbt'); ?> <span class="required">*</span></label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" required>
                    </div>
                </div>
                
                <?php 
                $fields = explode(',', $atts['fields']);
                foreach ($fields as $field):
                    $field = trim($field);
                    switch ($field):
                        case 'nisn':
                            ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nisn"><?php esc_html_e('NISN', 'vixelcbt'); ?> <span class="required">*</span></label>
                                    <input type="text" id="nisn" name="nisn" required>
                                </div>
                                <div class="form-group">
                                    <label for="npsn"><?php esc_html_e('NPSN', 'vixelcbt'); ?></label>
                                    <input type="text" id="npsn" name="npsn">
                                </div>
                            </div>
                            <?php
                            break;
                        case 'sekolah':
                            ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="sekolah"><?php esc_html_e('Sekolah', 'vixelcbt'); ?> <span class="required">*</span></label>
                                    <input type="text" id="sekolah" name="sekolah" required>
                                </div>
                            </div>
                            <?php
                            break;
                        case 'jurusan':
                            ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="jurusan"><?php esc_html_e('Jurusan', 'vixelcbt'); ?></label>
                                    <input type="text" id="jurusan" name="jurusan">
                                </div>
                                <div class="form-group">
                                    <label for="kelas"><?php esc_html_e('Kelas', 'vixelcbt'); ?></label>
                                    <input type="text" id="kelas" name="kelas">
                                </div>
                            </div>
                            <?php
                            break;
                        case 'phone':
                            ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone"><?php esc_html_e('No. Telepon', 'vixelcbt'); ?></label>
                                    <input type="tel" id="phone" name="phone">
                                </div>
                            </div>
                            <?php
                            break;
                        case 'ktp':
                            ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="ktp_file"><?php esc_html_e('Upload KTP', 'vixelcbt'); ?></label>
                                    <input type="file" id="ktp_file" name="ktp_file" accept="image/*,.pdf">
                                </div>
                            </div>
                            <?php
                            break;
                        case 'kk':
                            ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="kk_file"><?php esc_html_e('Upload KK', 'vixelcbt'); ?></label>
                                    <input type="file" id="kk_file" name="kk_file" accept="image/*,.pdf">
                                </div>
                            </div>
                            <?php
                            break;
                    endswitch;
                endforeach;
                ?>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <button type="submit" class="btn btn-primary"><?php esc_html_e('Daftar Sekarang', 'vixelcbt'); ?></button>
                    </div>
                </div>
                
                <div class="form-footer">
                    <p><?php esc_html_e('Sudah punya akun?', 'vixelcbt'); ?> <a href="/login"><?php esc_html_e('Login di sini', 'vixelcbt'); ?></a></p>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#vixelcbt-register').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'vixelcbt_register_user');
                
                $.ajax({
                    url: vixelcbt_public.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            window.location.href = '/login';
                        } else {
                            alert(response.data.message || 'Terjadi kesalahan');
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan sistem');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Login form shortcode
     */
    public function login_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '/dashboard',
        ), $atts, 'vixelcbt_login');
        
        if (is_user_logged_in()) {
            return '<div class="vixelcbt-notice">' . __('Anda sudah login.', 'vixelcbt') . ' <a href="/dashboard">' . __('Ke Dashboard', 'vixelcbt') . '</a></div>';
        }
        
        ob_start();
        ?>
        <div class="vixelcbt-login-form">
            <form id="vixelcbt-login" method="post">
                <?php wp_nonce_field('vixelcbt_login', 'vixelcbt_login_nonce'); ?>
                
                <div class="form-group">
                    <label for="username"><?php esc_html_e('Username / Email', 'vixelcbt'); ?></label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php esc_html_e('Password', 'vixelcbt'); ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        <?php esc_html_e('Remember Me', 'vixelcbt'); ?>
                    </label>
                </div>
                
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($atts['redirect']); ?>">
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><?php esc_html_e('Login', 'vixelcbt'); ?></button>
                </div>
                
                <div class="form-footer">
                    <p><a href="/daftar"><?php esc_html_e('Belum punya akun? Daftar di sini', 'vixelcbt'); ?></a></p>
                    <p><a href="<?php echo wp_lostpassword_url(); ?>"><?php esc_html_e('Lupa Password?', 'vixelcbt'); ?></a></p>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#vixelcbt-login').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=vixelcbt_login_user';
                
                $.ajax({
                    url: vixelcbt_public.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.data.redirect || '/dashboard';
                        } else {
                            alert(response.data.message || 'Login gagal');
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan sistem');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * User dashboard shortcode
     */
    public function user_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'sections' => 'profil,riwayat,modul,kelulusan',
        ), $atts, 'vixelcbt_user_dashboard');
        
        if (!is_user_logged_in()) {
            return '<div class="vixelcbt-notice">' . __('Anda harus login terlebih dahulu.', 'vixelcbt') . ' <a href="/login">' . __('Login', 'vixelcbt') . '</a></div>';
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $user_ext = VixelCBT_Database::instance()->get_user_ext($user_id);
        
        $sections = explode(',', $atts['sections']);
        $sections = array_map('trim', $sections);
        
        ob_start();
        ?>
        <div class="vixelcbt-user-dashboard">
            <div class="dashboard-header">
                <h2><?php printf(__('Selamat datang, %s', 'vixelcbt'), esc_html($user->display_name)); ?></h2>
                <div class="user-actions">
                    <a href="<?php echo wp_logout_url(home_url('/login')); ?>" class="btn btn-outline"><?php esc_html_e('Logout', 'vixelcbt'); ?></a>
                </div>
            </div>
            
            <div class="dashboard-tabs">
                <?php foreach ($sections as $section): ?>
                    <button class="tab-btn <?php echo $section === $sections[0] ? 'active' : ''; ?>" 
                            data-tab="<?php echo esc_attr($section); ?>">
                        <?php
                        switch ($section) {
                            case 'profil':
                                esc_html_e('Profil', 'vixelcbt');
                                break;
                            case 'riwayat':
                                esc_html_e('Riwayat Ujian', 'vixelcbt');
                                break;
                            case 'modul':
                                esc_html_e('Modul Tersedia', 'vixelcbt');
                                break;
                            case 'kelulusan':
                                esc_html_e('Status Kelulusan', 'vixelcbt');
                                break;
                        }
                        ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="dashboard-content">
                <?php foreach ($sections as $index => $section): ?>
                    <div class="tab-content <?php echo $index === 0 ? 'active' : ''; ?>" 
                         id="tab-<?php echo esc_attr($section); ?>">
                        <?php
                        switch ($section) {
                            case 'profil':
                                $this->render_profile_section($user, $user_ext);
                                break;
                            case 'riwayat':
                                $this->render_history_section($user_id);
                                break;
                            case 'modul':
                                $this->render_modules_section($user_id);
                                break;
                            case 'kelulusan':
                                $this->render_graduation_section($user_id);
                                break;
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.tab-btn').on('click', function() {
                var tabId = $(this).data('tab');
                
                $('.tab-btn').removeClass('active');
                $('.tab-content').removeClass('active');
                
                $(this).addClass('active');
                $('#tab-' + tabId).addClass('active');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Exam interface shortcode
     */
    public function exam_shortcode($atts) {
        $atts = shortcode_atts(array(
            'modul' => 0,
            'sesi' => 0,
            'token' => 'on',
            'fullscreen' => 'on',
        ), $atts, 'vixelcbt_exam');
        
        if (!is_user_logged_in()) {
            return '<div class="vixelcbt-notice error">' . __('Anda harus login terlebih dahulu.', 'vixelcbt') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="vixelcbt-exam-interface" 
             data-modul="<?php echo esc_attr($atts['modul']); ?>"
             data-sesi="<?php echo esc_attr($atts['sesi']); ?>"
             data-token="<?php echo esc_attr($atts['token']); ?>"
             data-fullscreen="<?php echo esc_attr($atts['fullscreen']); ?>">
            
            <div id="exam-loading" class="exam-screen">
                <h2><?php esc_html_e('Loading...', 'vixelcbt'); ?></h2>
                <div class="loading-spinner"></div>
            </div>
            
            <div id="exam-token" class="exam-screen" style="display:none;">
                <div class="token-form">
                    <h2><?php esc_html_e('Masukkan Token Ujian', 'vixelcbt'); ?></h2>
                    <input type="text" id="exam-token-input" placeholder="<?php esc_attr_e('Token 6-8 digit', 'vixelcbt'); ?>" maxlength="8">
                    <button id="verify-token" class="btn btn-primary"><?php esc_html_e('Verifikasi Token', 'vixelcbt'); ?></button>
                    <div id="token-error" class="error-message"></div>
                </div>
            </div>
            
            <div id="exam-instructions" class="exam-screen" style="display:none;">
                <div class="instructions-content">
                    <h2><?php esc_html_e('Petunjuk Ujian', 'vixelcbt'); ?></h2>
                    <ul>
                        <li><?php esc_html_e('Pastikan koneksi internet stabil', 'vixelcbt'); ?></li>
                        <li><?php esc_html_e('Ujian akan dimulai dalam mode fullscreen', 'vixelcbt'); ?></li>
                        <li><?php esc_html_e('Jawaban akan tersimpan otomatis', 'vixelcbt'); ?></li>
                        <li><?php esc_html_e('Jangan meninggalkan halaman ujian', 'vixelcbt'); ?></li>
                    </ul>
                    <button id="start-exam" class="btn btn-primary btn-lg"><?php esc_html_e('Mulai Ujian', 'vixelcbt'); ?></button>
                </div>
            </div>
            
            <div id="exam-interface" class="exam-screen" style="display:none;">
                <div class="exam-header">
                    <div class="exam-info">
                        <span id="exam-title"></span>
                        <span id="question-counter"></span>
                    </div>
                    <div class="exam-timer">
                        <span id="time-remaining">00:00:00</span>
                    </div>
                </div>
                
                <div class="exam-content">
                    <div class="question-panel">
                        <div id="question-content"></div>
                    </div>
                    
                    <div class="navigation-panel">
                        <button id="prev-question" class="btn btn-outline"><?php esc_html_e('« Sebelumnya', 'vixelcbt'); ?></button>
                        <div class="question-numbers" id="question-numbers"></div>
                        <button id="next-question" class="btn btn-outline"><?php esc_html_e('Selanjutnya »', 'vixelcbt'); ?></button>
                    </div>
                    
                    <div class="exam-actions">
                        <button id="save-answers" class="btn btn-secondary"><?php esc_html_e('Simpan Jawaban', 'vixelcbt'); ?></button>
                        <button id="submit-exam" class="btn btn-primary"><?php esc_html_e('Selesai Ujian', 'vixelcbt'); ?></button>
                    </div>
                </div>
            </div>
            
            <div id="exam-completed" class="exam-screen" style="display:none;">
                <div class="completion-message">
                    <h2><?php esc_html_e('Ujian Selesai', 'vixelcbt'); ?></h2>
                    <p><?php esc_html_e('Terima kasih telah mengikuti ujian. Hasil akan diumumkan setelah proses verifikasi.', 'vixelcbt'); ?></p>
                    <a href="/dashboard" class="btn btn-primary"><?php esc_html_e('Kembali ke Dashboard', 'vixelcbt'); ?></a>
                </div>
            </div>
        </div>
        
        <script src="<?php echo VIXELCBT_PLUGIN_URL; ?>public/js/exam-interface.js"></script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Schedule shortcode
     */
    public function jadwal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'kelompok' => '',
            'range' => '',
        ), $atts, 'vixelcbt_jadwal');
        
        global $wpdb;
        
        $query = "SELECT s.*, m.nama as modul_nama 
                  FROM {$wpdb->prefix}vxl_sesi s 
                  JOIN {$wpdb->prefix}vxl_modul m ON s.modul_id = m.modul_id 
                  WHERE s.status = 'active'";
        
        $query_params = array();
        
        if ($atts['kelompok']) {
            $query .= " AND s.kelompok_izin LIKE %s";
            $query_params[] = '%"' . $atts['kelompok'] . '"%';
        }
        
        if ($atts['range']) {
            $dates = explode(',', $atts['range']);
            if (count($dates) === 2) {
                $query .= " AND s.tanggal BETWEEN %s AND %s";
                $query_params[] = trim($dates[0]);
                $query_params[] = trim($dates[1]);
            }
        }
        
        $query .= " ORDER BY s.tanggal ASC, s.jam_mulai ASC";
        
        if (!empty($query_params)) {
            $sessions = $wpdb->get_results($wpdb->prepare($query, $query_params));
        } else {
            $sessions = $wpdb->get_results($query);
        }
        
        ob_start();
        ?>
        <div class="vixelcbt-jadwal">
            <h2><?php esc_html_e('Jadwal Ujian', 'vixelcbt'); ?></h2>
            
            <?php if ($sessions): ?>
            <div class="jadwal-list">
                <?php foreach ($sessions as $session): ?>
                <div class="jadwal-item">
                    <div class="jadwal-date">
                        <div class="date"><?php echo esc_html(date('d', strtotime($session->tanggal))); ?></div>
                        <div class="month"><?php echo esc_html(date('M', strtotime($session->tanggal))); ?></div>
                    </div>
                    <div class="jadwal-info">
                        <h3><?php echo esc_html($session->modul_nama); ?></h3>
                        <p><strong><?php esc_html_e('Sesi:', 'vixelcbt'); ?></strong> <?php echo esc_html($session->nama); ?></p>
                        <p><strong><?php esc_html_e('Waktu:', 'vixelcbt'); ?></strong> 
                           <?php echo esc_html($session->jam_mulai); ?> 
                           (<?php echo esc_html(vixelcbt_format_duration($session->durasi_menit)); ?>)</p>
                        <?php if ($session->ruang): ?>
                        <p><strong><?php esc_html_e('Ruang:', 'vixelcbt'); ?></strong> <?php echo esc_html($session->ruang); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="jadwal-status">
                        <?php if (vixelcbt_is_session_active($session->sesi_id)): ?>
                            <span class="status active"><?php esc_html_e('Aktif', 'vixelcbt'); ?></span>
                        <?php else: ?>
                            <span class="status scheduled"><?php esc_html_e('Terjadwal', 'vixelcbt'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-schedule">
                <p><?php esc_html_e('Tidak ada jadwal ujian tersedia.', 'vixelcbt'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Check graduation shortcode
     */
    public function cek_kelulusan_shortcode($atts) {
        $atts = shortcode_atts(array(
            'method' => 'nisn',
            'show' => 'ringkas',
            'protect' => 'on',
        ), $atts, 'vixelcbt_cek_kelulusan');
        
        ob_start();
        ?>
        <div class="vixelcbt-cek-kelulusan">
            <div class="search-form">
                <h2><?php esc_html_e('Cek Kelulusan', 'vixelcbt'); ?></h2>
                <form id="kelulusan-form" method="post">
                    <?php wp_nonce_field('vixelcbt_cek_kelulusan', 'vixelcbt_cek_kelulusan_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="search_key">
                            <?php 
                            echo $atts['method'] === 'nisn' ? 
                                esc_html__('NISN', 'vixelcbt') : 
                                esc_html__('Nomor Peserta', 'vixelcbt'); 
                            ?>
                        </label>
                        <input type="text" id="search_key" name="search_key" required 
                               placeholder="<?php 
                               echo $atts['method'] === 'nisn' ? 
                                   esc_attr__('Masukkan NISN', 'vixelcbt') : 
                                   esc_attr__('Masukkan Nomor Peserta', 'vixelcbt'); 
                               ?>">
                    </div>
                    
                    <input type="hidden" name="method" value="<?php echo esc_attr($atts['method']); ?>">
                    <input type="hidden" name="show" value="<?php echo esc_attr($atts['show']); ?>">
                    <input type="hidden" name="protect" value="<?php echo esc_attr($atts['protect']); ?>">
                    
                    <button type="submit" class="btn btn-primary"><?php esc_html_e('Cek Kelulusan', 'vixelcbt'); ?></button>
                </form>
            </div>
            
            <div id="kelulusan-result" class="result-container" style="display:none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var searchAttempts = 0;
            var maxAttempts = 5;
            
            $('#kelulusan-form').on('submit', function(e) {
                e.preventDefault();
                
                if (searchAttempts >= maxAttempts) {
                    alert('<?php esc_html_e('Terlalu banyak pencarian. Silakan coba lagi nanti.', 'vixelcbt'); ?>');
                    return;
                }
                
                var formData = $(this).serialize();
                formData += '&action=vixelcbt_check_graduation';
                
                $('#kelulusan-result').html('<div class="loading"><?php esc_html_e('Mencari data...', 'vixelcbt'); ?></div>').show();
                
                $.ajax({
                    url: vixelcbt_public.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#kelulusan-result').html(response.data.html);
                        } else {
                            $('#kelulusan-result').html('<div class="error">' + response.data.message + '</div>');
                        }
                        searchAttempts++;
                    },
                    error: function() {
                        $('#kelulusan-result').html('<div class="error"><?php esc_html_e('Terjadi kesalahan sistem.', 'vixelcbt'); ?></div>');
                        searchAttempts++;
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Announcement shortcode
     */
    public function pengumuman_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'hasil',
            'limit' => 10,
        ), $atts, 'vixelcbt_pengumuman');
        
        // Check if announcements are open
        if (!get_option('vixelcbt_pengumuman_open', 0)) {
            return '<div class="vixelcbt-notice">' . __('Pengumuman belum dibuka.', 'vixelcbt') . '</div>';
        }
        
        global $wpdb;
        
        if ($atts['type'] === 'hasil') {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT k.*, u.display_name, ue.sekolah, m.nama as modul_nama, s.nama as sesi_nama
                 FROM {$wpdb->prefix}vxl_kelulusan k
                 JOIN {$wpdb->users} u ON k.user_id = u.ID
                 JOIN {$wpdb->prefix}vxl_users_ext ue ON k.user_id = ue.user_id
                 JOIN {$wpdb->prefix}vxl_modul m ON k.modul_id = m.modul_id
                 JOIN {$wpdb->prefix}vxl_sesi s ON k.sesi_id = s.sesi_id
                 WHERE k.status IN ('lulus', 'tidak_lulus')
                 ORDER BY k.verified_at DESC
                 LIMIT %d",
                intval($atts['limit'])
            ));
            
            ob_start();
            ?>
            <div class="vixelcbt-pengumuman">
                <h2><?php esc_html_e('Pengumuman Hasil Ujian', 'vixelcbt'); ?></h2>
                
                <?php if ($results): ?>
                <div class="pengumuman-list">
                    <?php foreach ($results as $result): ?>
                    <div class="pengumuman-item status-<?php echo esc_attr($result->status); ?>">
                        <div class="participant-info">
                            <h3><?php echo esc_html($result->display_name); ?></h3>
                            <p><?php echo esc_html($result->sekolah); ?></p>
                        </div>
                        <div class="exam-info">
                            <p><strong><?php esc_html_e('Modul:', 'vixelcbt'); ?></strong> <?php echo esc_html($result->modul_nama); ?></p>
                            <p><strong><?php esc_html_e('Sesi:', 'vixelcbt'); ?></strong> <?php echo esc_html($result->sesi_nama); ?></p>
                        </div>
                        <div class="result-status">
                            <?php
                            $status_class = $result->status === 'lulus' ? 'success' : 'danger';
                            $status_text = $result->status === 'lulus' ? __('LULUS', 'vixelcbt') : __('TIDAK LULUS', 'vixelcbt');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-results">
                    <p><?php esc_html_e('Belum ada pengumuman hasil ujian.', 'vixelcbt'); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();
        }
        
        return '<div class="vixelcbt-notice">' . __('Tipe pengumuman tidak valid.', 'vixelcbt') . '</div>';
    }
    
    /**
     * Render profile section
     */
    private function render_profile_section($user, $user_ext) {
        ?>
        <div class="profile-section">
            <h3><?php esc_html_e('Profil Peserta', 'vixelcbt'); ?></h3>
            
            <div class="profile-grid">
                <div class="profile-item">
                    <label><?php esc_html_e('Nama Lengkap:', 'vixelcbt'); ?></label>
                    <span><?php echo esc_html($user->display_name); ?></span>
                </div>
                
                <div class="profile-item">
                    <label><?php esc_html_e('Email:', 'vixelcbt'); ?></label>
                    <span><?php echo esc_html($user->user_email); ?></span>
                </div>
                
                <?php if ($user_ext): ?>
                <div class="profile-item">
                    <label><?php esc_html_e('NISN:', 'vixelcbt'); ?></label>
                    <span><?php echo esc_html($user_ext->nisn); ?></span>
                </div>
                
                <div class="profile-item">
                    <label><?php esc_html_e('Nomor Peserta:', 'vixelcbt'); ?></label>
                    <span><?php echo esc_html($user_ext->nomor_peserta); ?></span>
                </div>
                
                <div class="profile-item">
                    <label><?php esc_html_e('Sekolah:', 'vixelcbt'); ?></label>
                    <span><?php echo esc_html($user_ext->sekolah); ?></span>
                </div>
                
                <div class="profile-item">
                    <label><?php esc_html_e('Jurusan:', 'vixelcbt'); ?></label>
                    <span><?php echo esc_html($user_ext->jurusan); ?></span>
                </div>
                
                <div class="profile-item">
                    <label><?php esc_html_e('Kelas:', 'vixelcbt'); ?></label>
                    <span><?php echo esc_html($user_ext->kelas); ?></span>
                </div>
                
                <div class="profile-item">
                    <label><?php esc_html_e('Kelompok:', 'vixelcbt'); ?></label>
                    <span><?php echo esc_html($user_ext->kelompok); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render history section
     */
    private function render_history_section($user_id) {
        global $wpdb;
        
        $attempts = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, m.nama as modul_nama, s.nama as sesi_nama
             FROM {$wpdb->prefix}vxl_attempt a
             JOIN {$wpdb->prefix}vxl_modul m ON a.modul_id = m.modul_id
             JOIN {$wpdb->prefix}vxl_sesi s ON a.sesi_id = s.sesi_id
             WHERE a.user_id = %d
             ORDER BY a.start_at DESC",
            $user_id
        ));
        
        ?>
        <div class="history-section">
            <h3><?php esc_html_e('Riwayat Ujian', 'vixelcbt'); ?></h3>
            
            <?php if ($attempts): ?>
            <div class="history-list">
                <?php foreach ($attempts as $attempt): ?>
                <div class="history-item status-<?php echo esc_attr($attempt->status); ?>">
                    <div class="history-info">
                        <h4><?php echo esc_html($attempt->modul_nama); ?></h4>
                        <p><?php esc_html_e('Sesi:', 'vixelcbt'); ?> <?php echo esc_html($attempt->sesi_nama); ?></p>
                        <p><?php esc_html_e('Tanggal:', 'vixelcbt'); ?> <?php echo esc_html(date('d/m/Y H:i', strtotime($attempt->start_at))); ?></p>
                    </div>
                    <div class="history-score">
                        <?php if ($attempt->status !== 'running'): ?>
                            <span class="score"><?php echo esc_html($attempt->total_skor); ?></span>
                            <span class="status"><?php echo esc_html(ucfirst($attempt->status)); ?></span>
                        <?php else: ?>
                            <span class="status running"><?php esc_html_e('Sedang Berlangsung', 'vixelcbt'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-history">
                <p><?php esc_html_e('Belum ada riwayat ujian.', 'vixelcbt'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render modules section
     */
    private function render_modules_section($user_id) {
        global $wpdb;
        
        $modules = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vxl_modul WHERE status = 'active' ORDER BY nama ASC"
        );
        
        ?>
        <div class="modules-section">
            <h3><?php esc_html_e('Modul Tersedia', 'vixelcbt'); ?></h3>
            
            <?php if ($modules): ?>
            <div class="modules-grid">
                <?php foreach ($modules as $module): ?>
                <div class="module-card">
                    <div class="module-info">
                        <h4><?php echo esc_html($module->nama); ?></h4>
                        <p><?php echo esc_html($module->deskripsi); ?></p>
                        <div class="module-details">
                            <span><?php esc_html_e('KKM:', 'vixelcbt'); ?> <?php echo esc_html($module->kkm); ?></span>
                            <span><?php esc_html_e('Max Attempt:', 'vixelcbt'); ?> <?php echo esc_html($module->attempts_max); ?></span>
                        </div>
                    </div>
                    <div class="module-actions">
                        <?php if ($module->pdf_url): ?>
                            <a href="<?php echo esc_url($module->pdf_url); ?>" target="_blank" class="btn btn-outline">
                                <?php esc_html_e('Lihat Materi', 'vixelcbt'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="/mulai-ujian?modul=<?php echo esc_attr($module->modul_id); ?>" class="btn btn-primary">
                            <?php esc_html_e('Mulai Ujian', 'vixelcbt'); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-modules">
                <p><?php esc_html_e('Belum ada modul tersedia.', 'vixelcbt'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render graduation section
     */
    private function render_graduation_section($user_id) {
        global $wpdb;
        
        $graduations = $wpdb->get_results($wpdb->prepare(
            "SELECT k.*, m.nama as modul_nama, s.nama as sesi_nama
             FROM {$wpdb->prefix}vxl_kelulusan k
             JOIN {$wpdb->prefix}vxl_modul m ON k.modul_id = m.modul_id
             JOIN {$wpdb->prefix}vxl_sesi s ON k.sesi_id = s.sesi_id
             WHERE k.user_id = %d
             ORDER BY k.verified_at DESC",
            $user_id
        ));
        
        ?>
        <div class="graduation-section">
            <h3><?php esc_html_e('Status Kelulusan', 'vixelcbt'); ?></h3>
            
            <?php if ($graduations): ?>
            <div class="graduation-list">
                <?php foreach ($graduations as $graduation): ?>
                <div class="graduation-item status-<?php echo esc_attr($graduation->status); ?>">
                    <div class="graduation-info">
                        <h4><?php echo esc_html($graduation->modul_nama); ?></h4>
                        <p><?php esc_html_e('Sesi:', 'vixelcbt'); ?> <?php echo esc_html($graduation->sesi_nama); ?></p>
                        <?php if ($graduation->verified_at): ?>
                            <p><?php esc_html_e('Diverifikasi:', 'vixelcbt'); ?> <?php echo esc_html(date('d/m/Y H:i', strtotime($graduation->verified_at))); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="graduation-status">
                        <?php
                        $status_class = '';
                        $status_text = '';
                        
                        switch ($graduation->status) {
                            case 'lulus':
                                $status_class = 'success';
                                $status_text = __('LULUS', 'vixelcbt');
                                break;
                            case 'tidak_lulus':
                                $status_class = 'danger';
                                $status_text = __('TIDAK LULUS', 'vixelcbt');
                                break;
                            case 'pending':
                                $status_class = 'warning';
                                $status_text = __('PENDING', 'vixelcbt');
                                break;
                        }
                        ?>
                        <span class="status-badge <?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html($status_text); ?>
                        </span>
                        
                        <?php if ($graduation->status === 'lulus'): ?>
                            <a href="#" class="btn btn-sm btn-primary download-certificate" 
                               data-user="<?php echo esc_attr($user_id); ?>"
                               data-modul="<?php echo esc_attr($graduation->modul_id); ?>">
                                <?php esc_html_e('Unduh Sertifikat', 'vixelcbt'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-graduation">
                <p><?php esc_html_e('Belum ada status kelulusan.', 'vixelcbt'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}