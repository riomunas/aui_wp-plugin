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
            'phone'    => 'Phone',
            'status'   => 'Status',
			'certificate' => 'L.Certificate',
			'ktp' => 'Identity',
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
//                 $quick_edit_link = sprintf('<a href="#" class="quick-edit" data-id="%s">Quick Edit</a>', $item->id);
				$set_to_graduated_link = sprintf('<a href="?page=data-mahasiswa&action=graduated&id=%s">Set To Graduated</a>', $item->id);
                $set_to_registered_link = sprintf('<a href="?page=data-mahasiswa&action=registered&id=%s">Set To Registered</a>', $item->id);
                $delete_link = sprintf('<a href="?page=data-mahasiswa&action=delete&id=%s">Delete</a>', $item->id);
// 				$actions = ['quick_edit' => $quick_edit_link];
				$actions = [];
				if ($item->status == 'PENDING') {
                   $actions['set_to_registered_link'] = $set_to_registered_link;
                   $actions['delete'] = $delete_link;
                } else if ($item->status == 'REGISTERED') {
                   $actions['set_to_graduated'] = $set_to_graduated_link;
                   $actions['delete'] = $delete_link;
                } else {
                   $actions['delete'] = $delete_link;
                }
				
                return sprintf('%1$s %2$s', $item->$column_name, $this->row_actions($actions));
            case 'nim':
            case 'degree_name':
            case 'program_title':
            case 'email':
            case 'phone':
            case 'status':
                return $item->$column_name;
			case 'certificate':
				if ($item->last_certification_path != null) {
					// Buat URL ke lokasi gambar
					$file_url = wp_upload_dir()['baseurl'] . '/student-photos/'.$item->last_certification_path;

					return '<div class="image-cell">
						<a target="_blank" href="'.esc_url($file_url).'"><i class="wp-menu-image dashicons-before dashicons-admin-media"></i></a>
                  	</div>';
				} else {
					return '-';
				}
			case 'ktp':
				if ($item->ktp_path != null) {
					// Buat URL ke lokasi gambar
					$file_url = wp_upload_dir()['baseurl'] . '/student-photos/'.$item->ktp_path;

					return '<div class="image-cell">
						<a target="_blank" href="'.esc_url($file_url).'"><i class="wp-menu-image dashicons-before dashicons-admin-media"></i></a>
                  	</div>';
				} else {
					return '-';
				}
            default:
                return print_r( $item, true ); // Default fallback
        }
    }
}

function add_quick_edit_script() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handle Quick Edit link click
            $('.quick-edit').on('click', function(e) {
                e.preventDefault();
                
                // Hide any existing inline edits
                $('.inline-edit-row').hide();
                
                // Get row data
                let row = $(this).closest('tr');
                let studentId = $(this).data('id');
                
                // Insert the quick edit form into the current row
                let quickEditRow = $('#inline-edit').clone(true);
                quickEditRow.find('input.name').val(row.find('.column-name').text().trim());
                quickEditRow.find('input.email').val(row.find('.column-email').text().trim());
                quickEditRow.find('select.status').val(row.find('.column-status').text().trim());
                quickEditRow.attr('data-id', studentId);
                quickEditRow.insertAfter(row).show();
            });
            
            // Handle save action
            $('.quick-edit-row .save').on('click', function() {
                let quickEditRow = $(this).closest('.quick-edit-row');
                let studentId = quickEditRow.data('id');
                let name = quickEditRow.find('input.name').val();
                let email = quickEditRow.find('input.email').val();
                let status = quickEditRow.find('select.status').val();
                
                // Send data via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'quick_edit_student',
                        student_id: studentId,
                        name: name,
                        email: email,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update row with new values
                            let row = $('tr[data-id="' + studentId + '"]');
                            row.find('.column-name').text(name);
                            row.find('.column-email').text(email);
                            row.find('.column-status').text(status);
                            
                            // Remove inline edit form
                            quickEditRow.hide();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            // Handle cancel action
            $('.quick-edit-row .cancel').on('click', function() {
                $(this).closest('.quick-edit-row').hide();
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'add_quick_edit_script');


function add_quick_edit_template() {
    ?>
    <div id="inline-edit" style="display:none;" class="inline-edit-row inline-edit-student quick-edit-row">
        <fieldset class="inline-edit-col-left">
            <div class="inline-edit-col">
                <label>
                    <span class="title">Nama</span>
                    <span class="input-text-wrap"><input type="text" name="name" class="name" value=""></span>
                </label>
                <label>
                    <span class="title">E-Mail</span>
                    <span class="input-text-wrap"><input type="email" name="email" class="email" value=""></span>
                </label>
                <label>
                    <span class="title">Status</span>
                    <span class="input-text-wrap">
                        <select name="status" class="status">
                            <option value="PENDING">Pending</option>
                            <option value="REGISTERED">Registered</option>
                            <option value="GRADUATED">Graduated</option>
                        </select>
                    </span>
                </label>
            </div>
        </fieldset>
        <div class="inline-edit-save submit">
            <button type="button" class="button save button-primary">Update</button>
            <button type="button" class="button cancel">Cancel</button>
        </div>
    </div>
    <?php
}
add_action('admin_footer', 'add_quick_edit_template');



function table_delete_item($id) {
    global $auidb;
    $auidb->update('students', ['deleted_at' => current_time('mysql')], ['id' => $id]);
}

function table_set_graduated_item($id, $date_of_graduated, $date_of_registered) {
    global $auidb;
    $mahasiswa = DataMahasiswaHelper::getDataMahasiswaById($id);
    if ($mahasiswa->status == 'GRADUATED') return;
    
    $result = DataMahasiswaHelper::generateGraduationNumber($mahasiswa, $date_of_graduated);
    DataMahasiswaHelper::generateCertificateForViewImage($mahasiswa);
    $auidb->update('students', [
		'date_of_registered' => $date_of_registered,
        'date_of_graduated' => $date_of_graduated,
        'number_of_graduated' => $result->number_of_graduated,
        'status' => 'GRADUATED'
    ], ['id' => $id]);
}

function table_set_registered_item($id) {
    global $auidb;
    $mahasiswa = DataMahasiswaHelper::getDataMahasiswaById($id);
    if ($mahasiswa->status == 'GRADUATED' || $mahasiswa->status == 'REGISTERED') return;
    
    $auidb->update('students', [
        'status' => 'REGISTERED'
    ], ['id' => $id]);
	
	// Kirim email
	$to = $mahasiswa->email;
	$subject = '[Asean University International] Payment Thank You E-Mail';
	$headers = 'From: Admin <admin@asean-university.com>' . "\r\n";
	$headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

	$email_template = file_get_contents(plugin_dir_path(__FILE__).'email-konfirmasi-pembayaran-template.html');
	$email_template = str_replace('{{name}}', ucwords($mahasiswa->name), $email_template);
	$email_template = str_replace('{{link}}', 'https://asean-university.com/search-mahasiswa/?search_keyword='.$mahasiswa->email, $email_template);
	wp_mail($to, $subject, $email_template, $headers);
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
            $date_of_registered = $_GET['date_of_registered'];
            $date_of_graduated = $_GET['date_of_graduated'];
            
            $mahasiswa = DataMahasiswaHelper::getDataMahasiswaById($id);

            switch ($action) {
                case 'update':
                    // Tambahkan logika untuk mengedit item
                    echo '<div class="notice notice-success is-dismissible"><p>Item ' . $id . ' diedit.</p></div>';
                    break;
                case 'confirm-delete':
                    // Tambahkan logika untuk menghapus item
                    table_delete_item($id);
                    echo '<div class="notice notice-success is-dismissible"><p>Data mahsiswa ' . $mahasiswa->name . '('.$mahasiswa->nim.') Deleted.</p></div>';
                    break;
                case 'confirm-graduated-date':
                    table_set_graduated_item($id, $date_of_graduated, $date_of_registered);
                    echo '<div class="notice notice-success is-dismissible"><p>Data mahsiswa ' . $mahasiswa->name . '('.$mahasiswa->nim.') set to Graduated.</p></div>';
                    break;
                case 'registered':
                    table_set_registered_item($id);
                    echo '<div class="notice notice-success is-dismissible"><p>Data mahsiswa ' . $mahasiswa->name . '('.$mahasiswa->nim.') set to Registed.</p></div>';
                    break;
                case 'return-to-the-base':
                    break;
                case 'view':
                    // Tambahkan logika untuk melihat item
                    // show_student_details($mahasiswa);
                    echo '<div class="notice notice-success is-dismissible"><p>Melihat item ' . $id . '.</p></div>';
                    break;
            }
        }
    }
}
add_action('admin_init', 'custom_media_table_action_handler');

