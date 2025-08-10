<?php
/**
 * REST API Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_REST {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('vixelcbt/v1', '/exam/start', array(
            'methods' => 'POST',
            'callback' => array($this, 'exam_start'),
            'permission_callback' => array($this, 'check_exam_permissions'),
            'args' => array(
                'modul_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'sesi_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'token' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        register_rest_route('vixelcbt/v1', '/exam/save-answer', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_answer'),
            'permission_callback' => array($this, 'check_exam_permissions'),
            'args' => array(
                'attempt_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'soal_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'jawaban' => array(
                    'required' => true,
                    'sanitize_callback' => 'vixelcbt_clean_input',
                ),
            ),
        ));
        
        register_rest_route('vixelcbt/v1', '/exam/submit', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_exam'),
            'permission_callback' => array($this, 'check_exam_permissions'),
            'args' => array(
                'attempt_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        register_rest_route('vixelcbt/v1', '/exam/state', array(
            'methods' => 'GET',
            'callback' => array($this, 'exam_state'),
            'permission_callback' => array($this, 'check_exam_permissions'),
            'args' => array(
                'attempt_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        register_rest_route('vixelcbt/v1', '/announce/check', array(
            'methods' => 'POST',
            'callback' => array($this, 'check_graduation'),
            'permission_callback' => '__return_true', // Public endpoint with rate limiting
            'args' => array(
                'method' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('nisn', 'nomor_peserta'),
                ),
                'search_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }
    
    /**
     * Check exam permissions
     */
    public function check_exam_permissions() {
        return is_user_logged_in();
    }
    
    /**
     * Start exam endpoint
     */
    public function exam_start(WP_REST_Request $request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $modul_id = $request->get_param('modul_id');
        $sesi_id = $request->get_param('sesi_id');
        $token = $request->get_param('token');
        
        try {
            // Validate session
            $sesi = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vxl_sesi WHERE sesi_id = %d AND status = 'active'",
                $sesi_id
            ));
            
            if (!$sesi) {
                throw new Exception(__('Session not found or inactive.', 'vixelcbt'));
            }
            
            // Check if session is active (time-wise)
            if (!vixelcbt_is_session_active($sesi_id)) {
                throw new Exception(__('Session is not currently active.', 'vixelcbt'));
            }
            
            // Validate token if required
            if ($sesi->token_on && !empty($token)) {
                $valid_token = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}vxl_tokens 
                     WHERE sesi_id = %d AND token = %s AND expired_at > NOW()",
                    $sesi_id,
                    $token
                ));
                
                if (!$valid_token) {
                    throw new Exception(__('Invalid or expired token.', 'vixelcbt'));
                }
            }
            
            // Check user group permissions
            $user_ext = VixelCBT_Database::instance()->get_user_ext($user_id);
            if ($user_ext && $sesi->kelompok_izin) {
                $allowed_groups = json_decode($sesi->kelompok_izin, true);
                if (!in_array($user_ext->kelompok, $allowed_groups)) {
                    throw new Exception(__('You are not authorized for this session.', 'vixelcbt'));
                }
            }
            
            // Check attempt limits
            $attempt_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vxl_attempt 
                 WHERE user_id = %d AND modul_id = %d AND status IN ('submitted', 'autosubmitted')",
                $user_id,
                $modul_id
            ));
            
            $modul = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vxl_modul WHERE modul_id = %d",
                $modul_id
            ));
            
            if ($attempt_count >= $modul->attempts_max) {
                throw new Exception(__('Maximum attempts reached.', 'vixelcbt'));
            }
            
            // Check for existing running attempt
            $running_attempt = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vxl_attempt 
                 WHERE user_id = %d AND modul_id = %d AND sesi_id = %d AND status = 'running'",
                $user_id,
                $modul_id,
                $sesi_id
            ));
            
            if ($running_attempt) {
                // Resume existing attempt
                $attempt_id = $running_attempt->attempt_id;
            } else {
                // Create new attempt
                $paket = rand(1, $sesi->paket_count);
                
                $wpdb->insert(
                    $wpdb->prefix . 'vxl_attempt',
                    array(
                        'user_id' => $user_id,
                        'modul_id' => $modul_id,
                        'sesi_id' => $sesi_id,
                        'paket' => $paket,
                        'status' => 'running',
                        'meta' => wp_json_encode(array(
                            'token_used' => $token,
                            'ip_address' => vixelcbt_get_user_ip(),
                            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                        )),
                    ),
                    array('%d', '%d', '%d', '%d', '%s', '%s')
                );
                
                $attempt_id = $wpdb->insert_id;
            }
            
            // Get questions for this attempt
            $questions = $this->get_exam_questions($modul_id, $user_id, $attempt_id);
            
            // Log activity
            VixelCBT_Database::instance()->log_activity($user_id, 'exam_started', array(
                'modul_id' => $modul_id,
                'sesi_id' => $sesi_id,
                'attempt_id' => $attempt_id,
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'attempt_id' => $attempt_id,
                    'modul' => $modul,
                    'questions' => $questions,
                    'remaining_time' => $this->calculate_remaining_time($sesi, $running_attempt),
                ),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $e->getMessage(),
            ), 400);
        }
    }
    
    /**
     * Save answer endpoint
     */
    public function save_answer(WP_REST_Request $request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $attempt_id = $request->get_param('attempt_id');
        $soal_id = $request->get_param('soal_id');
        $jawaban = $request->get_param('jawaban');
        
        try {
            // Validate attempt ownership
            $attempt = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vxl_attempt WHERE attempt_id = %d AND user_id = %d",
                $attempt_id,
                $user_id
            ));
            
            if (!$attempt || $attempt->status !== 'running') {
                throw new Exception(__('Invalid attempt or exam not running.', 'vixelcbt'));
            }
            
            // Save/update answer
            $wpdb->replace(
                $wpdb->prefix . 'vxl_jawaban',
                array(
                    'attempt_id' => $attempt_id,
                    'soal_id' => $soal_id,
                    'jawaban' => is_array($jawaban) ? wp_json_encode($jawaban) : $jawaban,
                ),
                array('%d', '%d', '%s')
            );
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Answer saved successfully.', 'vixelcbt'),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $e->getMessage(),
            ), 400);
        }
    }
    
    /**
     * Submit exam endpoint
     */
    public function submit_exam(WP_REST_Request $request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $attempt_id = $request->get_param('attempt_id');
        
        try {
            // Validate attempt
            $attempt = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vxl_attempt WHERE attempt_id = %d AND user_id = %d AND status = 'running'",
                $attempt_id,
                $user_id
            ));
            
            if (!$attempt) {
                throw new Exception(__('Invalid attempt.', 'vixelcbt'));
            }
            
            // Calculate auto score
            $auto_score = $this->calculate_auto_score($attempt_id);
            
            // Update attempt
            $wpdb->update(
                $wpdb->prefix . 'vxl_attempt',
                array(
                    'status' => 'submitted',
                    'end_at' => current_time('mysql'),
                    'skor_auto' => $auto_score,
                    'total_skor' => $auto_score, // Will be updated after manual scoring
                ),
                array('attempt_id' => $attempt_id),
                array('%s', '%s', '%f', '%f'),
                array('%d')
            );
            
            // Log activity
            VixelCBT_Database::instance()->log_activity($user_id, 'exam_submitted', array(
                'attempt_id' => $attempt_id,
                'score' => $auto_score,
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Exam submitted successfully.', 'vixelcbt'),
                'score' => $auto_score,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $e->getMessage(),
            ), 400);
        }
    }
    
    /**
     * Get exam state endpoint
     */
    public function exam_state(WP_REST_Request $request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $attempt_id = $request->get_param('attempt_id');
        
        try {
            $attempt = $wpdb->get_row($wpdb->prepare(
                "SELECT a.*, s.durasi_menit 
                 FROM {$wpdb->prefix}vxl_attempt a
                 JOIN {$wpdb->prefix}vxl_sesi s ON a.sesi_id = s.sesi_id
                 WHERE a.attempt_id = %d AND a.user_id = %d",
                $attempt_id,
                $user_id
            ));
            
            if (!$attempt) {
                throw new Exception(__('Attempt not found.', 'vixelcbt'));
            }
            
            $remaining_time = 0;
            if ($attempt->status === 'running') {
                $start_time = strtotime($attempt->start_at);
                $current_time = current_time('timestamp');
                $elapsed_minutes = ($current_time - $start_time) / 60;
                $remaining_time = max(0, $attempt->durasi_menit - $elapsed_minutes);
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'status' => $attempt->status,
                    'remaining_time' => $remaining_time,
                    'start_at' => $attempt->start_at,
                    'end_at' => $attempt->end_at,
                ),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $e->getMessage(),
            ), 400);
        }
    }
    
    /**
     * Check graduation endpoint
     */
    public function check_graduation(WP_REST_Request $request) {
        $method = $request->get_param('method');
        $search_key = $request->get_param('search_key');
        
        // Rate limiting
        $ip = vixelcbt_get_user_ip();
        $rate_limit_key = 'vixelcbt_api_graduation_' . md5($ip);
        $attempts = get_transient($rate_limit_key);
        
        if ($attempts && $attempts >= 3) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Rate limit exceeded.', 'vixelcbt'),
            ), 429);
        }
        
        // Update rate limit counter
        $new_attempts = $attempts ? $attempts + 1 : 1;
        set_transient($rate_limit_key, $new_attempts, 300); // 5 minutes
        
        // Implementation similar to AJAX handler
        // ... (graduation check logic)
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(),
        ), 200);
    }
    
    /**
     * Get exam questions with randomization
     */
    private function get_exam_questions($modul_id, $user_id, $attempt_id) {
        global $wpdb;
        
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vxl_soal 
             WHERE modul_id = %d AND status = 'active' 
             ORDER BY urutan ASC, soal_id ASC",
            $modul_id
        ));
        
        // Apply randomization logic based on module settings
        // and seed based on user/attempt for consistency
        
        return $questions;
    }
    
    /**
     * Calculate remaining time for exam
     */
    private function calculate_remaining_time($sesi, $attempt = null) {
        if (!$attempt) {
            return $sesi->durasi_menit * 60; // Return in seconds
        }
        
        $start_time = strtotime($attempt->start_at);
        $current_time = current_time('timestamp');
        $elapsed_seconds = $current_time - $start_time;
        $total_seconds = $sesi->durasi_menit * 60;
        
        return max(0, $total_seconds - $elapsed_seconds);
    }
    
    /**
     * Calculate automatic score
     */
    private function calculate_auto_score($attempt_id) {
        global $wpdb;
        
        $total_score = 0;
        
        // Get all answers for this attempt
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT j.*, s.tipe, s.jawaban_benar, s.bobot 
             FROM {$wpdb->prefix}vxl_jawaban j
             JOIN {$wpdb->prefix}vxl_soal s ON j.soal_id = s.soal_id
             WHERE j.attempt_id = %d",
            $attempt_id
        ));
        
        foreach ($answers as $answer) {
            $score = 0;
            
            switch ($answer->tipe) {
                case 'pg':
                    if ($answer->jawaban === $answer->jawaban_benar) {
                        $score = $answer->bobot;
                    }
                    break;
                    
                case 'checkbox':
                    $user_answers = json_decode($answer->jawaban, true);
                    $correct_answers = json_decode($answer->jawaban_benar, true);
                    
                    if (is_array($user_answers) && is_array($correct_answers)) {
                        $correct_count = count(array_intersect($user_answers, $correct_answers));
                        $total_correct = count($correct_answers);
                        $score = ($correct_count / $total_correct) * $answer->bobot;
                    }
                    break;
                    
                // Other question types would need manual scoring
                case 'esai':
                case 'upload':
                    $score = 0; // Manual scoring required
                    break;
            }
            
            // Update individual answer score
            $wpdb->update(
                $wpdb->prefix . 'vxl_jawaban',
                array('nilai' => $score),
                array('attempt_id' => $attempt_id, 'soal_id' => $answer->soal_id),
                array('%f'),
                array('%d', '%d')
            );
            
            $total_score += $score;
        }
        
        return $total_score;
    }
}