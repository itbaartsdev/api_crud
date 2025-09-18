<?php 
	fopen("../Panel/".$judul_tabel_sistem."/cetak.php", "x");
	$cetak  = fopen("../Panel/".$judul_tabel_sistem."/cetak.php", "w");
	include '../data/cetak/header.php';

	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
		}else if ($keterangan_field_sistem[$i] == "cetak") {
			include '../data/cetak/judul.php';
		}else{
			include '../data/cetak/judul.php';
		}
	}
	
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/cetak/isi.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/cetak/join.php';
		}else{
			}
		}	
		
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/cetak/sql.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
			include '../data/cetak/ulang.php';
		}else{
			include '../data/cetak/ulang.php';
			}
		}

	include '../data/cetak/footer.php';

	fwrite($cetak, $isi_cetak);
	fclose($cetak);
 ?>