<?php
/**
 * PDF Generation Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VixelCBT_PDF {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        // Initialize PDF library if needed
    }
    
    /**
     * Generate participant card
     */
    public function generate_participant_card($user_id) {
        $user = get_user_by('ID', $user_id);
        $user_ext = VixelCBT_Database::instance()->get_user_ext($user_id);
        
        if (!$user || !$user_ext) {
            return false;
        }
        
        // Create PDF content
        $html = $this->get_participant_card_template($user, $user_ext);
        
        return $this->generate_pdf($html, 'kartu-peserta-' . $user_ext->nomor_peserta . '.pdf');
    }
    
    /**
     * Generate attendance list
     */
    public function generate_attendance_list($sesi_id) {
        global $wpdb;
        
        $sesi = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, m.nama as modul_nama 
             FROM {$wpdb->prefix}vxl_sesi s
             JOIN {$wpdb->prefix}vxl_modul m ON s.modul_id = m.modul_id
             WHERE s.sesi_id = %d",
            $sesi_id
        ));
        
        if (!$sesi) {
            return false;
        }
        
        // Get participants for this session
        $participants = $wpdb->get_results($wpdb->prepare(
            "SELECT u.display_name, ue.nomor_peserta, ue.sekolah, ue.kelompok
             FROM {$wpdb->users} u
             JOIN {$wpdb->prefix}vxl_users_ext ue ON u.ID = ue.user_id
             WHERE ue.kelompok IN (SELECT kelompok FROM JSON_TABLE(%s, '$[*]' COLUMNS (kelompok VARCHAR(10) PATH '$')) AS jt)
             ORDER BY ue.nomor_peserta",
            $sesi->kelompok_izin
        ));
        
        $html = $this->get_attendance_list_template($sesi, $participants);
        
        return $this->generate_pdf($html, 'daftar-hadir-' . $sesi->nama . '.pdf');
    }
    
    /**
     * Generate exam report
     */
    public function generate_exam_report($sesi_id) {
        global $wpdb;
        
        $sesi = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, m.nama as modul_nama 
             FROM {$wpdb->prefix}vxl_sesi s
             JOIN {$wpdb->prefix}vxl_modul m ON s.modul_id = m.modul_id
             WHERE s.sesi_id = %d",
            $sesi_id
        ));
        
        if (!$sesi) {
            return false;
        }
        
        // Get exam results
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT u.display_name, ue.nomor_peserta, ue.sekolah, 
                    a.total_skor, k.status, a.start_at, a.end_at
             FROM {$wpdb->prefix}vxl_attempt a
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             JOIN {$wpdb->prefix}vxl_users_ext ue ON a.user_id = ue.user_id
             LEFT JOIN {$wpdb->prefix}vxl_kelulusan k ON a.user_id = k.user_id AND a.modul_id = k.modul_id AND a.sesi_id = k.sesi_id
             WHERE a.sesi_id = %d AND a.status IN ('submitted', 'autosubmitted')
             ORDER BY ue.nomor_peserta",
            $sesi_id
        ));
        
        $html = $this->get_exam_report_template($sesi, $results);
        
        return $this->generate_pdf($html, 'berita-acara-' . $sesi->nama . '.pdf');
    }
    
    /**
     * Generate certificate
     */
    public function generate_certificate($user_id, $modul_id) {
        global $wpdb;
        
        $user = get_user_by('ID', $user_id);
        $user_ext = VixelCBT_Database::instance()->get_user_ext($user_id);
        
        $modul = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vxl_modul WHERE modul_id = %d",
            $modul_id
        ));
        
        $kelulusan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vxl_kelulusan 
             WHERE user_id = %d AND modul_id = %d AND status = 'lulus'",
            $user_id,
            $modul_id
        ));
        
        if (!$user || !$user_ext || !$modul || !$kelulusan) {
            return false;
        }
        
        $html = $this->get_certificate_template($user, $user_ext, $modul, $kelulusan);
        
        return $this->generate_pdf($html, 'sertifikat-' . $user_ext->nomor_peserta . '-' . $modul->nama . '.pdf', 'landscape');
    }
    
    /**
     * Generate PDF from HTML
     */
    private function generate_pdf($html, $filename, $orientation = 'portrait') {
        // Simple HTML to PDF conversion
        // In a real implementation, you would use a library like TCPDF, FPDF, or Dompdf
        
        // For this example, we'll create a simple HTML file that can be printed to PDF
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/vixelcbt-pdfs/';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $html_file = $pdf_dir . str_replace('.pdf', '.html', $filename);
        
        // Add CSS for print
        $full_html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . esc_html($filename) . '</title>
            <style>
                @page {
                    size: A4 ' . $orientation . ';
                    margin: 20mm;
                }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                .logo {
                    max-height: 60px;
                    margin-bottom: 10px;
                }
                .title {
                    font-size: 18px;
                    font-weight: bold;
                    margin: 10px 0;
                }
                .subtitle {
                    font-size: 14px;
                    color: #666;
                }
                .content {
                    margin: 20px 0;
                }
                .participant-info {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 15px 0;
                }
                .info-row {
                    display: flex;
                    margin-bottom: 8px;
                }
                .info-label {
                    width: 150px;
                    font-weight: bold;
                }
                .info-value {
                    flex: 1;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }
                .signature-area {
                    margin-top: 40px;
                    display: flex;
                    justify-content: space-between;
                }
                .signature-box {
                    text-align: center;
                    width: 200px;
                }
                .signature-line {
                    border-top: 1px solid #333;
                    margin-top: 60px;
                    padding-top: 5px;
                }
                .certificate {
                    text-align: center;
                    padding: 40px;
                    border: 5px solid #333;
                    margin: 20px;
                }
                .certificate-title {
                    font-size: 36px;
                    font-weight: bold;
                    margin: 20px 0;
                    color: #2c5aa0;
                }
                .certificate-text {
                    font-size: 16px;
                    margin: 15px 0;
                }
                .certificate-name {
                    font-size: 24px;
                    font-weight: bold;
                    margin: 20px 0;
                    text-decoration: underline;
                }
                @media print {
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            ' . $html . '
        </body>
        </html>';
        
        file_put_contents($html_file, $full_html);
        
        // Return the URL to the HTML file (which can be printed to PDF)
        return $upload_dir['baseurl'] . '/vixelcbt-pdfs/' . str_replace('.pdf', '.html', $filename);
    }
    
    /**
     * Participant card template
     */
    private function get_participant_card_template($user, $user_ext) {
        $logo_url = get_option('vixelcbt_logo_url', '');
        
        return '
        <div class="header">
            ' . ($logo_url ? '<img src="' . esc_url($logo_url) . '" alt="Logo" class="logo">' : '') . '
            <div class="title">KARTU PESERTA UJIAN</div>
            <div class="subtitle">' . esc_html(get_bloginfo('name')) . '</div>
        </div>
        
        <div class="content">
            <div class="participant-info">
                <div class="info-row">
                    <div class="info-label">Nama Lengkap:</div>
                    <div class="info-value">' . esc_html($user->display_name) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nomor Peserta:</div>
                    <div class="info-value">' . esc_html($user_ext->nomor_peserta) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">NISN:</div>
                    <div class="info-value">' . esc_html($user_ext->nisn) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Sekolah:</div>
                    <div class="info-value">' . esc_html($user_ext->sekolah) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Jurusan:</div>
                    <div class="info-value">' . esc_html($user_ext->jurusan) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Kelas:</div>
                    <div class="info-value">' . esc_html($user_ext->kelas) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Kelompok:</div>
                    <div class="info-value">' . esc_html($user_ext->kelompok) . '</div>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <p><strong>Petunjuk:</strong></p>
                <ul>
                    <li>Kartu peserta ini wajib dibawa saat ujian</li>
                    <li>Tunjukkan kartu peserta kepada pengawas</li>
                    <li>Simpan kartu peserta dengan baik</li>
                </ul>
            </div>
        </div>
        
        <div class="signature-area">
            <div class="signature-box">
                <div>Peserta</div>
                <div class="signature-line">(' . esc_html($user->display_name) . ')</div>
            </div>
            <div class="signature-box">
                <div>Petugas</div>
                <div class="signature-line">(...........................)</div>
            </div>
        </div>';
    }
    
    /**
     * Attendance list template
     */
    private function get_attendance_list_template($sesi, $participants) {
        $logo_url = get_option('vixelcbt_logo_url', '');
        
        $html = '
        <div class="header">
            ' . ($logo_url ? '<img src="' . esc_url($logo_url) . '" alt="Logo" class="logo">' : '') . '
            <div class="title">DAFTAR HADIR UJIAN</div>
            <div class="subtitle">' . esc_html(get_bloginfo('name')) . '</div>
        </div>
        
        <div class="content">
            <div class="participant-info">
                <div class="info-row">
                    <div class="info-label">Modul:</div>
                    <div class="info-value">' . esc_html($sesi->modul_nama) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Sesi:</div>
                    <div class="info-value">' . esc_html($sesi->nama) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tanggal:</div>
                    <div class="info-value">' . esc_html(date('d/m/Y', strtotime($sesi->tanggal))) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Waktu:</div>
                    <div class="info-value">' . esc_html($sesi->jam_mulai) . ' (' . esc_html($sesi->durasi_menit) . ' menit)</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Ruang:</div>
                    <div class="info-value">' . esc_html($sesi->ruang) . '</div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nomor Peserta</th>
                        <th>Nama Lengkap</th>
                        <th>Sekolah</th>
                        <th>Kelompok</th>
                        <th style="width: 100px;">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($participants as $index => $participant) {
            $html .= '
                    <tr>
                        <td>' . ($index + 1) . '</td>
                        <td>' . esc_html($participant->nomor_peserta) . '</td>
                        <td>' . esc_html($participant->display_name) . '</td>
                        <td>' . esc_html($participant->sekolah) . '</td>
                        <td>' . esc_html($participant->kelompok) . '</td>
                        <td></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>
        
        <div class="signature-area">
            <div class="signature-box">
                <div>Pengawas 1</div>
                <div class="signature-line">(...........................)</div>
            </div>
            <div class="signature-box">
                <div>Pengawas 2</div>
                <div class="signature-line">(...........................)</div>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Exam report template
     */
    private function get_exam_report_template($sesi, $results) {
        $logo_url = get_option('vixelcbt_logo_url', '');
        
        $html = '
        <div class="header">
            ' . ($logo_url ? '<img src="' . esc_url($logo_url) . '" alt="Logo" class="logo">' : '') . '
            <div class="title">BERITA ACARA UJIAN</div>
            <div class="subtitle">' . esc_html(get_bloginfo('name')) . '</div>
        </div>
        
        <div class="content">
            <div class="participant-info">
                <div class="info-row">
                    <div class="info-label">Modul:</div>
                    <div class="info-value">' . esc_html($sesi->modul_nama) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Sesi:</div>
                    <div class="info-value">' . esc_html($sesi->nama) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Tanggal:</div>
                    <div class="info-value">' . esc_html(date('d/m/Y', strtotime($sesi->tanggal))) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Waktu:</div>
                    <div class="info-value">' . esc_html($sesi->jam_mulai) . ' (' . esc_html($sesi->durasi_menit) . ' menit)</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Jumlah Peserta:</div>
                    <div class="info-value">' . count($results) . ' orang</div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nomor Peserta</th>
                        <th>Nama Lengkap</th>
                        <th>Sekolah</th>
                        <th>Skor</th>
                        <th>Status</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($results as $index => $result) {
            $status_text = '';
            switch ($result->status) {
                case 'lulus':
                    $status_text = 'LULUS';
                    break;
                case 'tidak_lulus':
                    $status_text = 'TIDAK LULUS';
                    break;
                default:
                    $status_text = 'PENDING';
            }
            
            $html .= '
                    <tr>
                        <td>' . ($index + 1) . '</td>
                        <td>' . esc_html($result->nomor_peserta) . '</td>
                        <td>' . esc_html($result->display_name) . '</td>
                        <td>' . esc_html($result->sekolah) . '</td>
                        <td>' . esc_html($result->total_skor) . '</td>
                        <td>' . esc_html($status_text) . '</td>
                        <td>' . esc_html(date('H:i', strtotime($result->start_at))) . '</td>
                        <td>' . esc_html(date('H:i', strtotime($result->end_at))) . '</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div style="margin-top: 30px;">
                <p><strong>Catatan:</strong></p>
                <p>Ujian telah dilaksanakan sesuai dengan jadwal dan prosedur yang ditetapkan. 
                   Tidak ada kendala teknis yang berarti selama pelaksanaan ujian.</p>
            </div>
        </div>
        
        <div class="signature-area">
            <div class="signature-box">
                <div>Ketua Panitia</div>
                <div class="signature-line">(...........................)</div>
            </div>
            <div class="signature-box">
                <div>Pengawas Ujian</div>
                <div class="signature-line">(...........................)</div>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Certificate template
     */
    private function get_certificate_template($user, $user_ext, $modul, $kelulusan) {
        return '
        <div class="certificate">
            <div class="certificate-title">SERTIFIKAT</div>
            <div class="certificate-text">Diberikan kepada:</div>
            <div class="certificate-name">' . esc_html($user->display_name) . '</div>
            <div class="certificate-text">
                Yang telah berhasil menyelesaikan ujian<br>
                <strong>' . esc_html($modul->nama) . '</strong><br>
                dengan hasil memuaskan
            </div>
            <div class="certificate-text" style="margin-top: 30px;">
                Nomor Peserta: ' . esc_html($user_ext->nomor_peserta) . '<br>
                Sekolah: ' . esc_html($user_ext->sekolah) . '<br>
                Tanggal Verifikasi: ' . esc_html(date('d F Y', strtotime($kelulusan->verified_at))) . '
            </div>
            
            <div style="margin-top: 50px; display: flex; justify-content: space-between;">
                <div class="signature-box">
                    <div>Ketua Panitia</div>
                    <div class="signature-line">(...........................)</div>
                </div>
                <div class="signature-box">
                    <div>Direktur</div>
                    <div class="signature-line">(...........................)</div>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Get PDF file URL
     */
    public function get_pdf_url($filename) {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/vixelcbt-pdfs/' . $filename;
    }
    
    /**
     * Clean up old PDF files
     */
    public function cleanup_old_pdfs($days = 30) {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/vixelcbt-pdfs/';
        
        if (!file_exists($pdf_dir)) {
            return;
        }
        
        $files = glob($pdf_dir . '*');
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
}