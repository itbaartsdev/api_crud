<?php 
	fopen("Panel/".$table_display_name."/proses.php", "x");
	$proses  = fopen("Panel/".$table_display_name."/proses.php", "w");
	include '../data/proses/header.php';

	for ($i=0; $i < $total; $i++) {
		if ($field_properties[$i] == "primary") {
		}else if ($field_properties[$i] == "index") {
			include '../data/proses/judul.php';
		}else{
			include '../data/proses/judul.php';
		}
	}
	
	for ($i=0; $i < $total; $i++) {
		if ($field_properties[$i] == "primary") {
			include '../data/proses/isi1.php';
		}else if ($field_properties[$i] == "index") {
			include '../data/proses/ulang.php';
		}else{
			include '../data/proses/ulang.php';
			}
		}

	for ($i=0; $i < $total; $i++) {
		if ($field_properties[$i] == "primary") {
			include '../data/proses/isi2.php';
		}else if ($field_properties[$i] == "index") {
			include '../data/proses/ulang.php';
		}else{
			include '../data/proses/ulang.php';
			}
		}
		
	for ($i=0; $i < $total; $i++) {
		if ($field_properties[$i] == "primary") {
			include '../data/proses/footer.php';
		}else if ($field_properties[$i] == "index") {
		}else{
			}
		}

	fwrite($proses, $isi_proses);
	fclose($proses);
 ?>