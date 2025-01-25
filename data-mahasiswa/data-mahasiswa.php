<?php
// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function data_mahasiswa_plugin_enqueue_styles() {
    if (isset($_GET['page']) && $_GET['page'] == 'data-mahasiswa') {
        wp_enqueue_style( 'data-mahasiswa-plugin-styles', plugin_dir_url( __FILE__ ) . 'style.css' );
    }
}
add_action( 'admin_enqueue_scripts', 'data_mahasiswa_plugin_enqueue_styles' );

function data_mahasiswa_plugin_enqueue_scripts() {
    if (isset($_GET['page']) && $_GET['page'] == 'data-mahasiswa') {
        wp_enqueue_script( 'data-mahasiswa-plugin-scripts', plugin_dir_url( __FILE__ ) . 'data-mahasiswa.js', array(), false, true );
    }
}
add_action( 'admin_enqueue_scripts', 'data_mahasiswa_plugin_enqueue_scripts' );


require_once("data-mahasiswa-helper.php");
require_once("Data_Mahasiswa_List_Table.php");



/************/
/* REST API */
/************/
// Hook into the REST API initialization action
add_action('rest_api_init', function () {
    register_rest_route('asean-university/v1', '/student', array(
        'methods' => 'GET',
        'callback' => 'get_student_data',
        'permission_callback' => '__return_true',
        'args' => array(
            'nim' => array(
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
                    return is_string($param) && !empty($param);
                }
            ),
        ),
    ));
});
/**
 * Callback function to handle the request
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function get_student_data(WP_REST_Request $request) {
    // Get the NIM from the query parameters
    $nim = $request->get_param('nim');
    
    $student = DataMahasiswaHelper::getDataMahasiswaByNim($nim);
    
    if (null === $student) {
        return new WP_REST_Response(array('message' => 'Student not found'), 404);
    }
    
    return new WP_REST_Response($student, 200);
}



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
				'last_certification' => $_FILES['last_certification'],
                'address' => sanitize_text_field($_POST['address'])
            ];
	

            //save mahasiswa
            try {
                DataMahasiswaHelper::saveDataMahasiswa($data);
            } catch (Exception $e) {
                http_response_code(400);
                wp_send_json_error($e->getMessage());
            }
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
            // print_r($mahasiswa);

            if($mahasiswa->degree_id == 6 && $mahasiswa->department_id == 81) { ?>
                <div class="wrap">
                    <h1 class="wp-heading-inline">Graduated Details</h1>
                    <p>Set Graduated Date for <?= $mahasiswa->name ?> (<?= $mahasiswa->nim ?>).</p>
                    <form method="get">
                        <div class="form-group">
                            <label for="date_of_registered">Register Date:</label>
                            <input type="date" name="date_of_registered" id="date_of_registered" value="<?= $mahasiswa->date_of_registered ?>" required />
                        </div>
                        <div class="form-group">
                            <label for="date_of_graduated">Graduate Date:</label>
                            <input type="date" name="date_of_graduated" id="date_of_graduated" required />
                        </div>
                        <div class="form-group">
                            <label for="listening_comprehension">Listening Comprehension:</label>
                            <input type="number" name="listening_comprehension" id="listening_comprehension" required />
                        </div>
                        <div class="form-group">
                            <label for="structure_written_expression">Structure And Written Expression:</label>
                            <input type="number" name="structure_written_expression" id="structure_written_expression" required />
                        </div>
                        <div class="form-group">
                            <label for="reading_comprehension">Reading Comprehension:</label>
                            <input type="number" name="reading_comprehension" id="reading_comprehension" required />
                        </div>
                        <div class="form-group">
                            <label for="total">Total:</label>
                            <input type="number" name="total" id="total" readonly />
                        </div>
                        <input type="hidden" name="page" value="data-mahasiswa" />
                        <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>" />
                        <div class="form-actions">
                            <button type="submit" name="action" value="confirm-graduated-date" class="button button-primary">Yes, Set as Graduated</button>
                            <a href="?page=data-mahasiswa" class="button">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php } else { ?>
                <div class="wrap">
                    <h1 class="wp-heading-inline">Graduated Details</h1>
                    <p>Set Graduated Date for <?= $mahasiswa->name ?> (<?= $mahasiswa->nim ?>).</p>
                    <form method="get">
                        Register Date : <input type="date" name="date_of_registered" value="<?= $mahasiswa->date_of_registered ?>" required />
                        Graduate Date : <input type="date" name="date_of_graduated" required />
                        <input type="hidden" name="page" value="data-mahasiswa" />
                        <input type="hidden" name="id" value="<?php echo esc_attr($id); ?>">
                        <button type="submit" name="action" value="confirm-graduated-date" class="button button-primary">Yes, Set as Graduated</button>
                        <a href="?page=data-mahasiswa" class="button">Cancel</a>
                    </form>
                </div>
            <?php } ?>
            <?php
            return;
        }
    }
?>
    <!-- <style>
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
</style> -->

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