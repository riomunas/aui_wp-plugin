<?php
// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Enqueue Styles and Scripts
function certificate_plugin_enqueue_styles() {
    wp_enqueue_style( 'certificate-plugin-styles', plugin_dir_url( __FILE__ ) . 'style.css' );
}
add_action( 'wp_enqueue_scripts', 'certificate_plugin_enqueue_styles' );

require_once("Data_Certificate_List_Table.php");

function sertifikat_page() {
    if (isset($_GET['page']) && $_GET['page'] == 'sertifikat') {
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = $_GET['action'];
            $id = intval($_GET['id']);
            
            $mahasiswa = DataMahasiswaHelper::getDataMahasiswaById($id);
            
            // print_r($mahasiswa);
            // exit;
            
            $file_name = null;
            
            switch ($action) {
                case 'download':
                    DataMahasiswaHelper::generateCertificateForDownload($mahasiswa);
                    $file_name = 'certificate-'. $mahasiswa->nim .'.pdf';
                    break;
                case 'print':
                    DataMahasiswaHelper::generateCertificateForPrint($mahasiswa);
                    $file_name = 'certificate-'. $mahasiswa->nim .'-print.pdf';
                    break;
            }
            
            $pdf_file = site_url('/wp-content/uploads/temp-files/'.$file_name);
            ?>
            <script>
                window.onload = function() {
                var link = document.createElement('a');
                link.href = "<?= $pdf_file ?>";
                link.setAttribute('download', '<?= $file_name ?>');
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.location.replace('<?= admin_url(); ?>admin.php?page=sertifikat');
            };
            </script>
            <?php
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
    
    <h2 class="wp-heading-inline">Graduated Student List</h2>
    <div class="tablenav top">
        <form method="get" action="admin.php?page=sertifikat">
            <input type="hidden" name="page" value="sertifikat" />
            
            <label for="search" class="screen-reader-text">Search :</label>
            <input type="text" id="search" name="search" value="<?= esc_attr( $_GET['search'] ?? '' ) ?>" placeholder="Search by NIM, Nama, Email" />
            <input type="submit" name="submit" id="search-submit" class="button" value="Search" />
    
            <?php
                $student_list_table = new Data_Certificate_List_Table();
                $student_list_table->prepare_items();
                $student_list_table->display();
            ?>
        </form>        
    </div>
    
</div>
<?php
}
?>