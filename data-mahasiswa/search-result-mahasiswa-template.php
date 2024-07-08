<?php

// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>

<div>
    <div class="certificate-container">
        <div class="certificate">
            <? if ($mahasiswa): ?>
                <div style="
                    text-align: center;
                    color: lightseagreen;
                    margin: 30px 0px;
                    border: 1px solid lightseagreen;
                    padding: 15px;
                    border-radius: 10px;
                ">
                    <strong>Status : <?= $mahasiswa->status ?></strong> </br>
                </div>
                <? if(!empty($mahasiswa->photo_path)):  ?>
                    <div style="width: 150px; height: 150px; border-radius: 50%; overflow: hidden; margin: 70px auto 30px;">
                        <img src="https://registrasi.asean-university.com/download/<?= $mahasiswa->photo_path ?>" style="width: 100%; height: auto; display: block;">
                    </div>
                <? else: ?>
                    <div style="width: 150px; height: 150px; border-radius: 50%; overflow: hidden; margin: 70px auto 30px; background-color: #f3f4f6; text-align: center; line-height: 150px;">
                        <i class="fas fa-user text-6xl text-gray-400"></i>
                    </div>
                <? endif; ?>
                <div class="student-name"><?=strtoupper($mahasiswa->name)?></div>
                <div class="personal-info nim">Student ID : <?=strtoupper($mahasiswa->nim)?></div>
                <div class="certificate-title"><?=$mahasiswa->degree_title?></div>
                <div class="personal-info faculty"><?=$mahasiswa->program_title?></div>
            <? else: ?>
                <div style="
                    text-align: center;
                    color: crimson;
                    margin: 30px 0px;
                    border: 1px solid crimson;
                    padding: 15px;
                    border-radius: 10px;
                ">
                    <strong>Data Not Found !!!</strong> </br>
                </div>
            <? endif; ?>
        </div>
    </div>
</div>