<?php 
 $petik = "'"; 
	fopen("../Panel/".$judul_tabel_sistem."/form.php", "x");
	$form  = fopen("../Panel/".$judul_tabel_sistem."/form.php", "w");

	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
			include '../data/form/header.php';
		}else if ($keterangan_field_sistem[$i] == "index") {
		}else{
		}
	}
	
	for ($i=0; $i < $total; $i++) {
		if ($keterangan_field_sistem[$i] == "primary") {
		}else if ($keterangan_field_sistem[$i] == "index") {
			$hasil = explode('id_',$nama_field_sistem[$i]);

			$isi_form .= '
	<div class="col-lg-12">
	<div class="form-group">
	<label class="form-label">'.$judul_field_sistem[$i].'</label>
	        <select name="'.$nama_field_sistem[$i].'" class="js-example-basic-single form-control" id="'.$nama_field_sistem[$i].'" required>
			<option value>-- Pilih '.$judul_field_sistem[$i].' --</option>
			<?php
				$row = mysqli_query($koneksi,"SELECT * FROM '.$hasil[1].'");
                while ($rows = mysqli_fetch_array($row)) {	
                	 if ($data['.$petik.$nama_field_sistem[$i].$petik.'] == $rows['.$petik.$nama_field_sistem[$i].$petik.']) {
				?>
				<option value="<?=$rows['.$petik.$nama_field_sistem[$i].$petik.'];?>" selected><?=$rows['.$petik.$nama_field_sistem[$i].$petik.'];?></option>		
				<?php
				}else{
				?>
				<option value="<?=$rows['.$petik.$nama_field_sistem[$i].$petik.'];?>"><?=$rows['.$petik.$nama_field_sistem[$i].$petik.'];?></option>		
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