<?php 
	fopen("../laporan/".$nama_tabel_sistem.".php", "x");
	$laporan  = fopen("../laporan/".$nama_tabel_sistem.".php", "w");
	include '../data/laporan/header.php';

	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
		}else if ($keterangan_field_sistem[$i] == "laporan") {
			include '../data/laporan/judul.php';
		}else{
			include '../data/laporan/judul.php';
		}
	}
	
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/laporan/isi.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/laporan/join.php';
		}else{
			}
		}	
		
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/laporan/sql.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/laporan/ulang.php';
		}else{
			include '../data/laporan/ulang.php';
			}
		}

	include '../data/laporan/footer.php';

	fwrite($laporan, $isi_laporan);
	fclose($laporan);
 ?>