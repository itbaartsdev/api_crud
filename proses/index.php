<?php 
	fopen("../Panel/".$judul_tabel_sistem."/index.php", "x");
	$index  = fopen("../Panel/".$judul_tabel_sistem."/index.php", "w");
	include '../data/index/header.php';

	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/index/judul.php';
		}else{
			include '../data/index/judul.php';
		}
	}
	
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/index/isi.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/index/join.php';
		}else{
			}
		}	
		
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/index/sql.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/index/ulang.php';
		}else{
			include '../data/index/ulang.php';
			}
		}

	include '../data/index/footer.php';

	fwrite($index, $isi_index);
	fclose($index);
 ?>