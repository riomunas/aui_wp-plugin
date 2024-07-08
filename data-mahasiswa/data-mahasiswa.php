<?php
// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enqueue Styles and Scripts
function data_mahasiswa_plugin_enqueue_styles() {
    wp_enqueue_style( 'data-mahasiswa-plugin-styles', plugin_dir_url( __FILE__ ) . 'style.css' );
}
add_action( 'wp_enqueue_scripts', 'data_mahasiswa_plugin_enqueue_styles' );

require_once("data-mahasiswa-helper.php");
require_once("Data_Mahasiswa_List_Table.php");

function handle_form_search_mahasiswa_callback($search_keyword) {
    if (!$search_keyword) return;
    
    $mahasiswa = DataMahasiswaHelper::getDataMahasiswa($search_keyword);
    do_action('show_search_mahasiswa_result', $mahasiswa);
}
add_action('handle_form_search_mahasiswa', 'handle_form_search_mahasiswa_callback');


function show_search_mahasiswa_result_callback($mahasiswa) {
    include plugin_dir_path(__FILE__) . '/search-result-mahasiswa-template.php';
}
add_action('show_search_mahasiswa_result', 'show_search_mahasiswa_result_callback');

function data_mahasiswa_page() {
    
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $mahasiswa = DataMahasiswaHelper::getDataMahasiswaById($id);
        
        if ($_GET['action'] == 'delete') {
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">Delete Confirmation</h1>
                <p>Are you sure you want to delete <?= $mahasiswa->name ?> (<?= $mahasiswa->nim ?>) ?</p>
                <form method="get">
                    <input type="hidden" name="page" value="data-mahasiswa" />
                    <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
                    <button type="submit" name="action" value="confirm-delete" class="button button-primary">Yes, Delete</button>
                    <a href="?page=data-mahasiswa" class="button">Cancel</a>
                </form>
            </div>
            <?php
            return; // Menghentikan eksekusi untuk menampilkan form konfirmasi
        } else if ($_GET['action'] == 'edit') {
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">Edit Data Mahasiswa</h1>
                <?= print_r($mahasiswa) ?>
            </div>
            <?php
            return;
        }
    }

?>
    <style>
      @media only screen and (max-width: 768px) {
          form {
              flex-wrap: wrap;
          }

          input[type="text"]#search {
              width: 100%; /* Input mengambil lebar penuh */
              margin-bottom: 10px; /* Menambahkan margin antara input dan tombol pencarian */
          }

          input[type="submit"] {
              width: 100%; /* Tombol pencarian mengambil lebar penuh */
          }
      }

      /* Tata letak untuk layar berukuran besar (misalnya, desktop) */
      @media only screen and (min-width: 769px) {
          input[type="text"]#search {
              width: 350px; /* Mengatur lebar input menjadi 350px */
          }

          input[type="submit"] {
              width: 100px; /* Mengatur lebar tombol pencarian menjadi 100px */
          }
      }
</style>

<div class="wrap">
    
    <h2 class="wp-heading-inline">Student List</h2>
    <div class="tablenav top">
        <form method="get" action="admin.php?page=data-mahasiswa">
            <input type="hidden" name="page" value="data-mahasiswa" />
            
            <label for="search" class="screen-reader-text">Search :</label>
            <input type="text" id="search" name="search" value="<?= esc_attr( $_GET['search'] ?? '' ) ?>" placeholder="Search by NIM, Nama, Email" />
            <input type="submit" name="submit" id="search-submit" class="button" value="Search" />
    
            <?php
                $student_list_table = new Data_Mahasiswa_List_Table();
                $student_list_table->prepare_items();
                $student_list_table->display();
            ?>
        </form>        
    </div>
    
</div>
<?php
}
?>
