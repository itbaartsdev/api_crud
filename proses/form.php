<?php 
 $petik = "'"; 
	fopen("Panel/".$table_display_name."/form.php", "x");
	$form  = fopen("Panel/".$table_display_name."/form.php", "w");

	for ($i=0; $i < $total; $i++) {
		if ($field_properties[$i] == "primary") {
			include '../data/form/header.php';
		}else if ($field_properties[$i] == "index") {
		}else{
		}
	}
	
	for ($i=0; $i < $total; $i++) {
		if ($field_properties[$i] == "primary") {
		}else if ($field_properties[$i] == "index") {
			$hasil = explode('id_',$field_names[$i]);

			$isi_form .= '
	<div class="col-lg-12">
	<div class="form-group">
	<label class="form-label">'.$field_labels[$i].'</label>
	        <select name="'.$field_names[$i].'" class="js-example-basic-single form-control" id="'.$field_names[$i].'" required>
			<option value>-- Pilih '.$field_labels[$i].' --</option>
			<?php
				$row = mysqli_query($koneksi,"SELECT * FROM '.$hasil[1].'");
                while ($rows = mysqli_fetch_array($row)) {	
                	 if ($data['.$petik.$field_names[$i].$petik.'] == $rows['.$petik.$field_names[$i].$petik.']) {
				?>
				<option value="<?=$rows['.$petik.$field_names[$i].$petik.'];?>" selected><?=$rows['.$petik.$field_names[$i].$petik.'];?></option>		
				<?php
				}else{
				?>
				<option value="<?=$rows['.$petik.$field_names[$i].$petik.'];?>"><?=$rows['.$petik.$field_names[$i].$petik.'];?></option>		
			<?php
				}
				}
			?>
			</select>
			</div>
			</div>
			';			
		}else{
			include '../data/form/isi.php';
			}
		}
			include '../data/form/footer.php';

	fwrite($form, $isi_form);
	fclose($form);
 ?>