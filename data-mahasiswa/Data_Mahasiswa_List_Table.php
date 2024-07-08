<?php

// Sertakan file WP_List_Table
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once("data-mahasiswa-helper.php");

// Buat kelas turunan dari WP_List_Table untuk menampilkan data siswa
class Data_Mahasiswa_List_Table extends WP_List_Table {

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
            'degree_name'   => 'Degree',
            'program_title'  => 'Program',
            'email'    => 'E-Mail',
            'status'   => 'Status'
        );
    }
    
    // function column_cb($item) {
    //     return sprintf(
    //         '<input type="checkbox" name="bulk-action[]" value="%s" />', $item->id
    //     );
    // }
    
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

        $query = "FROM students s INNER JOIN degrees d on d.id = s.degree_id INNER JOIN programs p ON p.id = s.program_id  WHERE s.deleted_at IS NULL AND (s.nim LIKE %s OR s.email LIKE %s OR s.name LIKE %s) ORDER BY s.created_at";
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
        switch ( $column_name ) {
            case 'name':
                $edit_link = sprintf('<a href="?page=data-mahasiswa&action=edit&id=%s">Edit</a>', $item->id);
                $delete_link = sprintf('<a href="?page=data-mahasiswa&action=delete&id=%s">Delete</a>', $item->id);
                $view_link = sprintf('<a href="?page=data-mahasiswa&action=view&id=%s">View</a>', $item->id);
                $actions = ['edit' => $edit_link, 'view' => $view_link, 'delete' => $delete_link];
                return sprintf('%1$s %2$s', $item->$column_name, $this->row_actions($actions));
            case 'nim':
            case 'degree_name':
            case 'program_title':
            case 'email':
            case 'status':
                return $item->$column_name;
            default:
                return print_r( $item, true ); // Default fallback
        }
    }
}

function table_delete_item($id) {
    global $auidb;
    $auidb->update('students', ['deleted_at' => current_time('mysql')], ['id' => $id]);
}

function show_form_delete($mahasiswa) {
?>
    <form>
        Are you sure want to delete : <?= $mahasiswa->nama ?> (<?= $mahasiswa->nim ?>) ?
        <button type="submit" name="action" value="action-delete">Yes</button>
        <button type="submit" name="action" value="return-to-the-base">Cancel</button>
    </form>
<?php
}

function custom_media_table_action_handler() {
    if (isset($_GET['page']) && $_GET['page'] == 'data-mahasiswa') {
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = $_GET['action'];
            $id = intval($_GET['id']);
            
            $mahasiswa = DataMahasiswaHelper::getDataMahasiswaById($id);

            switch ($action) {
                case 'update':
                    // Tambahkan logika untuk mengedit item
                    echo '<div class="notice notice-success is-dismissible"><p>Item ' . $id . ' diedit.</p></div>';
                    break;
                case 'confirm-delete':
                    // Tambahkan logika untuk menghapus item
                    table_delete_item($id);
                    echo '<div class="notice notice-success is-dismissible"><p>Data mahsiswa ' . $mahasiswa->name . '('.$mahasiswa->nim.') dihapus.</p></div>';
                    break;
                case 'return-to-the-base':
                    break;
                case 'view':
                    // Tambahkan logika untuk melihat item
                    echo '<div class="notice notice-success is-dismissible"><p>Melihat item ' . $id . '.</p></div>';
                    break;
            }
        }
    }
}
add_action('admin_init', 'custom_media_table_action_handler');

