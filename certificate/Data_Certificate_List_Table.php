<?php
// Sertakan file WP_List_Table
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once ABSPATH ."wp-content/plugins/asean-university/data-mahasiswa/data-mahasiswa-helper.php";

// Buat kelas turunan dari WP_List_Table untuk menampilkan data siswa
class Data_Certificate_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'student',   // Label singular untuk item (opsional)
            'plural'   => 'students',  // Label plural untuk item (opsional)
            'ajax'     => false        // Gunakan AJAX untuk pagination (opsional, default false)
        ) );
    }

    // Mendefinisikan kolom-kolom tabel
    public function get_columns() {
        return array(
            // 'cb' => '<input type="checkbox" />', // Checkbox column
            'name'     => 'Nama',
            'nim'      => 'NIM',
            'number_of_graduated'    => 'Certificate Number',
            'degree_name'   => 'Degree',
            'program_title'  => 'Program',
            'status'   => 'Status'
        );
    }
    
    
    // Mendapatkan data untuk ditampilkan dalam tabel
    public function prepare_items() {
        global $auidb;
        $columns = $this->get_columns();
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;
        
        $search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
        
        print_r($searh);

    
        // Query data dari database dengan LIMIT dan OFFSET

        $query = "FROM students s INNER JOIN degrees d on d.id = s.degree_id INNER JOIN programs p ON p.id = s.program_id  WHERE s.status = 'GRADUATED' AND s.deleted_at IS NULL AND (s.nim LIKE %s OR s.email LIKE %s OR s.name LIKE %s) ORDER BY s.created_at";
        $sql_data = $auidb->prepare( "SELECT s.*, d.name as degree_name, p.name as program_title ".$query." DESC LIMIT %d OFFSET %d", '%'.$search.'%',  '%'.$search.'%',  '%'.$search.'%',  $per_page, $offset );
        $data = $auidb->get_results( $sql_data );
        
        $sql_total_items = $auidb->prepare( "SELECT COUNT(*) ".$query, '%'.$search.'%',  '%'.$search.'%',  '%'.$search.'%',  );
        $total_items = $auidb->get_var( $sql_total_items );
    
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, array(), $sortable );
    
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
    
        $this->items = $data;
    }
    
    // Mendefinisikan output untuk setiap kolom
    public function column_default( $item, $column_name ) {
        $file_url = site_url('/wp-content/uploads/temp-files/certificate-' . $item->nim . '-marked.pdf');
        $print_file_url = site_url('/wp-content/uploads/temp-files/certificate-' . $item->nim . '.pdf');
        switch ( $column_name ) {
            case 'name':
                $action_download = sprintf('<a href="?page=sertifikat&action=download&id=%s">Download</a>', $item->id);
                $action_print = sprintf('<a href="?page=sertifikat&action=print&id=%s">Print</a>', $item->id);
                // $download = '<a href='.esc_url($file_url).' download>Download</a>';
                // $download_for_print = '<a href='.esc_url($print_file_url).' download>Download For Print</a>';
                $actions = ['action_download' => $action_download, 'action_print' => $action_print];
                
                return sprintf('%1$s %2$s', $item->$column_name, $this->row_actions($actions));
            case 'nim':
            case 'degree_name':
            case 'program_title':
            case 'number_of_graduated':
            case 'status':
                return $item->$column_name;
            default:
                return print_r( $item, true ); // Default fallback
        }
    }
}

function certificate_table_action_handler() {
    if (isset($_GET['page']) && $_GET['page'] == 'sertifikat') {
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = $_GET['action'];
            $id = intval($_GET['id']);
            
            // $mahasiswa = DataMahasiswaHelper::getDataMahasiswaById($id);

            // switch ($action) {
            //     case 'download':
            //         // Tambahkan logika untuk mengedit item
            //         DataMahasiswaHelper::generateCertificateDownload($mahasiswa);
            //         break;
            //     case 'confirm-delete':
            //         // Tambahkan logika untuk menghapus item
            //         // table_delete_item($id);
            //         echo '<div class="notice notice-success is-dismissible"><p>Data mahsiswa ' . $mahasiswa->name . '('.$mahasiswa->nim.') Deleted.</p></div>';
            //         break;
            //     case 'confirm-graduated-date':
            //         // table_set_graduated_item($id, $date_of_graduated);
            //         echo '<div class="notice notice-success is-dismissible"><p>Data mahsiswa ' . $mahasiswa->name . '('.$mahasiswa->nim.') set to Graduated.</p></div>';
            //         break;
            //     case 'return-to-the-base':
            //         break;
            //     case 'view':
            //         // Tambahkan logika untuk melihat item
            //         echo '<div class="notice notice-success is-dismissible"><p>Melihat item ' . $id . '.</p></div>';
            //         break;
            // }
        }
    }
}
add_action('admin_init', 'certificate_table_action_handler');