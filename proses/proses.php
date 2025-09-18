<?php 
	fopen("../Panel/".$judul_tabel_sistem."/proses.php", "x");
	$proses  = fopen("../Panel/".$judul_tabel_sistem."/proses.php", "w");
	include '../data/proses/header.php';

	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/proses/judul.php';
		}else{
			include '../data/proses/judul.php';
		}
	}
	
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/proses/isi1.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/proses/ulang.php';
		}else{
			include '../data/proses/ulang.php';
			}
		}

	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/proses/isi2.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/proses/ulang.php';
		}else{
			include '../data/proses/ulang.php';
			}
		}
		
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/proses/footer.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
		}else{
			}
		}

	fwrite($proses, $isi_proses);
	fclose($proses);
 ?>