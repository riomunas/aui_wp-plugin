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

    public static function getDataCountries() {
        global $auidb;
        
        // Execute the query
        return $auidb->get_results("
            SELECT id, kode, name 
            FROM countries
            ORDER BY id
        ");
    }
    public static function getDataDegrees() {
        global $auidb;
        
        // Execute the query
        return $auidb->get_results("
            SELECT id, kode 
            FROM degrees
            ORDER BY id
        ");
    }
    
    public static function getDataDepartments() {
        global $auidb;
        
        // Execute the query
        return $auidb->get_results("
            SELECT id, kode, name, sign_title 
            FROM departments
            ORDER BY name
        ");
    }
    
    public static function getDataPrograms() {
        global $auidb;
        
        // Execute the query
        return $auidb->get_results("
            SELECT id, name, department_id, degree_id 
            FROM programs
            ORDER BY name
        ");
    }
    
    public static function saveDataMahasiswa($data) {
        global $auidb;
        
        //buat nim
        $nim = DataMahasiswaHelper::generateNim($data);
        
        $existingMahasiswa = DataMahasiswaHelper::getDataMahasiswaByNim($nim);
        
        //kalau sudah ada nim yang sama balikin error sudah ada yang pakai
        if ($existingMahasiswa != null) {
            throw new ErrorException("Student already registered");
        }
        
        //simpan image
        $upload_dir = wp_upload_dir();
        $uploadDirectory = $upload_dir['basedir'] . '/student-photos/'; // Direktori khusus untuk file sementara
    

        // Check and create upload directory if not exists
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }
        
        // Mendapatkan ekstensi file
        $photoFileType = strtolower(pathinfo($data['photo']["name"], PATHINFO_EXTENSION));
        $ktpFileType = strtolower(pathinfo($data['ktp']["name"], PATHINFO_EXTENSION));
        $lastCertificationFileType = strtolower(pathinfo($data['last_certification']["name"], PATHINFO_EXTENSION));
        
        
        // Handling last-certification upload
        $lastCertification = $data['last_certification'];
        $lastCertificationName = $nim.'_certification_.'.$lastCertificationFileType;
        $lastCertification_path = $uploadDirectory . $lastCertificationName;
        move_uploaded_file($lastCertification['tmp_name'], $lastCertification_path);
        
        // Handling selfie upload
        $photo = $data['photo'];
        $photoName = $nim.'_photo_.'.$photoFileType;
        $photo_path = $uploadDirectory . $photoName;
        move_uploaded_file($photo['tmp_name'], $photo_path);
    
        // Handling identity upload
        $ktp = $data['ktp'];
        $ktpName = $nim.'_ktp_.'.$ktpFileType;
        $ktp_path = $uploadDirectory . $ktpName;
        move_uploaded_file($ktp['tmp_name'], $ktp_path);
        
        $current_date = date('Y-m-d');
        
        //simpan data mahasiswa
        $result = $auidb->insert('students',
            array(
                'nim' => $nim,
                'name' => $data['name'],
                'city_of_birth' => $data['city_of_birth'],
                'date_of_birth' => $data['date_of_birth'],
                'email' => $data['email'],
                'address1' => $data['address'],
                'ktp_path' => $ktpName,
				'phone' => $data['phone'],
                'photo_path' => $photoName,
				'last_certification_path' => $lastCertificationName,
                'status' => 'PENDING',
                'program_id' => $data['program'],
                'department_id' => $data['faculty'],
                'degree_id' => $data['degree'],
                'country_id' => $data['country'],
                'date_of_registered' => $current_date,
                'created_at' => $current_date
            )
        );
        $newMahasiswaId = $auidb->insert_id;
        $mahasiswa = DataMahasiswaHelper::getDataMahasiswaById($newMahasiswaId);
        

        if ($result === false) {
            // Penyisipan gagal
            wp_send_json_error($auidb->last_error);
            wp_die();
        } else {
            //kirim e-mail
            // Info SMTP dari hostingan Anda
            $smtp_host = SMTP_HOST;
            $smtp_port = SMTP_PORT;
            $smtp_username = SMTP_USERNAME;
            $smtp_password = SMTP_PASSWORD;
            $smtp_secure = SMTP_SECURE;
        
            // Set konfigurasi SMTP
            $smtp_settings = array(
                'host' => $smtp_host,
                'port' => $smtp_port,
                'username' => $smtp_username,
                'password' => $smtp_password,
                'timeout' => '30',
                'ssl' => $smtp_secure,
            );
        
            // Set pengaturan PHPMailer
            add_action('phpmailer_init', function ($phpmailer) use ($smtp_settings) {
                $phpmailer->isSMTP();
                $phpmailer->Host = $smtp_settings['host'];
                $phpmailer->Port = $smtp_settings['port'];
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $smtp_settings['username'];
                $phpmailer->Password = $smtp_settings['password'];
                $phpmailer->SMTPSecure = $smtp_settings['ssl'];
            });
        
            // Kirim email
            $to = $data['email'];
            $subject = '[Asean University International] Welcome E-Mail';
            $headers = 'From: Admin <'.SMTP_USERNAME.'>' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
            
            $email_template = file_get_contents(plugin_dir_path(__FILE__).'email-template.html');
            $email_template = str_replace('{{name}}', ucwords($mahasiswa->name), $email_template);
            $email_template = str_replace('{{program}}', $mahasiswa->program_title, $email_template);
            $email_template = str_replace('{{biaya}}', number_format($mahasiswa->beasiswa), $email_template);
            
            $konfirmasi_subject = '[Asean University International] Konfirmasi E-Mail';
            $email_konfirmasi_template = file_get_contents(plugin_dir_path(__FILE__).'email-konfirmasi-template.html');
            $email_konfirmasi_template = str_replace('{{name}}', ucwords($mahasiswa->name), $email_konfirmasi_template);
            $email_konfirmasi_template = str_replace('{{email}}', $mahasiswa->email, $email_konfirmasi_template);
            $email_konfirmasi_template = str_replace('{{phone}}', $mahasiswa->phone, $email_konfirmasi_template);
            $email_konfirmasi_template = str_replace('{{program}}', $mahasiswa->program_title, $email_konfirmasi_template);
            $email_konfirmasi_template = str_replace('{{biaya}}', number_format($mahasiswa->beasiswa), $email_konfirmasi_template);
          
            
            if (wp_mail($to, $subject, $email_template, $headers)) {
                wp_mail(SMTP_FOWARD_EMAIL_1, $konfirmasi_subject, $email_konfirmasi_template, $headers);
                wp_mail(SMTP_FOWARD_EMAIL_2, $konfirmasi_subject, $email_konfirmasi_template, $headers);
                wp_send_json_success('Pendaftaran berhasil. Email konfirmasi telah dikirim ke ' . $data['email']);
            } else {
                wp_send_json_error('Pendaftaran berhasil, tetapi email konfirmasi gagal dikirim.');
            }
            
            wp_send_json_success($data);
        }
    }
    
    public static function generateNim($data) {
        // Menggabungkan ketiga string menjadi satu
        $combinedString = $data['email'].'-'. $data['date_of_birth'] .'-'. $data['degree'].'-'. $data['faculty'] .'-'. $data['program'];
        
        // Menggunakan hash md5
        $hash = md5($combinedString);
        
        // Mengkonversi hash ke format numerik
        $uniqueNumber = hexdec(substr($hash, 0, 12)); // Mengambil 12 karakter pertama untuk menghindari overflow
        
        return $uniqueNumber;
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
    
    public static function getDataMahasiswaByNim($nim) {
        global $auidb;
        
        $mahasiswa = $auidb->get_row($auidb->prepare("
            SELECT *, s.name as name, coalesce(s.title_of_graduated, p.name) as degree_title FROM students s 
            INNER JOIN programs p on p.id = s.program_id
            WHERE s.nim = %s
            AND s.deleted_at is null
        ", $nim));
        
        return $mahasiswa;
    }

    public static function getDataMahasiswaById($id) {
        global $auidb;
        
        $mahasiswa = $auidb->get_row($auidb->prepare("
            SELECT 
                s.*, coalesce(s.title_of_graduated, p.name) as program_title, s.title_of_graduated as degree_title, coalesce(s.name_sign_of_graduated, coalesce(d.sign_name, dep.sign_name)) as degree_sign_name, coalesce(d.sign_title, dep.sign_title) as degree_sign_title, 
                concat(s.city_of_birth, ' - ', c.name, ', ', DATE_FORMAT(date_of_birth, '%M %D, %Y')) birth_info,
                concat(s.city_of_birth, ' - ', c.name) birth_prefix,
                DATE_FORMAT(s.date_of_graduated, '%M %D, %Y') as date_of_graduated, s.date_of_graduated as tgl_lulus, d.beasiswa as beasiswa, p.department_id as department_id,
                coalesce(s.path_sign_of_graduated, coalesce(d.sign_path, dep.sign_path)) sign_path, dep.name as faculty
            FROM students s 
            INNER JOIN degrees d on d.id = s.degree_id 
            INNER JOIN programs p on p.id = s.program_id
            INNER JOIN departments dep on dep.id = p.department_id
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

        // SELECT s.scores_settings_id, s.value
        // FROM `scores` s
        // INNER JOIN `scores_settings` ss on s.scores_settings_id = ss.id
        // where s.student_id = 433
        // LIMIT 50

        $scores = $auidb->get_results($auidb->prepare("
            SELECT scores_settings_id, value
            FROM scores
            WHERE student_id = %d
        ", $id));

        $mahasiswa->scores = array();
        foreach ($scores as $score) {
            $mahasiswa->scores[$score->scores_settings_id] = $score->value;
        }        

        return $mahasiswa;
    }
    
    public static function getDataMahasiswa($keyword) {
        $mydb = new wpdb(EXTERNAL_DB_USER, EXTERNAL_DB_PASSWORD, EXTERNAL_DB_NAME, EXTERNAL_DB_HOST);

        $mahasiswa = $mydb->get_row($mydb->prepare("
            SELECT 
                s.*, coalesce(s.title_of_graduated, p.name) as program_title, coalesce(s.title_of_graduated, p.name)  as degree_title, coalesce(s.name_sign_of_graduated, coalesce(d.sign_name, dep.sign_name)) as degree_sign_name, coalesce(d.sign_title, dep.sign_title) as degree_sign_title, 
                concat(s.city_of_birth, ' - ', c.name, ', ', DATE_FORMAT(date_of_birth, '%M %D, %Y')) birth_info,
                concat(s.city_of_birth, ' - ', c.name) birth_prefix,
                DATE_FORMAT(s.date_of_graduated, '%M %D, %Y') as date_of_graduated, s.date_of_graduated as tgl_lulus , p.department_id as department_id,
                coalesce(s.path_sign_of_graduated, coalesce(d.sign_path, dep.sign_path)) sign_path, dep.name as faculty
            FROM students s 
            INNER JOIN degrees d on d.id = s.degree_id 
            INNER JOIN programs p on p.id = s.program_id
            INNER JOIN departments dep on dep.id = p.department_id
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
        
        switch ($mahasiswa->degree_id) {
            case 1: DataMahasiswaHelper::templateS1($mahasiswa, $type); break;
            case 3: DataMahasiswaHelper::templateS3($mahasiswa, $type); break;
            case 4: DataMahasiswaHelper::templateS4($mahasiswa, $type); break;
            case 5: DataMahasiswaHelper::templateS4($mahasiswa, $type); break;
            case 6: 
                if ($mahasiswa->department_id == 81) {
                    DataMahasiswaHelper::templateTOEFL($mahasiswa, $type);
                }
                break;
            default: DataMahasiswaHelper::templateDefault($mahasiswa, $type); break;
        }

    }
    // Fungsi untuk mendapatkan akhiran tanggal
    private static function getFormatedDate($date) {
        $dateTime = new DateTime($date);
        $day = $dateTime->format('j');
        $suffix = 'th';
        
        if (!in_array(($day % 100), [11, 12, 13])) {
            switch ($day % 10) {
                case 1:
                    $suffix =  'st'; break;
                case 2:
                    $suffix =  'nd'; break;
                case 3:
                    $suffix =  'rd'; break;
            }
        }
        $formattedDate = $dateTime->format("F j") . "<sup style='font-size: 24px;'>$suffix</sup>, " . $dateTime->format("Y");
        return $formattedDate;
    }
    
    
    private static function templateS1($mahasiswa, $type) {
        // initiate PDF
        $pdf = new Fpdi('L','mm','A4');
        
        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        //add new page
        $pdf->AddPage();
        
        $template_name = 'template-1.pdf';//download
        
        switch ($type) {
            case 'print': $template_name = 'print-template-1.pdf'; break;
            case 'image': $template_name = 'water-mark-template-1.pdf'; break;
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
        $pdf->write2DBarcode('https://asean-university.com/search-mahasiswa/?search_keyword='.$mahasiswa->nim, 'QRCODE,L', 235.37, 65.43, 40, 40, $style, 'N');
    
        $degreeId = $mahasiswa->degree_id;
        $departmentId = $mahasiswa->department_id;
        if ($degreeId < 3) {
            $degreeId = 0;
        } else {
            $departmentId = 0;
        }
        // Path ke gambar
        // $imagePath = plugin_dir_path(__FILE__).'3_0_sign.png';
        // $imagePath = plugin_dir_path(__FILE__).$degreeId.'_'.$departmentId.'_sign.png';
        $imagePath = plugin_dir_path(__FILE__).$mahasiswa->sign_path;
        $pdf->Image($imagePath, 20, 157.72, 115.53, 24.46, 'PNG');
    
        //number certificate
        $pdf->SetFont('helvetica', 'B', 9, '', true);
        $pdf->SetTextColor(10, 81, 130);
        $pdf->SetXY(23.93, 18.77);
        $pdf->Cell(256, 4.97, 'NUMBER : '.$mahasiswa->number_of_graduated, 0, 1,'L');
        
        //set warna font hitam
        $pdf->SetTextColor(0, 0, 0);
        
        //name
        $pdf->SetFont('times', 'B', 20, '', true);
        $pdf->SetXY(20, 67.66);
        $pdf->Cell(256, 7.46, $mahasiswa->name,0, 1,'C');
        
        //academic deggre
        $pdf->SetFont('times', '', 12, '', true);
        $pdf->SetXY(89.29, 79);
        $pdf->Cell(187.86, 4.97, $mahasiswa->program_title, 0, 1,'L');
        
        //dof
        $pdf->SetFont('times', '', 12, '', true);
        $pdf->SetXY(89.29, 90);
        $dateOfBirth = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_birth);
        $pdf->writeHtml($mahasiswa->birth_prefix.', '.$dateOfBirth, true, false, true, false, '');
        
        //student number
        $pdf->SetFont('times', '', 12, '', true);
        $pdf->SetXY(89.29, 101);
        $pdf->Cell(187.86, 4.97, $mahasiswa->nim, 0, 1,'L');
        
        //department
        $pdf->SetFont('times', '', 12, '', true);
        $pdf->SetXY(89.29, 112);
        $pdf->Cell(187.86, 4.97, $mahasiswa->faculty, 0, 1,'L');

        //dog
        $pdf->SetFont('times', '', 12, '', true);
        $pdf->SetXY(89.29, 122);
        $dateOfStudyStart = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_registered);
        $dateOfStudyEnd = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_graduated);
        $pdf->writeHtml($dateOfStudyStart.' to '.$dateOfStudyEnd, true, false, true, false, '');
		
        //tanggal
        $pdf->SetFont('times', '', 12, '', true);
        $dateOfStudyEnd = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_graduated);
        $pageWidth = $pdf->getPageWidth();
        $pdf->writeHTMLCell('256', 0, '25.94', '149.72', 'Malaysia, '.$dateOfStudyEnd, 0, 0, 0, true, 'C');
        
        //atas
        //dekan
        $pdf->SetFont('times', 'BU', 11);
        $pdf->SetXY(20, 179.49);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_name, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 179.49);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_name, 0, 1,'C');
        
        //bawah
        //dekan
        $pdf->SetFont('times', 'I', 11);
        $pdf->SetXY(20, 184.47);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_title, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 184.47);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_title, 0, 1,'C');
        
        // $pdf->Image('0_11_sign.png', 20, 83.58, 119.3, 119.3, 'PNG');
        // //tanda tangan concelor
        if ($type != 'print') {
            $pdf->Image(plugin_dir_path(__FILE__).'/conselor.png', 161, 157.72, 119.3, 0, 'PNG');
        }


        
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
    
    private static function templateS3($mahasiswa, $type) {
        // initiate PDF
        $pdf = new Fpdi('L','mm','A4');
        
        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        //add new page
        $pdf->AddPage();
        
        $template_name = 'template-3.pdf';//download
        
        switch ($type) {
            case 'print': $template_name = 'print-template-3.pdf'; break;
            case 'image': $template_name = 'water-mark-template-3.pdf'; break;
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
        $pdf->write2DBarcode('https://asean-university.com/search-mahasiswa/?search_keyword='.$mahasiswa->nim, 'QRCODE,L', 239.17, 92.5, 25, 25, $style, 'N');
    
        $degreeId = $mahasiswa->degree_id;
        $departmentId = $mahasiswa->department_id;
        if ($degreeId < 3) {
            $degreeId = 0;
        } else {
            $departmentId = 0;
        }
        $imagePath = plugin_dir_path(__FILE__).$mahasiswa->sign_path;
        
        if ($type != 'print') {
            $pdf->Image($imagePath, 20, 157.72, 115.53, 24.46, 'PNG');
        }
        
        //number certificate
        $pdf->SetFont('helvetica', 'B', 9, '', true);
        $pdf->SetTextColor(10, 81, 130);
        $pdf->SetXY(175.62, 21);
        $pdf->Cell(92.71, 4.97, 'NUMBER : '.$mahasiswa->number_of_graduated, 0, 1,'R');
        
        //set warna font hitam
        $pdf->SetTextColor(0, 0, 0);
        
        //name
        $pdf->SetFont('times', 'B', 24, '', true);
        $pdf->SetXY(20, 71.48);
        $pdf->Cell(256, 0, $mahasiswa->name,0, 1,'C');
        
        //student number
        $pdf->SetFont('helvetica', 'B', 12, '', true);
        $pdf->SetXY(20, 80.37);
        $pdf->Cell(256, 4.97, $mahasiswa->nim, 0, 1,'C');
        
        //dof
        $pdf->SetFont('times', 'I', 12, '', true);
        $dateOfBirth = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_birth);
        $pdf->writeHTMLCell('256', 0, '20', '85.87', 'Place and Date of Birth : '.$mahasiswa->birth_prefix.', '.$dateOfBirth, 0, 0, 0, true, 'C');
        
        //deggre title
        $pdf->SetFont('times', 'B', 18, '', true);
        $pdf->SetXY(20, 109.63);
        $x = ($pdf->getPageWidth() - 150) / 2;
        $pdf->MultiCell(150, 0, $mahasiswa->program_title, 1, 'C', 0, 0, $x, '', true);
        
        // //dog
        // $pdf->SetFont('times', 'I', 12, '', true);
        // $dateOfStudyStart = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_registered);
        // $dateOfStudyEnd = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_graduated);
        // $pageWidth = $pdf->getPageWidth();
        // $pdf->writeHTMLCell('256', 0, '20', '117.62', 'from '.$dateOfStudyStart.' to '.$dateOfStudyEnd, 0, 0, 0, true, 'C');

        //tanggal
        $pdf->SetFont('times', '', 12, '', true);
        $dateOfStudyEnd = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_graduated);
        $pageWidth = $pdf->getPageWidth();
        $pdf->writeHTMLCell('256', 0, '20', '154.41', 'Malaysia, '.$dateOfStudyEnd, 0, 0, 0, true, 'C');

        //atas
        //dekan
        $pdf->SetFont('times', 'BU', 11);
        $pdf->SetXY(20, 179.49);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_name, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 179.49);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_name, 0, 1,'C');
        
        //bawah
        //dekan
        $pdf->SetFont('times', 'I', 11);
        $pdf->SetXY(20, 184.47);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_title, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 184.47);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_title, 0, 1,'C');
        
        //tanda tangan concelor
        if ($type != 'print') {
            $pdf->Image(plugin_dir_path(__FILE__).'/conselor.png', 161, 157.72, 119.3, 0, 'PNG');
        }
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

    private static function templateTOEFL($mahasiswa, $type) {
        // initiate PDF
        $pdf = new Fpdi('L','mm','A4');
        
        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        //add new page
        $pdf->AddPage();
        
        $template_name = 'template-TOEFL.pdf';//download
        
        switch ($type) {
            case 'print': $template_name = 'print-template-TOEFL.pdf'; break;
            case 'image': $template_name = 'water-mark-template-TOEFL.pdf'; break;
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
        // $pdf->write2DBarcode('https://asean-university.com/search-mahasiswa/?search_keyword='.$mahasiswa->nim, 'QRCODE,L', 23.93, 23.93, 20, 20, $style, 'N');
    
        $degreeId = $mahasiswa->degree_id;
        $departmentId = $mahasiswa->department_id;
        if ($degreeId < 3) {
            $degreeId = 0;
        } else {
            $departmentId = 0;
        }
        
        //name
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('times', 'B', 20, '', true);
        $pdf->SetXY(0, 96);
        $pdf->Cell(297, 0, $mahasiswa->name,0, 1,'C');

        //dof
        $pdf->SetFont('times', 'I', 12, '', true);
        $dateOfBirth = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_birth);
        $pdf->writeHTMLCell('297', 0, '0', '105', '('.$dateOfBirth.' in '.$mahasiswa->birth_prefix.')', 0, 0, 0, true, 'C');

        //number certificate
        $pdf->SetFont('helvetica', 'B', 17, '', true);
        $pdf->SetTextColor(10, 81, 130);
        $pdf->SetXY(0, 121);
        $pdf->Cell(297, 0, $mahasiswa->number_of_graduated, 0, 1,'C');
        

        //set warna font hitam
        $pdf->SetTextColor(0, 0, 0);
        
        //listening
        $pdf->SetFont('helvetica', 'B', 12, '', true);
        $pdf->SetXY(8, 160);
        $pdf->Cell(97, 0, isset($mahasiswa->scores[1]) ? $mahasiswa->scores[1] : '', 0, 1,'R');

        //structure
        $pdf->SetFont('helvetica', 'B', 12, '', true);
        $pdf->SetXY(8, 167);
        $pdf->Cell(97, 0, isset($mahasiswa->scores[2]) ? $mahasiswa->scores[2] : '', 0, 1,'R');

        //reading
        $pdf->SetFont('helvetica', 'B', 12, '', true);
        $pdf->SetXY(8, 174);
        $pdf->Cell(97, 0, isset($mahasiswa->scores[3]) ? $mahasiswa->scores[3] : '', 0, 1,'R');

        //total
        $pdf->SetFont('helvetica', 'B', 12, '', true);
        $pdf->SetXY(8, 181);
        $pdf->Cell(97, 0, isset($mahasiswa->scores[4]) ? $mahasiswa->scores[4] : '', 0, 1,'R');
        
        //tanggal
        $pdf->SetFont('times', '', 12, '', true);
        $dateOfStudyEnd = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_graduated);
        $pageWidth = $pdf->getPageWidth();
        $pdf->writeHTMLCell('119.3', 0, '160.94', '154.41', 'Malaysia, '.$dateOfStudyEnd, 0, 0, 0, true, 'C');

        //atas
        //dekan
        $pdf->SetFont('times', 'BU', 11);
        $pdf->SetXY(160.94, 179.49);
        $pdf->Cell(119.3, 4.97, $mahasiswa->degree_sign_name, 0, 1,'C');
        
        //bawah
        //dekan
        $pdf->SetFont('times', 'I', 11);
        $pdf->SetXY(160.94, 184.47);
        $pdf->Cell(119.3, 4.97, $mahasiswa->degree_sign_title, 0, 1,'C');
        

        //tanda tangan
        $imagePath = plugin_dir_path(__FILE__).$mahasiswa->sign_path;
        // if ($type != 'print') {
            $pdf->Image($imagePath,  161, 157.72, 119.3, 0, 'PNG');
        // }

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
    
    private static function templateS4($mahasiswa, $type) {
        // initiate PDF
        $pdf = new Fpdi('L','mm','A4');
        
        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        //add new page
        $pdf->AddPage();
        
        $template_name = 'template-4.pdf';//download
        
        switch ($type) {
            case 'print': $template_name = 'print-template-4.pdf'; break;
            case 'image': $template_name = 'water-mark-template-4.pdf'; break;
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
        $pdf->write2DBarcode('https://asean-university.com/search-mahasiswa/?search_keyword='.$mahasiswa->nim, 'QRCODE,L', 23.93, 23.93, 20, 20, $style, 'N');
    
        $degreeId = $mahasiswa->degree_id;
        $departmentId = $mahasiswa->department_id;
        if ($degreeId < 3) {
            $degreeId = 0;
        } else {
            $departmentId = 0;
        }
        $imagePath = plugin_dir_path(__FILE__).$mahasiswa->sign_path;
        
        if ($type != 'print') {
            $pdf->Image($imagePath, 20, 157.72, 115.53, 24.46, 'PNG');
        }
        
        //number certificate
        $pdf->SetFont('helvetica', 'B', 9, '', true);
        $pdf->SetTextColor(10, 81, 130);
        $pdf->SetXY(175.62, 21);
        $pdf->Cell(92.71, 4.97, 'NUMBER : '.$mahasiswa->number_of_graduated, 0, 1,'R');
        
        //set warna font hitam
        $pdf->SetTextColor(0, 0, 0);
        
        //name
        $pdf->SetFont('times', 'B', 22, '', true);
        $pdf->SetXY(20, 84);
        $pdf->Cell(256, 0, $mahasiswa->name,0, 1,'C');
        
        // //student number
        // $pdf->SetFont('helvetica', 'B', 12, '', true);
        // $pdf->SetXY(20, 80.37);
        // $pdf->Cell(256, 4.97, $mahasiswa->nim, 0, 1,'C');
        
        //dof
        $pdf->SetFont('times', 'I', 12, '', true);
        $dateOfBirth = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_birth);
        $pdf->writeHTMLCell('256', 0, '20', '93', 'Date of Birth : '.$dateOfBirth.' in '.$mahasiswa->birth_prefix, 0, 0, 0, true, 'C');
        
        //deggre title 1
        $pdf->SetFont('times', 'B', 18, '', true);
        $pdf->SetXY(20, 120.54);
        $degreeTitle = 'Professor '.$mahasiswa->program_title;
        if ($mahasiswa->degree_id == 4) {
            $degreeTitle = 'Professor of '.$mahasiswa->faculty;
        }
        $pdf->Cell(256, 0, $degreeTitle, 0, 1,'C');
        
        if ($mahasiswa->degree_id == 4) {
            //deggre title 2
            $pdf->SetFont('times', 'B', 12, '', true);
            $pdf->SetXY(20, 128.92);
            $pdf->Cell(256, 0, 'with specific expertise in '.$mahasiswa->program_title, 0, 1,'C');
        }
        
        //bottom text
        $pdf->SetFont('times', '', 12, '', true);
        $pdf->SetXY(20, 140);
        $x = ($pdf->getPageWidth() - 200) / 2;
        $bottomText = 'to honor and recognize his broad knowledge, outstanding and extensive professional experience';
        if ($mahasiswa->degree_id == 4) {
            $bottomText = $bottomText.' in the field of '.$mahasiswa->program_title;
        }
        $pdf->MultiCell(200, 0, $bottomText, 1, 'C', 0, 0, $x, '', true);
        
        
        // //dog
        // $pdf->SetFont('times', 'I', 12, '', true);
        // $dateOfStudyStart = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_registered);
        // $dateOfStudyEnd = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_graduated);
        // $pageWidth = $pdf->getPageWidth();
        // $pdf->writeHTMLCell('256', 0, '20', '117.62', 'from '.$dateOfStudyStart.' to '.$dateOfStudyEnd, 0, 0, 0, true, 'C');

        //tanggal
        $pdf->SetFont('times', '', 12, '', true);
        $dateOfStudyEnd = DataMahasiswaHelper::getFormatedDate($mahasiswa->date_of_graduated);
        $pageWidth = $pdf->getPageWidth();
        $pdf->writeHTMLCell('256', 0, '20', '154.41', 'Malaysia, '.$dateOfStudyEnd, 0, 0, 0, true, 'C');

        //atas
        //dekan
        $pdf->SetFont('times', 'BU', 11);
        $pdf->SetXY(20, 179.49);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_name, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 179.49);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_name, 0, 1,'C');
        
        //bawah
        //dekan
        $pdf->SetFont('times', 'I', 11);
        $pdf->SetXY(20, 184.47);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_title, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 184.47);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_title, 0, 1,'C');
        
        //tanda tangan concelor
        if ($type != 'print') {
            $pdf->Image(plugin_dir_path(__FILE__).'/conselor.png', 161, 157.72, 119.3, 0, 'PNG');
        }
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

    private static function templateDefault($mahasiswa, $type) {
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
        $pdf->write2DBarcode('https://asean-university.com/search-mahasiswa/?search_keyword='.$mahasiswa->nim, 'QRCODE,L', 235.37, 65.43, 40, 40, $style, 'N');
    
        $degreeId = $mahasiswa->degree_id;
        $departmentId = $mahasiswa->department_id;
        if ($degreeId < 3) {
            $degreeId = 0;
        } else {
            $departmentId = 0;
        }
        // Path ke gambar
        $imagePath = plugin_dir_path(__FILE__).$mahasiswa->sign_path;
        $pdf->Image($imagePath, 20, 157.72, 115.53, 24.46, 'PNG');
    
        //number certificate
        $pdf->SetFont('helvetica', 'B', 9, '', true);
        $pdf->SetTextColor(10, 81, 130);
        $pdf->SetXY(23.93, 18.77);
        $pdf->Cell(256, 4.97, 'NUMBER : '.$mahasiswa->number_of_graduated, 0, 1,'L');
        
        //set warna font hitam
        $pdf->SetTextColor(0, 0, 0);
        
        //name
        $pdf->SetFont('times', 'B', 18, '', true);
        $pdf->SetXY(20, 81.58);
        $pdf->Cell(256, 7.46, $mahasiswa->name,0, 1,'C');
        
        //dof
        $pdf->SetFont('times', 'I', 12, '', true);
        $pdf->SetXY(20, 87.72);
        $pdf->Cell(256, 4.97, $mahasiswa->birth_info,0, 1,'C');
        
        //student number
        $pdf->SetFont('helvetica', 'B', 12, '', true);
        $pdf->SetXY(20, 94.69);
        $pdf->Cell(256, 4.97, $mahasiswa->nim, 0, 1,'C');
        
        //deggre
        $pdf->SetFont('times', 'B', 18, '', true);
        $pdf->SetXY(20, 115.87);
        $pdf->Cell(256, 4.97, $mahasiswa->program_title, 0, 1,'C');
        
        // //program
        // $pdf->SetFont('times', 'I', 12, '', true);
        // $pdf->SetXY(20, 123.33);
        // $pdf->Cell(256, 4.97, $mahasiswa->program_title, 0, 1,'C');
        
        //tanggal 
        $pdf->SetFont('times', '', 12, '', true);
        $pdf->SetXY(20, 154.41);
        $pdf->Cell(256, 4.97, 'Malaysia, '.$mahasiswa->date_of_graduated, 0, 1,'C');
        
        
        
        
        
        
        //atas
        //dekan
        $pdf->SetFont('times', 'BU', 11);
        $pdf->SetXY(20, 179.49);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_name, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 179.49);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_name, 0, 1,'C');
        
        //bawah
        //dekan
        $pdf->SetFont('times', 'I', 11);
        $pdf->SetXY(20, 184.47);
        $pdf->Cell(115.53, 4.97, $mahasiswa->degree_sign_title, 0, 1,'C');
        //cancelor
        $pdf->SetXY(160.94, 184.47);
        $pdf->Cell(119.3, 4.97, $mahasiswa->sign_certificate_title, 0, 1,'C');
        
        $pdf->Image('0_11_sign.png', 20, 83.58, 119.3, 119.3, 'PNG');


        
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