<?php

// Pastikan tidak ada akses langsung
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>

<style>
	@media (max-width: 600px) {
		.profile-card {
			max-width: 100%;
		}
	}
	.main{
		width: 100%;
		height: 100vh;
		display: flex;
		align-items: flex-start;
		justify-content: center;
		background-position: center;
		background-size: cover;
	}
	.profile-card{
		max-width: 600px;
		display: flex;
		flex-direction: column;
		align-items: center;
		flex: 1;
		margin: 10px;
		border-radius: 10px;
		border: 1px solid rgb(64 113 244);
	}
	.image{
		position: relative;
		height: 150px;
		width: 150px;
	}
	.image .profile-pic{
		width: 100%;
		height: 100%;
		object-fit: contain;
		border-radius: 50%;
		padding: 10px;
		border: 2px solid rgb(64 113 244);
	}
	.data{
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-top: 15px;
	}
	.data h2{
		font-size: 33px;
		font-weight: 600;
	}
	span{
		font-size: 18px;
	}
	.row{
		display: flex;
		align-items: center;
		margin-top: 30px;
	}
	.row .info{
		text-align: center;
		padding: 0 20px;
	}
	.buttons{
		display: flex;
		align-items: center;
		margin-top: 30px;
	}
	.buttons .btn{
		color: #fff;
		text-decoration: none;
		margin: 0 20px;
		padding: 8px 25px;
		border-radius: 25px;
		font-size: 18px;
		white-space: nowrap;
		background: rgb(64 113 244)
	}
</style>	


    <section class="main">
    <? if ($mahasiswa): ?>
        <div class="profile-card">
    		<div  style="background: rgb(64 113 244);
    				width: 100%;
    				text-align: center;
    				padding: 20px;
    				border-top-right-radius: 9px;
    				border-top-left-radius: 9px;
    				height: 150px;
    				margin-bottom: 70px;
    				padding-top: 70px;">
    			<img src="https://asean-university.com/wp-content/uploads/student-photos/<?= $mahasiswa->photo_path ?>" class="profile-pic" style="width: 150px;
    																																			   border: 4px solid white;
    																																			   height: 150px;
    																																			   object-fit: contain;
    																																			   border-radius: 50%;
    																																			   padding: 10px;
    																																			   background: lightgray;
    																																			   margin-left: auto;
    																																			   margin-right: auto;
    																																			   outline-width: 4px;
    																																			   outline-style: solid;
    																																			   outline-color: rgb(64 113 244);">
    		</div>
    		<div class="data" style="margin: 20px 20px 0px 20px;">
    			<!-- 	nama	 -->
    			<h2 style="text-align:center"><?= $mahasiswa->name ?></h2>
    			<!--   program   -->
    			<span style="text-align:center"><?= $mahasiswa->degree_title ?></span>
    		</div>
    
    		<div class="row" style="margin:0px 20px;">
    			<div class="info">
    				<h3>(<?= $mahasiswa->nim ?>)</h3>
    			</div>
    		</div>
    		<!--   status	 -->
    		<div class="buttons" style="margin:20px">
    			<div class="btn">
    				<?= $mahasiswa->status ?>
    			</div>
    		</div>
    	</div>
    
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
    </section>