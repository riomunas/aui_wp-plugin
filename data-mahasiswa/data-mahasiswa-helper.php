<?php

if ( !defined('EXTERNAL_DB_HOST') || !defined('EXTERNAL_DB_USER') || !defined('EXTERNAL_DB_PASSWORD') || !defined('EXTERNAL_DB_NAME') ) {
    return 'External database constants are not properly defined.';
}

require_once('fpdi/src/autoload.php');

if (!class_exists('TCPDF')) {
    include_once('tcpdf/tcpdf.php');
}

use setasign\Fpdi\Tcpdf\Fpdi;

class DataMahasiswaHelper {

    public static function getDataMahasiswaByGraduatedDate($dateOfgraduated) {
        // Implementasi fungsi pengambilan data mahasiswa berdasarkan tanggal lulus
        // ...
    }
    
    public static function generateGraduationNumber($mahasiswa, $date_of_graduated) {
        global $auidb;
        
        $dateTime = new DateTime($date_of_graduated);
        // Ambil nilai tahun dan bulan
        $year = $dateTime->format('Y');
        $month = $dateTime->format('n');
        
        
        // Mulai transaksi
        $auidb->query('START TRANSACTION');
        
        //cari data counters yang akan di locking kalau tidak nemu insert baru
        $counter = $auidb->get_row($auidb->prepare(
            "SELECT * FROM counters WHERE type = %s AND degree_id = %d AND year = %d AND month = %d FOR UPDATE",
            'CERTIFICATE', $mahasiswa->degree_id, $year, $month
        ));
        
        $counter_id = null;
        
        try {
            if ($counter) {
                // Baris ditemukan, lakukan operasi yang membutuhkan locking di sini
                // Misalnya update counter
                $auidb->update(
                    'counters',
                    array('counter' => $counter->counter + 1), // Update count
                    array('id' => $counter->id)            // Kondisi update
                );
                $counter_id = $counter->id;
            } else {
                // Baris tidak ditemukan, insert baris baru
                $auidb->insert(
                    'counters',
                    array(
                        'type' => 'CERTIFICATE',
                        'degree_id' => $mahasiswa->degree_id,
                        'year' => $year,
                        'month' => $month,
                        'counter' => 1 // Initial count
                    )
                );
                $counter_id = $auidb->insert_id;
            }
            // Komit transaksi
            $auidb->query('COMMIT');
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            $auidb->query('ROLLBACK');
            throw $e;
        }
        
        $number_of_graduated = $auidb->get_row(
            $auidb->prepare("SELECT concat('AUI-GRD/',LPAD(degree_id, 2, '0'), '/', right(year, 2), LPAD(month, 2, '0'), '/', LPAD(counter, 5, '0')) as number_of_graduated  FROM `counters` WHERE id = %d", $counter_id)
        );
        
        return $number_of_graduated;
    }

    public static function getDataMahasiswaById($id) {
        global $auidb;
        
        $mahasiswa = $auidb->get_row($auidb->prepare("
            SELECT 
                s.*, p.name as program_title, s.title_of_graduated as degree_title, s.name_sign_of_graduated as degree_sign_name, s.title_sign_of_graduated as degree_sign_title, 
                concat(s.city_of_birth, ' - ', c.name, ', ', DATE_FORMAT(date_of_birth, '%M %D, %Y')) birth_info,
                DATE_FORMAT(s.date_of_graduated, '%M %D, %Y') as date_of_graduated 
            FROM students s 
            INNER JOIN degrees d on d.id = s.degree_id 
            INNER JOIN programs p on p.id = s.program_id
            INNER JOIN countries c on c.id = s.country_id
            WHERE s.id = %d
        ", $id));
        
        if ($mahasiswa) {
            $setting_name = $auidb->get_row($auidb->prepare("
                SELECT *
                FROM settings s 
                WHERE kode = 0
            "));
            $mahasiswa->sign_certificate_name = $setting_name->keterangan;
            
            
            $setting_title = $auidb->get_row($auidb->prepare("
                SELECT *
                FROM settings s 
                WHERE kode = 1
            "));
            $mahasiswa->sign_certificate_title = $setting_title->keterangan;
        }
        return $mahasiswa;
    }
    
    public static function getDataMahasiswa($keyword) {
        $mydb = new wpdb(EXTERNAL_DB_USER, EXTERNAL_DB_PASSWORD, EXTERNAL_DB_NAME, EXTERNAL_DB_HOST);

        $mahasiswa = $mydb->get_row($mydb->prepare("
            SELECT 
                s.*, p.name as program_title, d.title as degree_title, d.sign_name as degree_sign_name, d.sign_title as degree_sign_title, 
                concat(s.city_of_birth, ' - ', c.name, ', ', DATE_FORMAT(date_of_birth, '%M %D, %Y')) birth_info,
                DATE_FORMAT(s.date_of_graduated, '%M %D, %Y') as date_of_graduated 
            FROM students s 
            INNER JOIN degrees d on d.id = s.degree_id 
            INNER JOIN programs p on p.id = s.program_id
            INNER JOIN countries c on c.id = s.country_id
            WHERE (s.nim = %s OR s.email = %s OR s.number_of_graduated)
        ", $keyword, $keyword));
        
        if ($mahasiswa) {
            $setting_name = $mydb->get_row($mydb->prepare("
                SELECT *
                FROM settings s 
                WHERE kode = 0
            "));
            $mahasiswa->sign_certificate_name = $setting_name->keterangan;
            
            
            $setting_title = $mydb->get_row($mydb->prepare("
                SELECT *
                FROM settings s 
                WHERE kode = 1
            "));
            $mahasiswa->sign_certificate_title = $setting_title->keterangan;
        }
        return $mahasiswa;
    }

    public static function generateCertificateForDownload($mahasiswa) {
        DataMahasiswaHelper::generateCertificate($mahasiswa, 'download');
        DataMahasiswaHelper::generateCertificate($mahasiswa, 'image');
    }
    
    public static function generateCertificateForPrint($mahasiswa) {
        DataMahasiswaHelper::generateCertificate($mahasiswa, 'print');
        DataMahasiswaHelper::generateCertificate($mahasiswa, 'image');
    }
    
    public static function generateCertificateForViewImage($mahasiswa) {
        DataMahasiswaHelper::generateCertificate($mahasiswa, 'image');
    }

    private static function generateCertificate($mahasiswa, $type) {

        // initiate PDF
        $pdf = new Fpdi('L','mm','A4');
        
        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        //add new page
        $pdf->AddPage();
        
        $template_name = 'template.pdf';//download
        
        switch ($type) {
            case 'print': $template_name = 'print-template.pdf'; break;
            case 'image': $template_name = 'water-mark-template.pdf'; break;
        }
        
        $path = plugin_dir_path(__FILE__).$template_name;
        $pdf->setSourceFile($path);
        $tplIdx = $pdf->importPage(1);
        
        $pdf->useTemplate($tplIdx, 0, 0); // Lebar dan tinggi dalam milimeter (215.9x279.4 mm = 8.5x11 inchi)
    
        //qrcode
        $style = array(
            'border' => false,
            'padding' => 0,
            'fgcolor' => array(175, 144, 59),
            'bgcolor' => false
        );
        // QRCODE,H : QR-CODE Best error correction
        $pdf->write2DBarcode('https://asean-university.com/certificate/'.$mahasiswa->nim.'/', 'QRCODE,L', 235.37, 65.43, 40, 40, $style, 'N');
    
        //number certificate
        $pdf->SetFont('helvetica', 'B', 9, '', true);
        $pdf->SetTextColor(10, 81, 130);
        $pdf->SetXY(23.93, 18.77);
        $pdf->Cell(256, 4.97, 'NUMBER : '.$mahasiswa->number_of_graduated, 0, 1,'L');
        
        //set warna font hitam
        $pdf->SetTextColor(0, 0, 0);
        
        //name
        $pdf->SetFont('times', 'B', 18, '', true);
        $pdf->SetXY(20, 83.58);
        $pdf->Cell(256, 7.46, $mahasiswa->name,0, 1,'C');
        
        //dof
        $pdf->SetFont('times', 'I', 12, '', true);
        $pdf->SetXY(20, 89.72);
        $pdf->Cell(256, 4.97, $mahasiswa->birth_info,0, 1,'C');
        
        //student number
        $pdf->SetFont('helvetica', 'B', 12, '', true);
        $pdf->SetXY(20, 94.69);
        $pdf->Cell(256, 4.97, $mahasiswa->nim, 0, 1,'C');
        
        //deggre
        $pdf->SetFont('times', 'B', 18, '', true);
        $pdf->SetXY(20, 115.87);
        $pdf->Cell(256, 4.97, $mahasiswa->degree_title, 0, 1,'C');
        
        //faculty
        $pdf->SetFont('times', 'I', 12, '', true);
        $pdf->SetXY(20, 123.33);
        $pdf->Cell(256, 4.97, $mahasiswa->program_title, 0, 1,'C');
        
        //tanggal 
        $pdf->SetFont('times', '', 12, '', true);
        $pdf->SetXY(20, 154.41);
        $pdf->Cell(256, 4.97, 'Malaysia, '.$mahasiswa->date_of_graduated, 0, 1,'C');
        
        
        
        
        
        
        //atas
        //dekan
        $pdf->SetFont('times', 'BU', 11);
        $pdf->SetXY(20, 171.08);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_name, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 171.08);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_name, 0, 1,'C');
        
        //bawah
        //dekan
        $pdf->SetFont('times', 'I', 11);
        $pdf->SetXY(20, 176.06);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_title, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 176.06);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_title, 0, 1,'C');
        
        
        /*
        'I': Output ke browser. Hasil PDF akan ditampilkan di browser.
        'D': Download file. Hasil PDF akan diunduh oleh pengguna sebagai file.
        'F': Simpan ke file. Hasil PDF akan disimpan ke dalam file di server.
        'S': Mengembalikan data sebagai string. Hasil PDF akan dikembalikan sebagai string.
        */
        
        $file_name = 'certificate-'.$mahasiswa->nim.'.pdf';
        switch ($type) {
            case 'print': $file_name = 'certificate-'.$mahasiswa->nim.'-print.pdf'; break;
            case 'image': $file_name = 'certificate-'.$mahasiswa->nim.'-marked.pdf'; break;
        }
        
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/temp-files/'; // Direktori khusus untuk file sementara
        $file_path = $temp_dir . $file_name; // Path lengkap file
        
        $pdf->Output($file_path, 'F');
        
        if ($type == 'image') {
            $imagick = new Imagick();
            $imagick->readImage($file_path);
            
            // Iterasi melalui setiap halaman PDF
            foreach ($imagick as $index => $page) {
                // Set format gambar (misalnya JPG)
                $page->setImageFormat('jpg');
                
                // Path untuk gambar output
                $outputPath = $temp_dir.'certificate-'.$mahasiswa->nim.'.jpg';
                
                // Simpan gambar
                $page->writeImage($outputPath);
            }
            
            $imagick->clear();
            $imagick->destroy();
        }
    }

}

?>