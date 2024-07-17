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

// Hook auipmt_mahasiswaregistration_ajax_handler function to AJAX hooks
add_action('wp_ajax_auipmt_mahasiswaregistration', 'auipmt_mahasiswaregistration_handler');
add_action('wp_ajax_nopriv_auipmt_mahasiswaregistration', 'auipmt_mahasiswaregistration_handler');
// Function to handle AJAX requests from the registration form
function auipmt_mahasiswaregistration_handler() {
    if (isset($_POST['name']) &&isset($_POST['country']) &&isset($_POST['city_of_birth']) &&isset($_POST['date_of_birth']) 
        &&isset($_POST['degree']) &&isset($_POST['faculty']) &&isset($_POST['program']) &&isset($_POST['email']) 
        &&isset($_POST['phone']) &&isset($_FILES['selfie']) &&isset($_FILES['identity']) &&isset($_POST['address'])) {
            
        
            // Store other form data
            $data = [
                'name' => sanitize_text_field($_POST['name']),
                'country' => sanitize_text_field($_POST['country']),
                'city_of_birth' => sanitize_text_field($_POST['city_of_birth']),
                'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
                'degree' => sanitize_text_field($_POST['degree']),
                'faculty' => sanitize_text_field($_POST['faculty']),
                'program' => sanitize_text_field($_POST['program']),
                'email' => sanitize_text_field($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'photo' => $_FILES['selfie'],
                'ktp' => $_FILES['identity'],
                'address' => sanitize_text_field($_POST['address'])
            ];

            //save mahasiswa
            DataMahasiswaHelper::saveDataMahasiswa($data);
        // wp_send_json_success('Pendaftaran berhasil. Email konfirmasi telah dikirim ke '.$name);
    } else {
        wp_send_json_error('Data is not complate');
    }
    wp_die();
}

// Hook the beasiswa_registration_ajax_handler function to AJAX hooks
add_action('wp_ajax_auipmt_initdata', 'auipmt_initdata_handler');
add_action('wp_ajax_nopriv_auipmt_initdata', 'auipmt_initdata_handler');
// Function to handle AJAX requests from the registration form
function auipmt_initdata_handler() {
    $name = 'rio';
    $degrees = DataMahasiswaHelper::getDataDegrees();
    $departments = DataMahasiswaHelper::getDataDepartments();
    $programs = DataMahasiswaHelper::getDataPrograms();
    $countries = DataMahasiswaHelper::getDataCountries();
    
    return wp_send_json(array(
        'countries' => $countries,
        'degrees' => $degrees,
        'departments' => $departments,
        'programs' => $programs
    ));
}


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
        } else if ($_GET['action'] == 'graduated') {
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">Graduated Details</h1>
                <p>Set Graduated Date for <?= $mahasiswa->name ?> (<?= $mahasiswa->nim ?>).</p>
                <form method="get">
                    <input type="date" name="date_of_graduated" required />
                    <input type="hidden" name="page" value="data-mahasiswa" />
                    <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
                    <button type="submit" name="action" value="confirm-graduated-date" class="button button-primary">Yes, Set as Graduated</button>
                    <a href="?page=data-mahasiswa" class="button">Cancel</a>
                </form>
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


