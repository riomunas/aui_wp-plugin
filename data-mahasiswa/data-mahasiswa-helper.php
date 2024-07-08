<?php

if ( !defined('EXTERNAL_DB_HOST') || !defined('EXTERNAL_DB_USER') || !defined('EXTERNAL_DB_PASSWORD') || !defined('EXTERNAL_DB_NAME') ) {
    return 'External database constants are not properly defined.';
}

class DataMahasiswaHelper {

    public static function getDataMahasiswaByGraduatedDate($dateOfgraduated) {
        // Implementasi fungsi pengambilan data mahasiswa berdasarkan tanggal lulus
        // ...
    }

    public static function getDataMahasiswaById($id) {
        global $auidb;
        return $auidb->get_row(
            $auidb->prepare("SELECT * FROM students WHERE id = %d", $id)
        );
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
            WHERE (s.nim = %s or s.email = %s)
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

    public static function generateCertificate($mahasiswa, $isForGenerate) {
        // Implementasi fungsi pembuatan sertifikat
        // ...
    }
    
    public static function sayHello($name) {
        return "Hello, ".$name;
    }

}

?>