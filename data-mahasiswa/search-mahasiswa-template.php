<?php

// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$search_keyword = $_GET['search_keyword'];
?>

<style>
    @media (max-width: 600px) {
        .mahasiswa-form-row form {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 5px;
            padding:10px;
        }
    }
</style>
<div class="mahasiswa-form-row">
    <form name="form_search_mahasiswa mahasiswa-form-row" method="get">
        <label for="search_keyword">NIM/E-Mail :</label>
        <input type="text" id="search_keyword" name="search_keyword" required placeholder="Enter your NIM / E-Mail"  value=<?= $search_keyword ?>>
        <button type="submit">Search</button>
        <button type="button" onclick="redirect()">Reset</button>
    </form>
</div>
<script>
    function redirect() {
        window.location.href = '<?= site_url("/search-mahasiswa") ?>';
    }
</script>

<?php 
do_action('handle_form_search_mahasiswa', $search_keyword);
?>