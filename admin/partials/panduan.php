<?php
/**
 * Panduan Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'guide';

?>
<div class="wrap">
    <h1><?php esc_html_e('Panduan VixelCBT', 'vixelcbt'); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=vixelcbt-guide&tab=guide" class="nav-tab <?php echo $tab === 'guide' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Panduan', 'vixelcbt'); ?>
        </a>
        <a href="?page=vixelcbt-guide&tab=generator" class="nav-tab <?php echo $tab === 'generator' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Generator Halaman', 'vixelcbt'); ?>
        </a>
        <a href="?page=vixelcbt-guide&tab=shortcodes" class="nav-tab <?php echo $tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Shortcodes', 'vixelcbt'); ?>
        </a>
        <a href="?page=vixelcbt-guide&tab=faq" class="nav-tab <?php echo $tab === 'faq' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('FAQ', 'vixelcbt'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php
        switch ($tab) {
            case 'generator':
                include VIXELCBT_PLUGIN_PATH . 'admin/partials/panduan-generator.php';
                break;
            case 'shortcodes':
                ?>
                <div class="vixelcbt-guide-content">
                    <h2><?php esc_html_e('Daftar Shortcodes', 'vixelcbt'); ?></h2>
                    
                    <div class="shortcode-section">
                        <h3>[vixelcbt_register]</h3>
                        <p><?php esc_html_e('Menampilkan form pendaftaran peserta.', 'vixelcbt'); ?></p>
                        <p><strong><?php esc_html_e('Atribut:', 'vixelcbt'); ?></strong></p>
                        <ul>
                            <li><code>fields</code> - <?php esc_html_e('Field yang ditampilkan (nisn,sekolah,jurusan,kelas,phone,ktp,kk)', 'vixelcbt'); ?></li>
                        </ul>
                        <p><strong><?php esc_html_e('Contoh:', 'vixelcbt'); ?></strong></p>
                        <code>[vixelcbt_register fields="nisn,sekolah,jurusan,kelas,phone"]</code>
                    </div>
                    
                    <div class="shortcode-section">
                        <h3>[vixelcbt_login]</h3>
                        <p><?php esc_html_e('Menampilkan form login peserta.', 'vixelcbt'); ?></p>
                        <p><strong><?php esc_html_e('Atribut:', 'vixelcbt'); ?></strong></p>
                        <ul>
                            <li><code>redirect</code> - <?php esc_html_e('URL redirect setelah login', 'vixelcbt'); ?></li>
                        </ul>
                        <p><strong><?php esc_html_e('Contoh:', 'vixelcbt'); ?></strong></p>
                        <code>[vixelcbt_login redirect="/dashboard"]</code>
                    </div>
                    
                    <div class="shortcode-section">
                        <h3>[vixelcbt_user_dashboard]</h3>
                        <p><?php esc_html_e('Menampilkan dashboard peserta.', 'vixelcbt'); ?></p>
                        <p><strong><?php esc_html_e('Atribut:', 'vixelcbt'); ?></strong></p>
                        <ul>
                            <li><code>sections</code> - <?php esc_html_e('Bagian yang ditampilkan (profil,riwayat,modul,kelulusan)', 'vixelcbt'); ?></li>
                        </ul>
                        <p><strong><?php esc_html_e('Contoh:', 'vixelcbt'); ?></strong></p>
                        <code>[vixelcbt_user_dashboard sections="profil,riwayat,modul"]</code>
                    </div>
                    
                    <div class="shortcode-section">
                        <h3>[vixelcbt_exam]</h3>
                        <p><?php esc_html_e('Menampilkan interface ujian.', 'vixelcbt'); ?></p>
                        <p><strong><?php esc_html_e('Atribut:', 'vixelcbt'); ?></strong></p>
                        <ul>
                            <li><code>modul</code> - <?php esc_html_e('ID modul ujian', 'vixelcbt'); ?></li>
                            <li><code>sesi</code> - <?php esc_html_e('ID sesi ujian', 'vixelcbt'); ?></li>
                            <li><code>token</code> - <?php esc_html_e('Aktifkan token (on/off)', 'vixelcbt'); ?></li>
                            <li><code>fullscreen</code> - <?php esc_html_e('Mode fullscreen (on/off)', 'vixelcbt'); ?></li>
                        </ul>
                        <p><strong><?php esc_html_e('Contoh:', 'vixelcbt'); ?></strong></p>
                        <code>[vixelcbt_exam modul="1" sesi="1" token="on" fullscreen="on"]</code>
                    </div>
                    
                    <div class="shortcode-section">
                        <h3>[vixelcbt_cek_kelulusan]</h3>
                        <p><?php esc_html_e('Menampilkan form cek kelulusan.', 'vixelcbt'); ?></p>
                        <p><strong><?php esc_html_e('Atribut:', 'vixelcbt'); ?></strong></p>
                        <ul>
                            <li><code>method</code> - <?php esc_html_e('Metode pencarian (nisn/nomor_peserta)', 'vixelcbt'); ?></li>
                            <li><code>show</code> - <?php esc_html_e('Tampilan hasil (status/ringkas/detail)', 'vixelcbt'); ?></li>
                            <li><code>protect</code> - <?php esc_html_e('Proteksi nilai (on/off)', 'vixelcbt'); ?></li>
                        </ul>
                        <p><strong><?php esc_html_e('Contoh:', 'vixelcbt'); ?></strong></p>
                        <code>[vixelcbt_cek_kelulusan method="nisn" show="ringkas" protect="on"]</code>
                    </div>
                </div>
                <?php
                break;
            case 'faq':
                ?>
                <div class="vixelcbt-guide-content">
                    <h2><?php esc_html_e('FAQ & Troubleshooting', 'vixelcbt'); ?></h2>
                    
                    <div class="faq-section">
                        <h3><?php esc_html_e('Q: Token ujian salah terus, bagaimana?', 'vixelcbt'); ?></h3>
                        <p><?php esc_html_e('A: Pastikan sesi ujian sudah aktif dan token belum expired. Cek pengaturan rotate interval di sesi ujian.', 'vixelcbt'); ?></p>
                    </div>
                    
                    <div class="faq-section">
                        <h3><?php esc_html_e('Q: Timer ujian tidak berjalan dengan benar?', 'vixelcbt'); ?></h3>
                        <p><?php esc_html_e('A: Pastikan pengaturan timezone WordPress sudah benar. Timer menggunakan server time sebagai authoritative.', 'vixelcbt'); ?></p>
                    </div>
                    
                    <div class="faq-section">
                        <h3><?php esc_html_e('Q: Autosave tidak bekerja?', 'vixelcbt'); ?></h3>
                        <p><?php esc_html_e('A: Periksa koneksi internet peserta dan pastikan AJAX endpoint tidak diblokir oleh firewall/security plugin.', 'vixelcbt'); ?></p>
                    </div>
                    
                    <div class="faq-section">
                        <h3><?php esc_html_e('Q: Email notifikasi tidak terkirim?', 'vixelcbt'); ?></h3>
                        <p><?php esc_html_e('A: Cek pengaturan SMTP dan gunakan tombol Test Email untuk verifikasi. Pastikan kredensial SMTP benar.', 'vixelcbt'); ?></p>
                    </div>
                    
                    <div class="faq-section">
                        <h3><?php esc_html_e('Q: Bagaimana cara reset attempt peserta?', 'vixelcbt'); ?></h3>
                        <p><?php esc_html_e('A: Buka menu Hasil Ujian, cari peserta yang ingin direset, klik tombol Reset Attempt.', 'vixelcbt'); ?></p>
                    </div>
                    
                    <div class="faq-section">
                        <h3><?php esc_html_e('Q: Export Excel tidak bekerja?', 'vixelcbt'); ?></h3>
                        <p><?php esc_html_e('A: Pastikan memory limit PHP cukup untuk data yang diekspor. Coba filter data untuk mengurangi jumlah record.', 'vixelcbt'); ?></p>
                    </div>
                </div>
                <?php
                break;
            default:
                ?>
                <div class="vixelcbt-guide-content">
                    <h2><?php esc_html_e('Alur Operasional VixelCBT', 'vixelcbt'); ?></h2>
                    
                    <div class="guide-step">
                        <h3>1. <?php esc_html_e('Persiapan Sistem', 'vixelcbt'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Buat kelompok peserta (A, B, C, dst)', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Konfigurasikan pengaturan umum (KKM, timer, keamanan)', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Setup SMTP untuk notifikasi email', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Generate halaman front-end menggunakan Generator', 'vixelcbt'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="guide-step">
                        <h3>2. <?php esc_html_e('Buat Modul & Soal', 'vixelcbt'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Tambah modul ujian dengan pengaturan KKM dan timer', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Input soal ke bank soal (PG, checkbox, esai, dll)', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Set scope soal (pre-test, post-test, atau keduanya)', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Upload materi PDF jika diperlukan', 'vixelcbt'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="guide-step">
                        <h3>3. <?php esc_html_e('Manajemen Peserta', 'vixelcbt'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Import data peserta dari CSV atau input manual', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Verifikasi data profil peserta (NISN, sekolah, dll)', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Generate nomor peserta otomatis', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Assign peserta ke kelompok yang sesuai', 'vixelcbt'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="guide-step">
                        <h3>4. <?php esc_html_e('Penjadwalan Ujian', 'vixelcbt'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Buat sesi ujian dengan tanggal, jam, dan durasi', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Set kapasitas ruang dan kelompok yang diizinkan', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Aktifkan token otomatis atau manual', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Konfigurasikan whitelist IP jika diperlukan', 'vixelcbt'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="guide-step">
                        <h3>5. <?php esc_html_e('Pelaksanaan Ujian', 'vixelcbt'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Cetak kartu peserta dan daftar hadir', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Buka token sesi untuk peserta', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Monitoring real-time peserta yang ujian', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Handle pelanggaran (blur detection, copy-paste)', 'vixelcbt'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="guide-step">
                        <h3>6. <?php esc_html_e('Penilaian & Verifikasi', 'vixelcbt'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Review hasil ujian dan jawaban peserta', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Verifikasi manual untuk soal esai/upload', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Tentukan status kelulusan (lulus/tidak/remedial)', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Buka pengumuman untuk peserta', 'vixelcbt'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="guide-step">
                        <h3>7. <?php esc_html_e('Pelaporan & Dokumentasi', 'vixelcbt'); ?></h3>
                        <ul>
                            <li><?php esc_html_e('Export hasil ujian ke Excel/PDF', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Cetak berita acara dan rekap nilai', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Generate sertifikat untuk yang lulus', 'vixelcbt'); ?></li>
                            <li><?php esc_html_e('Backup data ujian untuk arsip', 'vixelcbt'); ?></li>
                        </ul>
                    </div>
                    
                    <h2><?php esc_html_e('Istilah Penting', 'vixelcbt'); ?></h2>
                    
                    <div class="terms-section">
                        <dl>
                            <dt><strong><?php esc_html_e('Modul', 'vixelcbt'); ?></strong></dt>
                            <dd><?php esc_html_e('Unit ujian yang berisi kumpulan soal dan pengaturan KKM.', 'vixelcbt'); ?></dd>
                            
                            <dt><strong><?php esc_html_e('Sesi', 'vixelcbt'); ?></strong></dt>
                            <dd><?php esc_html_e('Jadwal pelaksanaan ujian dengan waktu dan tempat tertentu.', 'vixelcbt'); ?></dd>
                            
                            <dt><strong><?php esc_html_e('Kelompok', 'vixelcbt'); ?></strong></dt>
                            <dd><?php esc_html_e('Pengelompokan peserta untuk mengatur akses dan jadwal ujian.', 'vixelcbt'); ?></dd>
                            
                            <dt><strong><?php esc_html_e('Token', 'vixelcbt'); ?></strong></dt>
                            <dd><?php esc_html_e('Kode akses untuk memulai ujian yang dapat di-rotate otomatis.', 'vixelcbt'); ?></dd>
                            
                            <dt><strong><?php esc_html_e('Paket Soal', 'vixelcbt'); ?></strong></dt>
                            <dd><?php esc_html_e('Variasi urutan soal untuk menghindari contek-menyontek.', 'vixelcbt'); ?></dd>
                            
                            <dt><strong><?php esc_html_e('Grace Period', 'vixelcbt'); ?></strong></dt>
                            <dd><?php esc_html_e('Waktu tambahan setelah ujian berakhir untuk submit jawaban.', 'vixelcbt'); ?></dd>
                        </dl>
                    </div>
                </div>
                <?php
                break;
        }
        ?>
    </div>
</div>