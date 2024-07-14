<?php
/**
 * Plugin Name: Asean University International Plugin
 * Description: Simple Manajemen Akademik
 * Version: 1.0
 * Author: dev@asean-univeristy.com
 */
 
 
global $auidb;
$auidb = new wpdb(EXTERNAL_DB_USER, EXTERNAL_DB_PASSWORD, EXTERNAL_DB_NAME, EXTERNAL_DB_HOST);


// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Sertakan file dengan fungsi halaman
include plugin_dir_path( __FILE__ ) . 'data-mahasiswa/data-mahasiswa.php';
// include plugin_dir_path( __FILE__ ) . 'beasiswa/beasiswa.php';
include plugin_dir_path( __FILE__ ) . 'certificate/certificate.php';

// Fungsi untuk menambahkan kapabilitas khusus
function aui_app_add_custom_capabilities() {
    // Menambahkan kapabilitas ke peran administrator
    $administrator = get_role( 'administrator' );
    if ( $administrator ) {
        $administrator->add_cap( 'manage_aui_app' );
    }

    // Menambahkan kapabilitas ke peran admin-akademik
    $admin_akademik = get_role( 'admin-akademik' );
    if ( $admin_akademik ) {
        $admin_akademik->add_cap( 'manage_aui_app' );
    }
}
add_action( 'admin_init', 'aui_app_add_custom_capabilities' );

// Fungsi untuk membuat peran admin-akademik saat plugin diaktifkan
function aui_app_create_roles() {
    add_role(
        'admin-akademik',
        __( 'Admin Akademik' ),
        array(
            'read' => true, // True allows this capability
        )
    );
}
register_activation_hook( __FILE__, 'aui_app_create_roles' );

// Fungsi untuk menambahkan menu dan sub menu
function aui_app_plugin_menu() {
    $capability = 'manage_aui_app'; // Kapabilitas khusus

    // Tambahkan menu utama AUI-App
    add_menu_page(
        'AUI-App',
        'AUI-App',
        $capability,
        'aui-app-menu',
        'aui_app_menu_page',
        'dashicons-building',
        2
    );

    // Tambahkan sub menu Data Mahasiswa
    add_submenu_page(
        'aui-app-menu',
        'Mahasiswa',
        'Mahasiswa',
        $capability,
        'data-mahasiswa',
        'data_mahasiswa_page'
    );

    // Tambahkan sub menu Sertifikat
    add_submenu_page(
        'aui-app-menu',
        'Sertifikat',
        'Sertifikat',
        $capability,
        'sertifikat',
        'sertifikat_page'
    );

    // // Tambahkan sub menu Beasiswa
    // add_submenu_page(
    //     'aui-app-menu',
    //     'Beasiswa',
    //     'Beasiswa',
    //     $capability,
    //     'beasiswa',
    //     'beasiswa_page'
    // );

    // Hapus submenu yang otomatis dibuat untuk halaman utama AUI-App
    remove_submenu_page('aui-app-menu', 'aui-app-menu');
}

// Hook untuk menambahkan menu dan sub menu ke dalam admin menu
add_action( 'admin_menu', 'aui_app_plugin_menu' );

// Tambahkan rewrite rule untuk menangani URL custom sertifikat
function aui_app_rewrite_rules() {
    add_rewrite_rule('^certificate/([^/]*)/?', 'index.php?certificate_number=$matches[1]', 'top'); //untuk sertifikat
    add_rewrite_rule('^search-mahasiswa/?$', 'index.php?search_mahasiswa=1', 'top');
    add_rewrite_rule('^pendaftaran-mahasiswa/?$', 'index.php?pendaftaran_mahasiswa=1', 'top');
}
add_action('init', 'aui_app_rewrite_rules');

// Daftarkan query var baru untuk sertifikat
function aui_app_query_vars($vars) {
    $vars[] = 'certificate_number'; //sertifikat
    $vars[] = 'search_mahasiswa';//pencarian mahasiswa
    $vars[] = 'pendaftaran_mahasiswa';//pencarian mahasiswa
    return $vars;
}
add_filter('query_vars', 'aui_app_query_vars');

// Tangani template redirect untuk sertifikat
function university_app_template_redirect() {
    $certificate_number = get_query_var('certificate_number');
    if ($certificate_number) {
        get_header();
        include plugin_dir_path(__FILE__) . 'certificate/certificate-template.php';
        get_footer();
        exit;
    }
    
    $search_mahasiswa = get_query_var('search_mahasiswa');
    if ($search_mahasiswa) {
        get_header(); 
        include plugin_dir_path(__FILE__) . 'data-mahasiswa/search-mahasiswa-template.php';
        get_footer(); 
        exit;
    }
    
    $pendaftaran_mahasiswa = get_query_var('pendaftaran_mahasiswa');
    if ($pendaftaran_mahasiswa) {
        get_header(); 
        include plugin_dir_path(__FILE__) . 'data-mahasiswa/pendaftaran-mahasiswa-template.php';
        get_footer(); 
        exit;
    }
}
add_action('template_redirect', 'university_app_template_redirect');


// Flush rewrite rules saat plugin diaktifkan
function aui_app_activate() {
    aui_app_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'aui_app_activate');

// Flush rewrite rules saat plugin dinonaktifkan
function aui_app_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'aui_app_deactivate');


// Fungsi callback untuk halaman AUI-App
function aui_app_menu_page() {
    echo '<div class="wrap">';
    echo '<h1>AUI - App</h1>';
    echo '<p>Selamat datang di AUI - App. Allah Maha Baik</p>';
    echo '</div>';
}
