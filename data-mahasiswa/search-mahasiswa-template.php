<?php

// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$search_keyword = $_POST['search_keyword'];
?>

<div class="mahasiswa-form-row">
    <form name="form_search_mahasiswa mahasiswa-form-row" method="post">
        <label for="search_keyword">NIM/E-Mail :</label>
        <input type="text" id="search_keyword" name="search_keyword" required placeholder="Enter your NIM / E-Mail"  value=<?= $search_keyword ?>>
        <button type="submit" name="search_submit">Search</button>
    </form>
</div>

<?php 
do_action('handle_form_search_mahasiswa', $search_keyword);
?>