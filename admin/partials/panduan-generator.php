<?php
/**
 * Page Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle page generation
if (isset($_POST['generate_pages']) && wp_verify_nonce($_POST['vixelcbt_generator_nonce'], 'vixelcbt_generator')) {
    
    vixelcbt_check_capability('manage_options');
    
    $pages_to_create = array(
        'daftar' => array(
            'title' => __('Daftar Peserta', 'vixelcbt'),
            'content' => '[vixelcbt_register fields="nisn,sekolah,jurusan,kelas,phone,ktp,kk"]',
            'slug' => 'daftar'
        ),
        'login' => array(
            'title' => __('Login Peserta', 'vixelcbt'),
            'content' => '[vixelcbt_login redirect="/dashboard"]',
            'slug' => 'login'
        ),
        'dashboard' => array(
            'title' => __('Dashboard Peserta', 'vixelcbt'),
            'content' => '[vixelcbt_user_dashboard sections="profil,riwayat,modul,kelulusan"]',
            'slug' => 'dashboard'
        ),
        'mulai-ujian' => array(
            'title' => __('Mulai Ujian', 'vixelcbt'),
            'content' => '[vixelcbt_exam token="on" fullscreen="on"]',
            'slug' => 'mulai-ujian'
        ),
        'jadwal-ujian' => array(
            'title' => __('Jadwal Ujian', 'vixelcbt'),
            'content' => '[vixelcbt_jadwal]',
            'slug' => 'jadwal-ujian'
        ),
        'cek-kelulusan' => array(
            'title' => __('Cek Kelulusan', 'vixelcbt'),
            'content' => '[vixelcbt_cek_kelulusan method="nisn" show="ringkas" protect="on"]',
            'slug' => 'cek-kelulusan'
        ),
        'pengumuman' => array(
            'title' => __('Pengumuman', 'vixelcbt'),
            'content' => '[vixelcbt_pengumuman type="hasil" limit="10"]',
            'slug' => 'pengumuman'
        )
    );
    
    $created_pages = array();
    $errors = array();
    
    foreach ($pages_to_create as $key => $page_data) {
        if (!isset($_POST['pages'][$key])) {
            continue;
        }
        
        // Check if page already exists
        $existing_page = get_page_by_path($page_data['slug']);
        if ($existing_page) {
            $errors[] = sprintf(__('Halaman "%s" sudah ada.', 'vixelcbt'), $page_data['title']);
            continue;
        }
        
        // Create page
        $page_id = wp_insert_post(array(
            'post_title' => $page_data['title'],
            'post_content' => $page_data['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $page_data['slug'],
            'post_author' => get_current_user_id(),
        ));
        
        if (!is_wp_error($page_id)) {
            $created_pages[] = $page_data['title'];
        } else {
            $errors[] = sprintf(__('Gagal membuat halaman "%s": %s', 'vixelcbt'), $page_data['title'], $page_id->get_error_message());
        }
    }
    
    // Show results
    if ($created_pages) {
        echo '<div class="notice notice-success"><p>' . 
             sprintf(__('Berhasil membuat halaman: %s', 'vixelcbt'), implode(', ', $created_pages)) . 
             '</p></div>';
    }
    
    if ($errors) {
        echo '<div class="notice notice-error"><p>' . implode('<br>', $errors) . '</p></div>';
    }
}

?>

<div class="vixelcbt-generator">
    <h2><?php esc_html_e('Generator Halaman Otomatis', 'vixelcbt'); ?></h2>
    
    <p><?php esc_html_e('Tool ini akan membuat halaman WordPress dengan shortcode VixelCBT yang sudah siap digunakan.', 'vixelcbt'); ?></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('vixelcbt_generator', 'vixelcbt_generator_nonce'); ?>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Pilih', 'vixelcbt'); ?></th>
                    <th><?php esc_html_e('Halaman', 'vixelcbt'); ?></th>
                    <th><?php esc_html_e('Slug', 'vixelcbt'); ?></th>
                    <th><?php esc_html_e('Shortcode', 'vixelcbt'); ?></th>
                    <th><?php esc_html_e('Deskripsi', 'vixelcbt'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <input type="checkbox" name="pages[daftar]" value="1" checked>
                    </td>
                    <td><strong><?php esc_html_e('Daftar Peserta', 'vixelcbt'); ?></strong></td>
                    <td><code>/daftar</code></td>
                    <td><code>[vixelcbt_register]</code></td>
                    <td><?php esc_html_e('Form pendaftaran peserta baru', 'vixelcbt'); ?></td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="pages[login]" value="1" checked>
                    </td>
                    <td><strong><?php esc_html_e('Login Peserta', 'vixelcbt'); ?></strong></td>
                    <td><code>/login</code></td>
                    <td><code>[vixelcbt_login]</code></td>
                    <td><?php esc_html_e('Form login untuk peserta', 'vixelcbt'); ?></td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="pages[dashboard]" value="1" checked>
                    </td>
                    <td><strong><?php esc_html_e('Dashboard Peserta', 'vixelcbt'); ?></strong></td>
                    <td><code>/dashboard</code></td>
                    <td><code>[vixelcbt_user_dashboard]</code></td>
                    <td><?php esc_html_e('Dashboard untuk peserta yang sudah login', 'vixelcbt'); ?></td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="pages[mulai-ujian]" value="1" checked>
                    </td>
                    <td><strong><?php esc_html_e('Mulai Ujian', 'vixelcbt'); ?></strong></td>
                    <td><code>/mulai-ujian</code></td>
                    <td><code>[vixelcbt_exam]</code></td>
                    <td><?php esc_html_e('Interface untuk mengerjakan ujian', 'vixelcbt'); ?></td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="pages[jadwal-ujian]" value="1" checked>
                    </td>
                    <td><strong><?php esc_html_e('Jadwal Ujian', 'vixelcbt'); ?></strong></td>
                    <td><code>/jadwal-ujian</code></td>
                    <td><code>[vixelcbt_jadwal]</code></td>
                    <td><?php esc_html_e('Menampilkan jadwal ujian', 'vixelcbt'); ?></td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="pages[cek-kelulusan]" value="1" checked>
                    </td>
                    <td><strong><?php esc_html_e('Cek Kelulusan', 'vixelcbt'); ?></strong></td>
                    <td><code>/cek-kelulusan</code></td>
                    <td><code>[vixelcbt_cek_kelulusan]</code></td>
                    <td><?php esc_html_e('Form untuk mengecek hasil kelulusan', 'vixelcbt'); ?></td>
                </tr>
                <tr>
                    <td>
                        <input type="checkbox" name="pages[pengumuman]" value="1" checked>
                    </td>
                    <td><strong><?php esc_html_e('Pengumuman', 'vixelcbt'); ?></strong></td>
                    <td><code>/pengumuman</code></td>
                    <td><code>[vixelcbt_pengumuman]</code></td>
                    <td><?php esc_html_e('Halaman pengumuman hasil ujian', 'vixelcbt'); ?></td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" name="generate_pages" class="button button-primary" value="<?php esc_attr_e('Generate Halaman Terpilih', 'vixelcbt'); ?>">
        </p>
    </form>
    
    <div class="generator-notes">
        <h3><?php esc_html_e('Catatan Penting:', 'vixelcbt'); ?></h3>
        <ul>
            <li><?php esc_html_e('Halaman yang sudah ada tidak akan ditimpa.', 'vixelcbt'); ?></li>
            <li><?php esc_html_e('Anda dapat mengedit shortcode dan konten halaman setelah dibuat.', 'vixelcbt'); ?></li>
            <li><?php esc_html_e('Untuk halaman Mulai Ujian, Anda perlu menambahkan parameter modul dan sesi.', 'vixelcbt'); ?></li>
            <li><?php esc_html_e('Sesuaikan menu navigasi WordPress untuk menampilkan halaman-halaman ini.', 'vixelcbt'); ?></li>
        </ul>
    </div>
</div>