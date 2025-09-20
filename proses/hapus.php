<?php 
	fopen("Panel/".$table_display_name."/hapus.php", "x");
	$hapus  = fopen("Panel/".$table_display_name."/hapus.php", "w");

	for ($i=0; $i < $total; $i++) {
		if ($field_properties[$i] == "primary") {
			include '../data/hapus.php';
		}else if ($field_properties[$i] == "index") {
		}else{
		}
	}

	fwrite($hapus, $isi_hapus);
	fclose($hapus);
 ?>