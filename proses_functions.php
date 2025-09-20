<?php
// File generation functions extracted from proses.php
// This file contains only the functions without main execution logic

function generateIndexFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total) {
    $content = "
<div class=\"row\">
    <!-- Zero config table start -->
    <div class=\"col-sm-12\">
        <div class=\"card\">
            <div class=\"card-header\">
                <a href=\"index.php?page=<?=\$folder;?>&form=Tambah\" style=\"float: right;\" class=\"btn btn-sm btn-primary has-ripple\">
                    <i class=\"feather icon-plus\"></i>
                </a>
            </div>
            <div class=\"card-body\">
                <div class=\"dt-responsive table-responsive\">
                    <table id=\"simpletable\" class=\"table table-striped table-bordered nowrap\">
                        <thead>
                            <tr>
                                <th>No</th>";
    
    // Add field headers
    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id') {
            $content .= "
                                <th>".$judul_field_sistem[$i]."</th>";
        }
    }
    
    $content .= "
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            \$no = 1;";
    
    // Build SQL query with explicit primary key selection to avoid JOIN conflicts
    $sql_query = "SELECT ".$nama_tabel_sistem.".*, ".$nama_tabel_sistem.".id as primary_id";
    $joins = "";
    
    for ($i = 0; $i < $total; $i++) {
        if (isset($tipe_field_sistem[$i]) && $tipe_field_sistem[$i] == 'relation') {
            $field_name = $nama_field_sistem[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';
            
            $sql_query .= ", ".$ref_table.".".$ref_field;
            $joins .= " INNER JOIN ".$ref_table." ON ".$nama_tabel_sistem.".".$field_name."=".$ref_table.".id";
        }
    }
    
    $sql_query .= " FROM ".$nama_tabel_sistem.$joins;
    
    $content .= "
                            \$sql = mysqli_query(\$koneksi,\"".$sql_query."\");
                            while (\$data = mysqli_fetch_array(\$sql)) {
                            \$id = \$data['primary_id'];
                            ?>
                            <tr>
                                <td><?=\$no++;?></td>";
    
    // Add field data
    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id') {
            $field_type = isset($tipe_field_sistem[$i]) ? $tipe_field_sistem[$i] : 'text';
            
            if ($field_type == 'relation') {
                // Display specific relation field that was selected
                $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';
                $content .= "
                                <td><?=\$data['".$ref_field."'];?></td>";
            } elseif ($field_type == 'file') {
                // Display file with view button
                $content .= "
                                <td>
                                    <?php if (!empty(\$data['".$nama_field_sistem[$i]."'])) { ?>
                                        <a href=\"images/".$judul_tabel_sistem."/<?=\$data['".$nama_field_sistem[$i]."'];?>\" target=\"_blank\" class=\"btn btn-sm btn-info has-ripple\">
                                            <i class=\"fas fa-eye\"></i> View
                                        </a>
                                    <?php } else { ?>
                                        <span class=\"text-muted\">No file</span>
                                    <?php } ?>
                                </td>";
            } else {
                $content .= "
                                <td><?=\$data['".$nama_field_sistem[$i]."'];?></td>";
            }
        }
    }
    
    $content .= "
                                <td align=\"center\">
                                    <a href=\"index.php?page=<?=\$folder;?>&form=Ubah&id=<?=\$id;?>\" class=\"btn btn-sm btn-success has-ripple\">
                                    <i class=\"fas fa-user-edit\"></i>
                                    </a>
                                    <a href=\"index.php?page=<?=\$folder;?>&form=Hapus&id=<?=\$id;?>\" class=\"btn btn-sm btn-danger has-ripple\" onclick=\"return confirm('Yakin ingin menghapus data?')\">
                                    <i class=\"fas fa-trash-alt\"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        <!-- Zero config table end -->
</div>
";
    
    return $content;
}

function generateCetakFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total) {
    $content = "
<div class=\"row\">
    <!-- Zero config table start -->
    <div class=\"col-sm-12\">
        <div class=\"card\">
            <div class=\"card-header\">
                <form method=\"POST\" action=\"../laporan/".$nama_tabel_sistem.".php\" target=\"_blank\">
                    <div class=\"row\">
                        <div class=\"col-sm-5\">
                            <input class=\"form-control\" placeholder=\"Dari Tanggal\" type=\"date\"  name=\"tanggal_dari\" required>
                        </div>
                        <div class=\"col-sm-6\">
                            <input class=\"form-control\" placeholder=\"Sampai Tanggal\" type=\"date\"  name=\"tanggal_sampai\" required>
                        </div>
                        <div class=\"col-sm-1\">
                            <button style=\"float: right;\" class=\"btn btn-primary btn-sm\">
                                <i class=\"fa fa-print\"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class=\"card-body\">
                <div class=\"dt-responsive table-responsive\">
                    <table id=\"simpletable\" class=\"table table-striped table-bordered nowrap\">
                        <thead>
                            <tr>
                                <th>No</th>";

    // Add field headers
    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id') {
            $content .= "
                                <th>".$judul_field_sistem[$i]."</th>";
        }
    }

    $content .= "
                                <th>Tanggal Input</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            \$no = 1;";

    // Build SQL query with explicit primary key selection to avoid JOIN conflicts
    $sql_query = "SELECT ".$nama_tabel_sistem.".*, ".$nama_tabel_sistem.".id as primary_id";
    $joins = "";

    for ($i = 0; $i < $total; $i++) {
        if (isset($tipe_field_sistem[$i]) && $tipe_field_sistem[$i] == 'relation') {
            $field_name = $nama_field_sistem[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';

            $sql_query .= ", ".$ref_table.".".$ref_field;
            $joins .= " INNER JOIN ".$ref_table." ON ".$nama_tabel_sistem.".".$field_name."=".$ref_table.".id";
        }
    }

    $sql_query .= " FROM ".$nama_tabel_sistem.$joins;

    $content .= "
                            \$sql = mysqli_query(\$koneksi,\"".$sql_query."\");
                            while (\$data = mysqli_fetch_array(\$sql)) {
                            \$id = \$data['primary_id'];
                            ?>
                            <tr>
                                <td><?=\$no++;?></td>";

    // Add field data
    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id') {
            $field_type = isset($tipe_field_sistem[$i]) ? $tipe_field_sistem[$i] : 'text';

            if ($field_type == 'relation') {
                // Display specific relation field that was selected (same as index)
                $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';
                $content .= "
                                <td><?=\$data['".$ref_field."'];?></td>";
            } elseif ($field_type == 'file') {
                // Display file with view button for print page
                $content .= "
                                <td>
                                    <?php if (!empty(\$data['".$nama_field_sistem[$i]."'])) { ?>
                                        <a href=\"images/".$judul_tabel_sistem."/<?=\$data['".$nama_field_sistem[$i]."'];?>\" target=\"_blank\" class=\"btn btn-sm btn-info has-ripple\">
                                            <i class=\"fas fa-eye\"></i> View
                                        </a>
                                    <?php } else { ?>
                                        <span class=\"text-muted\">No file</span>
                                    <?php } ?>
                                </td>";
            } elseif ($field_type == 'date') {
                $content .= "
                                <td><?php echo date('Y-m-d', strtotime(\$data['".$nama_field_sistem[$i]."'])); ?></td>";
            } else {
                $content .= "
                                <td><?=\$data['".$nama_field_sistem[$i]."'];?></td>";
            }
        }
    }

    $content .= "
                                <td><?php echo date('Y-m-d', strtotime(\$data['input_date'])); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        <!-- Zero config table end -->
</div>
";

    return $content;
}

function generateFormFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $values_field_sistem, $relation_table_sistem, $relation_field_sistem, $total) {
    $content = "
<?php 
if (\$_GET['form'] == \"Ubah\") {
    \$sql    = mysqli_query(\$koneksi,\"SELECT * FROM ".$nama_tabel_sistem." WHERE id='\$id'\");
    \$data   = mysqli_fetch_array(\$sql);
}
?>
<div class=\"row\">
    <!-- [ Select2 ] start -->
    <div class=\"col-sm-12\">
        <div class=\"card select-card\">
            <div class=\"card-body\">
                <form method=\"post\" action=\"<?=\$folder;?>/proses.php\" enctype=\"multipart/form-data\">
                    <div class=\"row\">";
    
    // Add form fields using same logic as data/form/isi.php
    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id' && $nama_field_sistem[$i] != 'input_date') {
            $field_type = isset($tipe_field_sistem[$i]) ? $tipe_field_sistem[$i] : 'text';
            
            if ($field_type == 'year') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <input id=\"".$nama_field_sistem[$i]."\" class=\"form-control\" type=\"number\" max=\"99999\" name=\"".$nama_field_sistem[$i]."\" value=\"<?=\$data['".$nama_field_sistem[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'date') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <input class=\"form-control\" type=\"date\" name=\"".$nama_field_sistem[$i]."\" value=\"<?php echo date('Y-m-d', strtotime(\$data['".$nama_field_sistem[$i]."'])); ?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'datetime') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <input id=\"".$nama_field_sistem[$i]."\" class=\"form-control\" type=\"datetime-local\" name=\"".$nama_field_sistem[$i]."\" value=\"<?=\$data['".$nama_field_sistem[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'time') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <input id=\"".$nama_field_sistem[$i]."\" class=\"form-control\" type=\"time\" name=\"".$nama_field_sistem[$i]."\" value=\"<?=\$data['".$nama_field_sistem[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'month') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <input id=\"".$nama_field_sistem[$i]."\" class=\"form-control\" type=\"month\" name=\"".$nama_field_sistem[$i]."\" value=\"<?=\$data['".$nama_field_sistem[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'int') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <input id=\"".$nama_field_sistem[$i]."\" class=\"form-control\" type=\"number\" name=\"".$nama_field_sistem[$i]."\" value=\"<?=\$data['".$nama_field_sistem[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'file') {
                $content .= '
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label>'.$judul_field_sistem[$i].'</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="'.$nama_field_sistem[$i].'">
                                    <label class="custom-file-label">Choose file</label>
                                </div>								
                            </div>
                        </div>';
            } elseif ($field_type == 'enum') {
                // Handle enum field with values from values_field_sistem
                $enum_values = isset($values_field_sistem[$i]) ? $values_field_sistem[$i] : '';
                
                // Parse enum values properly: 'value1','value2','value3' -> array('value1', 'value2', 'value3')
                $hasil = [];
                if (!empty($enum_values)) {
                    // Remove outer quotes if any and split by ','
                    $enum_values = trim($enum_values, '"\'');
                    // Split by ',' and clean each value
                    $temp_hasil = explode(',', $enum_values);
                    foreach ($temp_hasil as $value) {
                        $clean_value = trim($value, '"\''); // Remove quotes around each value
                        if (!empty($clean_value)) {
                            $hasil[] = $clean_value;
                        }
                    }
                }
                $jumlah = count($hasil);
                
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <select name=\"".$nama_field_sistem[$i]."\" class=\"js-example-basic-single form-control\" data-placeholder=\"Pilih Salah Satu\" id=\"".$nama_field_sistem[$i]."\" required>
                                    <option value>-- Pilih ".$judul_field_sistem[$i]." --</option>
                                    <?php
                                    if (\$data['".$nama_field_sistem[$i]."'] == NULL){
                                        // No current value
                                    } else {
                                        ?>
                                        <option value=\"<?=\$data['".$nama_field_sistem[$i]."'];?>\" selected><?=\$data['".$nama_field_sistem[$i]."'];?></option>
                                        <?php
                                    }
                                    ?>";
                
                // Add enum options
                for ($z = 0; $z < $jumlah; $z++) {
                    $option_value = $hasil[$z];
                    $content .= "
                                    <?php
                                    if(\$data['".$nama_field_sistem[$i]."'] == \"".$option_value."\"){
                                        // Already selected above
                                    } else {
                                        ?>
                                        <option value=\"".$option_value."\">".$option_value."</option>
                                        <?php
                                    }
                                    ?>";
                }
                
                $content .= "
                                </select>
                            </div>
                        </div>";
            } elseif ($field_type == 'relation') {
                // Generate dropdown for relation field
                $field_name = $nama_field_sistem[$i];
                $ref_table = isset($relation_table_sistem[$i]) ? $relation_table_sistem[$i] : str_replace('id_', '', $field_name);
                $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';
                
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <select class=\"js-example-basic-single form-control\" name=\"".$nama_field_sistem[$i]."\" required>
                                    <option value=\"\">Pilih ".$judul_field_sistem[$i]."</option>
                                    <?php 
                                    \$sql_rel = mysqli_query(\$koneksi, \"SELECT * FROM ".$ref_table." ORDER BY id\");
                                    while (\$data_rel = mysqli_fetch_array(\$sql_rel)) {
                                        \$selected = (\$data['".$nama_field_sistem[$i]."'] == \$data_rel['id']) ? 'selected' : '';
                                        echo '<option value=\"'.\$data_rel['id'].'\" '.\$selected.'>'.\$data_rel['".$ref_field."'].'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>";
            } else {
                // Default text input for varchar, text, etc.
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$judul_field_sistem[$i]."</label>
                                <input id=\"".$nama_field_sistem[$i]."\" class=\"form-control\" type=\"text\" name=\"".$nama_field_sistem[$i]."\" value=\"<?=\$data['".$nama_field_sistem[$i]."'];?>\" required>
                            </div>
                        </div>";
            }
        }
    }
    
    $content .= "
                        <div class=\"col-xl-12\">
                        <?=\$button;?>
                        <button type=\"reset\" class=\"btn btn-danger\">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- [ Select2 ] end -->
</div>
";
    
    return $content;
}

function generateProsesFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $total) {
    $content = "
<?php 
include '../../conf/koneksi.php';";
    
    // Add variable declarations
    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id' && $nama_field_sistem[$i] != 'input_date') {
            $field_type = isset($tipe_field_sistem[$i]) ? $tipe_field_sistem[$i] : 'text';
            
            if ($field_type == 'file') {
                $content .= "
\$file_".$nama_field_sistem[$i]."  = \$_FILES['".$nama_field_sistem[$i]."']['name'];
\$tmp_".$nama_field_sistem[$i]."   = \$_FILES['".$nama_field_sistem[$i]."']['tmp_name'];
move_uploaded_file(\$tmp_".$nama_field_sistem[$i].", '../images/".$judul_tabel_sistem."/'.\$file_".$nama_field_sistem[$i].");
\$".$nama_field_sistem[$i]." = \$file_".$nama_field_sistem[$i].";";
            } elseif ($field_type == 'date') {
                $content .= "
\$".$nama_field_sistem[$i]." = date('Y-m-d', strtotime(\$_POST['".$nama_field_sistem[$i]."']));";
            } else {
                $content .= "
\$".$nama_field_sistem[$i]." = \$_POST['".$nama_field_sistem[$i]."'];";
            }
        }
    }
    
    // Add INSERT logic
    $content .= "

if (isset(\$_POST['tambah'])) {

\$sql = mysqli_query(\$koneksi,\"INSERT INTO ".$nama_tabel_sistem." SET id=NULL";
    
    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id' && $nama_field_sistem[$i] != 'input_date') {
            $content .= ", ".$nama_field_sistem[$i]."='\$".$nama_field_sistem[$i]."'";
        }
    }
    
    $content .= "\");
echo \"<script>alert('Data berhasil disimpan!');document.location='../index.php?page=".$judul_tabel_sistem."'</script>\";
}

if (isset(\$_POST['ubah'])) {
\$id = \$_POST['id'];
\$sql = mysqli_query(\$koneksi,\"UPDATE ".$nama_tabel_sistem." SET id='\$id'";

    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id' && $nama_field_sistem[$i] != 'input_date') {
            $content .= ", ".$nama_field_sistem[$i]."='\$".$nama_field_sistem[$i]."'";
        }
    }

    $content .= " WHERE id='\$id'\");
echo \"<script>alert('Data berhasil dirubah!');document.location='../index.php?page=".$judul_tabel_sistem."'</script>\";
}
?>";
    
    return $content;
}

function generateHapusFile($judul_tabel_sistem, $nama_tabel_sistem, $primary_key) {
    $content = "
<?php
\$id = \$_GET['id'];
\$sql = mysqli_query(\$koneksi,\"DELETE FROM ".$nama_tabel_sistem." WHERE id='\$id'\");
echo \"<script>alert('Data berhasil dihapus.');window.location='index.php?page=".$judul_tabel_sistem."';</script>\";
?>";

    return $content;
}

function generateLaporanFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total) {
    $content = "
<?php
\$title = \" Laporan ".$judul_tabel_sistem."\";

include '../modul/pdf/head.php';

\$tanggal_dari = isset(\$_GET['tanggal_dari']) ? \$_GET['tanggal_dari'] : \"\";
\$tanggal_sampai = isset(\$_GET['tanggal_sampai']) ? \$_GET['tanggal_sampai'] : \"\";

\$html .= \"
<div class='modern-report-container'>
    <div class='modern-report-header'>
        <div class='modern-report-title'>
            <h2>Laporan ".$judul_tabel_sistem."</h2>
            <p>Data laporan terlengkap dan terpercaya</p>
        </div>
    </div>

    <div class='modern-table-wrapper'>
        <table border='0' class='display modern-table'>
            <thead>
                <tr>
                    <th class='modern-th-number'>No</th>";

// Add headers
for ($i = 0; $i < $total; $i++) {
    if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id') {
        $content .= "
                    <th class='modern-th'>".$judul_field_sistem[$i]."</th>";
    }
}

$content .= "
                    <th class='modern-th-date'>Tanggal Input</th>
                </tr>
            </thead>
            <tbody>
\";

\$no = 1;

// Build SQL query with explicit primary key selection to avoid JOIN conflicts
\$sql_query = \"SELECT ".$nama_tabel_sistem.".*, ".$nama_tabel_sistem.".id as primary_id\";
\$joins = \"\";

for (\$i = 0; \$i < $total; \$i++) {
    if (isset(\$tipe_field_sistem[\$i]) && \$tipe_field_sistem[\$i] == 'relation') {
        \$field_name = \$nama_field_sistem[\$i];
        \$ref_table = str_replace('id_', '', \$field_name);
        \$ref_field = isset(\$relation_field_sistem[\$i]) ? \$relation_field_sistem[\$i] : 'nama';

        \$sql_query .= \", \".\$ref_table.\".\".\$ref_field;
        \$joins .= \" INNER JOIN \".\$ref_table.\" ON \".\$nama_tabel_sistem.\".\".\$field_name.\"=\".\$ref_table.\".id\";
    }
}

\$sql_query .= \" FROM \".\$nama_tabel_sistem.\$joins;

if (!empty(\$tanggal_dari) && !empty(\$tanggal_sampai)) {
    \$sql_query .= \" WHERE input_date BETWEEN '\".date('Y-m-d', strtotime(\$tanggal_dari)).\"' AND '\".date('Y-m-d', strtotime(\$tanggal_sampai)).\"'\";
} elseif (!empty(\$tanggal_dari)) {
    \$sql_query .= \" WHERE DATE(input_date) >= '\".date('Y-m-d', strtotime(\$tanggal_dari)).\"'\";
} elseif (!empty(\$tanggal_sampai)) {
    \$sql_query .= \" WHERE DATE(input_date) <= '\".date('Y-m-d', strtotime(\$tanggal_sampai)).\"'\";
}

\$sql_query .= \" ORDER BY input_date DESC\";
\$sql = mysqli_query(\$koneksi, \$sql_query);

while (\$data = mysqli_fetch_array(\$sql)) {
    \$html .= \"<tr>
        <td align='center'>\".\$no++.\"</td>";

// Add data fields
for (\$i = 0; \$i < $total; \$i++) {
    if (isset(\$nama_field_sistem[\$i]) && \$nama_field_sistem[\$i] != 'id') {
        \$field_type = isset(\$tipe_field_sistem[\$i]) ? \$tipe_field_sistem[\$i] : 'text';

        if (\$field_type == 'relation') {
            // Display specific relation field that was selected (same as index)
            \$ref_field = isset(\$relation_field_sistem[\$i]) ? \$relation_field_sistem[\$i] : 'nama';
            \$content .= "
        <td class='modern-td'>\".\$data['" . \$ref_field . "'].\"|</td>";
        } elseif (\$field_type == 'date') {
            \$content .= "
        <td class='modern-td'>\".date('Y-m-d', strtotime(\$data['" . \$nama_field_sistem[\$i] . "'])).\"|</td>";
        } elseif (\$field_type == 'file') {
            \$content .= "
        <td class='modern-td'>\".(!\$data['" . \$nama_field_sistem[\$i] . "'] ? 'No file' : \$data['" . \$nama_field_sistem[\$i] . "']).\"|</td>";
        } else {
            \$content .= "
        <td class='modern-td'>\".\$data['" . \$nama_field_sistem[\$i] . "'].\"|</td>";
        }
    }
}

\$content .= "
        <td class='modern-td-date'>\".date('Y-m-d', strtotime(\$data['input_date'])).\"|</td>
    </tr>\";
}

\$html .= \"
            </tbody>
        </table>
    </div>
</div>
\";

include '../modul/pdf/foot.php';
?>";

    return $content;
}

?>
