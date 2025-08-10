<?php
/**
 * Export Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_Export {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('wp_ajax_vixelcbt_export_results', array($this, 'export_results'));
        add_action('wp_ajax_vixelcbt_export_participants', array($this, 'export_participants'));
        add_action('wp_ajax_vixelcbt_export_questions', array($this, 'export_questions'));
    }
    
    /**
     * Export exam results to Excel/CSV
     */
    public function export_results() {
        if (!current_user_can('vixelcbt_export_data')) {
            wp_die(__('You do not have permission to export data.', 'vixelcbt'));
        }
        
        vixelcbt_verify_nonce($_POST['nonce'], 'vixelcbt_admin_nonce');
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $modul_id = intval($_POST['modul_id'] ?? 0);
        $sesi_id = intval($_POST['sesi_id'] ?? 0);
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        
        global $wpdb;
        
        // Build query
        $query = "
            SELECT 
                u.display_name as nama,
                ue.nisn,
                ue.nomor_peserta,
                ue.sekolah,
                ue.jurusan,
                ue.kelas,
                ue.kelompok,
                ue.kota,
                ue.provinsi,
                ue.phone,
                m.nama as modul_nama,
                s.nama as sesi_nama,
                s.tanggal,
                s.jam_mulai,
                a.start_at,
                a.end_at,
                a.skor_auto,
                a.skor_manual,
                a.total_skor,
                a.status as attempt_status,
                k.status as kelulusan_status,
                k.verified_at,
                k.catatan
            FROM {$wpdb->prefix}vxl_attempt a
            JOIN {$wpdb->users} u ON a.user_id = u.ID
            JOIN {$wpdb->prefix}vxl_users_ext ue ON a.user_id = ue.user_id
            JOIN {$wpdb->prefix}vxl_modul m ON a.modul_id = m.modul_id
            JOIN {$wpdb->prefix}vxl_sesi s ON a.sesi_id = s.sesi_id
            LEFT JOIN {$wpdb->prefix}vxl_kelulusan k ON a.user_id = k.user_id AND a.modul_id = k.modul_id AND a.sesi_id = k.sesi_id
            WHERE a.status IN ('submitted', 'autosubmitted')
        ";
        
        $params = array();
        
        if ($modul_id) {
            $query .= " AND a.modul_id = %d";
            $params[] = $modul_id;
        }
        
        if ($sesi_id) {
            $query .= " AND a.sesi_id = %d";
            $params[] = $sesi_id;
        }
        
        if ($date_from) {
            $query .= " AND DATE(a.start_at) >= %s";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $query .= " AND DATE(a.start_at) <= %s";
            $params[] = $date_to;
        }
        
        $query .= " ORDER BY ue.nomor_peserta ASC";
        
        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $results = $wpdb->get_results($query);
        }
        
        if (empty($results)) {
            wp_send_json_error(array(
                'message' => __('No data found to export.', 'vixelcbt'),
            ));
        }
        
        // Generate filename
        $filename = 'hasil-ujian-' . date('Y-m-d-H-i-s');
        
        if ($format === 'excel') {
            $this->export_to_excel($results, $filename);
        } else {
            $this->export_to_csv($results, $filename);
        }
    }
    
    /**
     * Export participants to CSV
     */
    public function export_participants() {
        if (!current_user_can('vixelcbt_export_data')) {
            wp_die(__('You do not have permission to export data.', 'vixelcbt'));
        }
        
        vixelcbt_verify_nonce($_POST['nonce'], 'vixelcbt_admin_nonce');
        
        global $wpdb;
        
        $participants = $wpdb->get_results(
            "SELECT 
                u.display_name as nama,
                u.user_email as email,
                ue.nisn,
                ue.npsn,
                ue.nomor_peserta,
                ue.sekolah,
                ue.jurusan,
                ue.kelas,
                ue.kelompok,
                ue.kota,
                ue.provinsi,
                ue.phone,
                ue.created_at as tanggal_daftar
             FROM {$wpdb->users} u
             JOIN {$wpdb->prefix}vxl_users_ext ue ON u.ID = ue.user_id
             WHERE u.user_status = 0
             ORDER BY ue.nomor_peserta ASC"
        );
        
        if (empty($participants)) {
            wp_send_json_error(array(
                'message' => __('No participants found to export.', 'vixelcbt'),
            ));
        }
        
        $filename = 'data-peserta-' . date('Y-m-d-H-i-s');
        $this->export_to_csv($participants, $filename);
    }
    
    /**
     * Export questions to CSV/JSON
     */
    public function export_questions() {
        if (!current_user_can('vixelcbt_manage_questions')) {
            wp_die(__('You do not have permission to export questions.', 'vixelcbt'));
        }
        
        vixelcbt_verify_nonce($_POST['nonce'], 'vixelcbt_admin_nonce');
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $modul_id = intval($_POST['modul_id'] ?? 0);
        
        global $wpdb;
        
        $query = "
            SELECT 
                s.soal_id,
                m.nama as modul_nama,
                s.tipe,
                s.pertanyaan,
                s.opsi,
                s.jawaban_benar,
                s.bobot,
                s.scope,
                s.urutan,
                s.media_url,
                s.status
            FROM {$wpdb->prefix}vxl_soal s
            JOIN {$wpdb->prefix}vxl_modul m ON s.modul_id = m.modul_id
        ";
        
        if ($modul_id) {
            $questions = $wpdb->get_results($wpdb->prepare($query . " WHERE s.modul_id = %d ORDER BY s.urutan ASC", $modul_id));
        } else {
            $questions = $wpdb->get_results($query . " ORDER BY m.nama ASC, s.urutan ASC");
        }
        
        if (empty($questions)) {
            wp_send_json_error(array(
                'message' => __('No questions found to export.', 'vixelcbt'),
            ));
        }
        
        $filename = 'bank-soal-' . date('Y-m-d-H-i-s');
        
        if ($format === 'json') {
            $this->export_to_json($questions, $filename);
        } else {
            // Flatten the data for CSV
            $flattened = array();
            foreach ($questions as $question) {
                $row = (array) $question;
                
                // Convert JSON fields to readable format
                if ($question->opsi) {
                    $opsi = json_decode($question->opsi, true);
                    if (is_array($opsi)) {
                        foreach ($opsi as $index => $option) {
                            $row['opsi_' . chr(65 + $index)] = $option;
                        }
                    }
                }
                
                if ($question->jawaban_benar) {
                    $jawaban = json_decode($question->jawaban_benar, true);
                    if (is_array($jawaban)) {
                        $row['jawaban_benar'] = implode(', ', $jawaban);
                    }
                }
                
                unset($row['opsi']); // Remove original JSON field
                $flattened[] = (object) $row;
            }
            
            $this->export_to_csv($flattened, $filename);
        }
    }
    
    /**
     * Export data to CSV
     */
    private function export_to_csv($data, $filename) {
        if (empty($data)) {
            return false;
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Get column headers from first row
        $first_row = (array) $data[0];
        $headers = array_keys($first_row);
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            $row_array = (array) $row;
            
            // Clean data for CSV
            foreach ($row_array as $key => $value) {
                if (is_null($value)) {
                    $row_array[$key] = '';
                } elseif (is_array($value) || is_object($value)) {
                    $row_array[$key] = json_encode($value);
                } else {
                    $row_array[$key] = (string) $value;
                }
            }
            
            fputcsv($output, $row_array);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export data to Excel (simplified HTML table format)
     */
    private function export_to_excel($data, $filename) {
        if (empty($data)) {
            return false;
        }
        
        // Set headers for download
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for UTF-8
        echo chr(0xEF).chr(0xBB).chr(0xBF);
        
        // Start HTML table
        echo '<table border="1">';
        
        // Get column headers from first row
        $first_row = (array) $data[0];
        $headers = array_keys($first_row);
        
        // Write headers
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        // Write data rows
        foreach ($data as $row) {
            echo '<tr>';
            $row_array = (array) $row;
            
            foreach ($headers as $header) {
                $value = isset($row_array[$header]) ? $row_array[$header] : '';
                
                if (is_null($value)) {
                    $value = '';
                } elseif (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                } else {
                    $value = (string) $value;
                }
                
                echo '<td>' . htmlspecialchars($value) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
    }
    
    /**
     * Export data to JSON
     */
    private function export_to_json($data, $filename) {
        if (empty($data)) {
            return false;
        }
        
        // Set headers for download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Convert to JSON with pretty printing
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Generate CSV template for participants import
     */
    public function generate_participants_template() {
        $headers = array(
            'nama',
            'email',
            'username',
            'password',
            'nisn',
            'npsn',
            'sekolah',
            'jurusan',
            'kelas',
            'kelompok',
            'kota',
            'provinsi',
            'phone'
        );
        
        // Sample data
        $sample_data = array(
            array(
                'nama' => 'John Doe',
                'email' => 'john.doe@example.com',
                'username' => 'johndoe',
                'password' => 'password123',
                'nisn' => '1234567890',
                'npsn' => '12345678',
                'sekolah' => 'SMA Negeri 1 Jakarta',
                'jurusan' => 'IPA',
                'kelas' => 'XII-A',
                'kelompok' => 'A',
                'kota' => 'Jakarta',
                'provinsi' => 'DKI Jakarta',
                'phone' => '081234567890'
            ),
            array(
                'nama' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'username' => 'janesmith',
                'password' => 'password456',
                'nisn' => '0987654321',
                'npsn' => '87654321',
                'sekolah' => 'SMA Negeri 2 Jakarta',
                'jurusan' => 'IPS',
                'kelas' => 'XII-B',
                'kelompok' => 'B',
                'kota' => 'Jakarta',
                'provinsi' => 'DKI Jakarta',
                'phone' => '081987654321'
            )
        );
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template-import-peserta.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write sample data
        foreach ($sample_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Generate CSV template for questions import
     */
    public function generate_questions_template() {
        $headers = array(
            'modul_nama',
            'tipe',
            'pertanyaan',
            'opsi_a',
            'opsi_b',
            'opsi_c',
            'opsi_d',
            'opsi_e',
            'jawaban_benar',
            'bobot',
            'scope',
            'urutan',
            'media_url'
        );
        
        // Sample data
        $sample_data = array(
            array(
                'modul_nama' => 'Matematika Dasar',
                'tipe' => 'pg',
                'pertanyaan' => 'Berapakah hasil dari 2 + 2?',
                'opsi_a' => '3',
                'opsi_b' => '4',
                'opsi_c' => '5',
                'opsi_d' => '6',
                'opsi_e' => '',
                'jawaban_benar' => 'B',
                'bobot' => '1',
                'scope' => 'both',
                'urutan' => '1',
                'media_url' => ''
            ),
            array(
                'modul_nama' => 'Matematika Dasar',
                'tipe' => 'checkbox',
                'pertanyaan' => 'Pilih bilangan genap berikut:',
                'opsi_a' => '2',
                'opsi_b' => '3',
                'opsi_c' => '4',
                'opsi_d' => '5',
                'opsi_e' => '6',
                'jawaban_benar' => 'A,C,E',
                'bobot' => '2',
                'scope' => 'both',
                'urutan' => '2',
                'media_url' => ''
            ),
            array(
                'modul_nama' => 'Matematika Dasar',
                'tipe' => 'esai',
                'pertanyaan' => 'Jelaskan konsep limit dalam matematika!',
                'opsi_a' => '',
                'opsi_b' => '',
                'opsi_c' => '',
                'opsi_d' => '',
                'opsi_e' => '',
                'jawaban_benar' => '',
                'bobot' => '5',
                'scope' => 'post',
                'urutan' => '3',
                'media_url' => ''
            )
        );
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template-import-soal.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create file pointer
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write sample data
        foreach ($sample_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get export statistics
     */
    public function get_export_stats() {
        global $wpdb;
        
        return array(
            'total_participants' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vxl_users_ext"),
            'total_questions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vxl_soal"),
            'total_attempts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vxl_attempt WHERE status IN ('submitted', 'autosubmitted')"),
            'total_modules' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vxl_modul"),
            'total_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vxl_sesi"),
        );
    }
}