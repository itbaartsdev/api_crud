<?php
// File generation functions extracted from proses.php
// This file contains only the functions without main execution logic

function generateIndexFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total) {
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
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $content .= "
                                <th>".$field_labels[$i]."</th>";
        }
    }
    
    $content .= "
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            \$no = 1;";
    
    // Build SQL query with JOINs for relation fields
    $sql_query = "SELECT *,".$new_table_name.".id AS primary_id ";
    $joins = "";
    $has_relation_fields = false;
    $relation_counter = 0;

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_types[$i]) && $field_types[$i] == 'relation') {
            $field_name = $field_names[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = 'nama';
            
            // Use relation_counter to map to relation arrays
            if (isset($relation_table_sistem[$relation_counter])) {
                $ref_table = $relation_table_sistem[$relation_counter];
            }
            if (isset($relation_field_sistem[$relation_counter])) {
                $ref_field = $relation_field_sistem[$relation_counter];
            }
            
            $joins .= " INNER JOIN ".$ref_table." ON ".$new_table_name.".".$field_name."=".$ref_table.".id";
            $relation_counter++;
        }
    }

    $sql_query .= " FROM ".$new_table_name.$joins;
    
    $content .= "
                            \$sql = mysqli_query(\$koneksi,\"".$sql_query."\");
                            while (\$data = mysqli_fetch_array(\$sql)) {
                            \$id = \$data['primary_id'];
                            ?>
                            <tr>
                                <td><?=\$no++;?></td>";
    
    // Add field data - use separate counter for relation fields
    $relation_counter = 0;
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';
            
            if ($field_type == 'relation') {
                // Use relation_counter to get correct field
                $ref_field = isset($relation_field_sistem[$relation_counter]) ? $relation_field_sistem[$relation_counter] : 'nama';
                $content .= "
                                <td><?=\$data['".$ref_field."'];?></td>";
                $relation_counter++;
            } elseif ($field_type == 'file') {
                // Display file with view button
                $content .= "
                                <td>
                                    <?php if (!empty(\$data['".$field_names[$i]."'])) { ?>
                                        <a href=\"../images/".$table_display_name."/<?=\$data['".$field_names[$i]."'];?>\" target=\"_blank\" class=\"btn btn-sm btn-info has-ripple\">
                                            <i class=\"fas fa-eye\"></i> View
                                        </a>
                                    <?php } else { ?>
                                        <span class=\"text-muted\">No file</span>
                                    <?php } ?>
                                </td>";
            } else {
                $content .= "
                                <td><?=\$data['".$field_names[$i]."'];?></td>";
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

function generateCetakFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total) {
    $content = "
<div class=\"row\">
    <!-- Zero config table start -->
    <div class=\"col-sm-12\">
        <div class=\"card\">
            <div class=\"card-header\">
                <form method=\"POST\" action=\"../laporan/".$new_table_name.".php\" target=\"_blank\">
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
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $content .= "
                                <th>".$field_labels[$i]."</th>";
        }
    }

    $content .= "
                                <th>Tanggal Input</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            \$no = 1;";

    // Build SQL query with JOINs for relation fields
    $sql_query = "SELECT *, ".$new_table_name.".id AS primary_id";
    $joins = "";
    $has_relation_fields = false;
    $relation_counter = 0;

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_types[$i]) && $field_types[$i] == 'relation') {
            $field_name = $field_names[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = 'nama';
            
            // Use relation_counter to map to relation arrays
            if (isset($relation_table_sistem[$relation_counter])) {
                $ref_table = $relation_table_sistem[$relation_counter];
            }
            if (isset($relation_field_sistem[$relation_counter])) {
                $ref_field = $relation_field_sistem[$relation_counter];
            }

            $joins .= " INNER JOIN ".$ref_table." ON ".$new_table_name.".".$field_name."=".$ref_table.".id";
            $relation_counter++;
        }
    }

    $sql_query .= " FROM ".$new_table_name.$joins;

    $content .= "
                            \$sql = mysqli_query(\$koneksi,\"".$sql_query."\");
                            while (\$data = mysqli_fetch_array(\$sql)) {
                            \$id = \$data['primary_id'];
                            ?>
                            <tr>
                                <td><?=\$no++;?></td>";

    // Add field data - use separate counter for relation fields
    $relation_counter = 0;
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';

            if ($field_type == 'relation') {
                // Use relation_counter to get correct field
                $ref_field = isset($relation_field_sistem[$relation_counter]) ? $relation_field_sistem[$relation_counter] : 'nama';
                $content .= "
                                <td><?=\$data['".$ref_field."'];?></td>";
                $relation_counter++;
            } elseif ($field_type == 'file') {
                // Display file with view button for print page
                $content .= "
                                <td>
                                    <?php if (!empty(\$data['".$field_names[$i]."'])) { ?>
                                        <a href=\"../images/".$table_display_name."/<?=\$data['".$field_names[$i]."'];?>\" target=\"_blank\" class=\"btn btn-sm btn-info has-ripple\">
                                            <i class=\"fas fa-eye\"></i> View
                                        </a>
                                    <?php } else { ?>
                                        <span class=\"text-muted\">No file</span>
                                    <?php } ?>
                                </td>";
            } elseif ($field_type == 'date') {
                $content .= "
                                <td><?php echo tgl(date('Y-m-d', strtotime(\$data['".$field_names[$i]."']))); ?></td>";
            } else {
                $content .= "
                                <td><?=\$data['".$field_names[$i]."'];?></td>";
            }
        }
    }

    $content .= "
                                <td><?php echo tgl(date('Y-m-d', strtotime(\$data['input_date']))); ?></td>
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

function generateFormFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $field_lengths, $relation_table_sistem, $relation_field_sistem, $total) {
    $content = "
<?php 
if (\$_GET['form'] == \"Ubah\") {
    \$sql    = mysqli_query(\$koneksi,\"SELECT * FROM ".$new_table_name." WHERE id='\$id'\");
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
        if (isset($field_names[$i]) && $field_names[$i] != 'id' && $field_names[$i] != 'input_date') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';
            
            if ($field_type == 'year') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <input id=\"".$field_names[$i]."\" class=\"form-control\" type=\"number\" max=\"99999\" name=\"".$field_names[$i]."\" value=\"<?=\$data['".$field_names[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'date') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <input class=\"form-control\" type=\"date\" name=\"".$field_names[$i]."\" value=\"<?php echo date('Y-m-d', strtotime(\$data['".$field_names[$i]."'])); ?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'datetime') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <input id=\"".$field_names[$i]."\" class=\"form-control\" type=\"datetime-local\" name=\"".$field_names[$i]."\" value=\"<?=\$data['".$field_names[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'time') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <input id=\"".$field_names[$i]."\" class=\"form-control\" type=\"time\" name=\"".$field_names[$i]."\" value=\"<?=\$data['".$field_names[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'month') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <input id=\"".$field_names[$i]."\" class=\"form-control\" type=\"month\" name=\"".$field_names[$i]."\" value=\"<?=\$data['".$field_names[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'int') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <input id=\"".$field_names[$i]."\" class=\"form-control\" type=\"number\" name=\"".$field_names[$i]."\" value=\"<?=\$data['".$field_names[$i]."'];?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'file') {
                $content .= '
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label>'.$field_labels[$i].'</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" name="'.$field_names[$i].'">
                                    <label class="custom-file-label">Choose file</label>
                                </div>								
                            </div>
                        </div>';
            } elseif ($field_type == 'enum') {
                // Handle enum field with values from field_lengths
                $enum_values = isset($field_lengths[$i]) ? $field_lengths[$i] : '';
                
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
                                <label>".$field_labels[$i]."</label>
                                <select name=\"".$field_names[$i]."\" class=\"js-example-basic-single form-control\" data-placeholder=\"Pilih Salah Satu\" id=\"".$field_names[$i]."\" required>
                                    <option value>-- Pilih ".$field_labels[$i]." --</option>
                                    <?php
                                    if (\$data['".$field_names[$i]."'] == NULL){
                                        // No current value
                                    } else {
                                        ?>
                                        <option value=\"<?=\$data['".$field_names[$i]."'];?>\" selected><?=\$data['".$field_names[$i]."'];?></option>
                                        <?php
                                    }
                                    ?>";
                
                // Add enum options
                for ($z = 0; $z < $jumlah; $z++) {
                    $option_value = $hasil[$z];
                    $content .= "
                                    <?php
                                    if(\$data['".$field_names[$i]."'] == \"".$option_value."\"){
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
                // Generate dropdown for relation field - use static counter
                $field_name = $field_names[$i];
                
                // We need to track which relation field this is (0, 1, 2, etc.)
                // Count how many relation fields we've seen before this one
                $current_relation_index = 0;
                for ($j = 0; $j < $i; $j++) {
                    if (isset($field_types[$j]) && $field_types[$j] == 'relation') {
                        $current_relation_index++;
                    }
                }
                
                $ref_table = str_replace('id_', '', $field_name); // Default fallback
                $ref_field = 'nama'; // Default fallback
                
                if (isset($relation_table_sistem[$current_relation_index])) {
                    $ref_table = $relation_table_sistem[$current_relation_index];
                }
                if (isset($relation_field_sistem[$current_relation_index])) {
                    $ref_field = $relation_field_sistem[$current_relation_index];
                }
                
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <select class=\"js-example-basic-single form-control\" name=\"".$field_names[$i]."\" required>
                                    <option value=\"\">Pilih ".$field_labels[$i]."</option>
                                    <?php 
                                    \$sql_rel = mysqli_query(\$koneksi, \"SELECT * FROM ".$ref_table." ORDER BY id\");
                                    while (\$data_rel = mysqli_fetch_array(\$sql_rel)) {
                                        \$selected = (\$data['".$field_names[$i]."'] == \$data_rel['id']) ? 'selected' : '';
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
                                <label>".$field_labels[$i]."</label>
                                <input id=\"".$field_names[$i]."\" class=\"form-control\" type=\"text\" name=\"".$field_names[$i]."\" value=\"<?=\$data['".$field_names[$i]."'];?>\" required>
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

function generateProsesFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $total) {
    $content = "
<?php 
include '../../conf/koneksi.php';";
    
    // Add variable declarations
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id' && $field_names[$i] != 'input_date') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';
            
            if ($field_type == 'file') {
                $content .= "
\$file_".$field_names[$i]."  = \$_FILES['".$field_names[$i]."']['name'];
\$tmp_".$field_names[$i]."   = \$_FILES['".$field_names[$i]."']['tmp_name'];
move_uploaded_file(\$tmp_".$field_names[$i].", '../../images/".$table_display_name."/'.\$file_".$field_names[$i].");
\$".$field_names[$i]." = \$file_".$field_names[$i].";";
            } elseif ($field_type == 'date') {
                $content .= "
\$".$field_names[$i]." = date('Y-m-d', strtotime(\$_POST['".$field_names[$i]."']));";
            } else {
                $content .= "
\$".$field_names[$i]." = \$_POST['".$field_names[$i]."'];";
            }
        }
    }
    
    // Add INSERT logic
    $content .= "

if (isset(\$_POST['tambah'])) {

\$sql = mysqli_query(\$koneksi,\"INSERT INTO ".$new_table_name." SET id=NULL";
    
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id' && $field_names[$i] != 'input_date') {
            $content .= ", ".$field_names[$i]."='\$".$field_names[$i]."'";
        }
    }
    
    $content .= "\");
echo \"<script>alert('Data berhasil disimpan!');document.location='../index.php?page=".$table_display_name."'</script>\";
}

if (isset(\$_POST['ubah'])) {
\$id = \$_POST['id'];
\$sql = mysqli_query(\$koneksi,\"UPDATE ".$new_table_name." SET id='\$id'";

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id' && $field_names[$i] != 'input_date') {
            $content .= ", ".$field_names[$i]."='\$".$field_names[$i]."'";
        }
    }

    $content .= " WHERE id='\$id'\");
echo \"<script>alert('Data berhasil dirubah!');document.location='../index.php?page=".$table_display_name."'</script>\";
}
?>";
    
    return $content;
}

function generateHapusFile($table_display_name, $new_table_name, $primary_key) {
    $content = "
<?php
\$id = \$_GET['id'];
\$sql = mysqli_query(\$koneksi,\"DELETE FROM ".$new_table_name." WHERE id='\$id'\");
echo \"<script>alert('Data berhasil dihapus.');window.location='index.php?page=".$table_display_name."';</script>\";
?>";

    return $content;
}

function generateLaporanFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total) {
    $content = "
<?php
\$title = \" Laporan ".$table_display_name."\";

include '../modul/pdf/head.php';

\$html .= \"
<div class='modern-report-container'>
    <div class='modern-table-wrapper'>
        <table border='1' width='100%' class='display modern-table'>
            <thead>
                <tr>
                    <th class='modern-th-number'>No</th>
";
// Add headers
for ($i = 0; $i < $total; $i++) {
    if (isset($field_names[$i]) && $field_names[$i] != 'id') {
        $content .= "<th class='modern-th'>".$field_labels[$i]."</th>";
    }
}

$content .= "
                    <th class='modern-th-date'>Tanggal Input</th>
                </tr>
            </thead>
            <tbody>\";
\$no = 1;
";

    // Build SQL query with JOINs for relation fields
    $sql_query = "SELECT *, ".$new_table_name.".id AS primary_id ";
    $joins = "";
    $has_relation_fields = false;
    $relation_counter = 0;

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_types[$i]) && $field_types[$i] == 'relation') {
            $field_name = $field_names[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = 'nama';
            
            // Use relation_counter to map to relation arrays
            if (isset($relation_table_sistem[$relation_counter])) {
                $ref_table = $relation_table_sistem[$relation_counter];
            }
            if (isset($relation_field_sistem[$relation_counter])) {
                $ref_field = $relation_field_sistem[$relation_counter];
            }
            
            $joins .= " INNER JOIN ".$ref_table." ON ".$new_table_name.".".$field_name."=".$ref_table.".id";
            $relation_counter++;
        }
    }

    $sql_query .= " FROM ".$new_table_name.$joins;

    $content .= "
                            \$sql = mysqli_query(\$koneksi,\"".$sql_query."\");
                            while (\$data = mysqli_fetch_array(\$sql)) {
                            \$id = \$data['primary_id'];
                            
\$html .= \"                            <tr>
                                <td>\".\$no++.\"</td>";

    // Add field data - use separate counter for relation fields
    $relation_counter = 0;
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';

            if ($field_type == 'relation') {
                // Use relation_counter to get correct field
                $ref_field = isset($relation_field_sistem[$relation_counter]) ? $relation_field_sistem[$relation_counter] : 'nama';
                $content .= "
                                <td>\".\$data['".$ref_field."'].\"</td>";
                $relation_counter++;
            } elseif ($field_type == 'file') {
                // Display file with view button for print page
                $content .= "
                                <td>
                                    <?php if (!empty(\$data['".$field_names[$i]."'])) { ?>
                                        <a href='../images/".$table_display_name."/\".\$data['".$field_names[$i]."']\"' target='_blank'>
                                            <i class='fas fa-eye'></i> View
                                        </a>
                                    <?php } else { ?>
                                        <span class='text-muted'>No file</span>
                                    <?php } ?>
                                </td>";
            } elseif ($field_type == 'date') {
                $content .= "
                                <td>\".tgl(date('Y-m-d', strtotime(\$data['".$field_names[$i]."']))).\"</td>";
            } else {
                $content .= "
                                <td>\".\$data['".$field_names[$i]."'].\"</td>";
            }
        }
    }

    $content .= "
                                <td>\".tgl(date('Y-m-d', strtotime(\$data['input_date']))).\"</td>
                            </tr>\";
                            }
   \$html .= \"                        </tbody>
                    </table>\";

include '../modul/pdf/foot.php';
";
    return $content;
}

?>
