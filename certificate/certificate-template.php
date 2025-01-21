<?php

// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<div class="certificate-container">
    <?php
    $certificate_number = get_query_var('certificate_number');
    if ($certificate_number): ?>
        <img src=<?= site_url('/wp-content/uploads/temp-files/certificate-'.$certificate_number.'.jpg'); ?> alt="Certificate"/>
    <?php else: ?>
        <p>Nomor sertifikat tidak ditemukan.</p>
    <?php endif; ?>
</div>