<?php 
	fopen("../Panel/".$judul_tabel_sistem."/hapus.php", "x");
	$hapus  = fopen("../Panel/".$judul_tabel_sistem."/hapus.php", "w");

	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/hapus.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
		}else{
		}
	}

	fwrite($hapus, $isi_hapus);
	fclose($hapus);
 ?>