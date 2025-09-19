<?php 
// Start output buffering immediately to capture any unwanted output
ob_start();

// Turn off error display for production
error_reporting(0);
ini_set('display_errors', 0);

include 'conf/koneksi.php';

// Check if form was submitted
if (!isset($_POST['tambah'])) {
    header('Location: ../crud.php');
    exit;
}

// Validate and sanitize input data
$nama_tabel_sistem = isset($_POST['nama_tabel_sistem']) ? trim($_POST['nama_tabel_sistem']) : '';
$judul_tabel_sistem = isset($_POST['judul_tabel_sistem']) ? trim($_POST['judul_tabel_sistem']) : '';

$judul_field_sistem = isset($_POST['judul_field_sistem']) ? $_POST['judul_field_sistem'] : array();
$nama_field_sistem = isset($_POST['nama_field_sistem']) ? $_POST['nama_field_sistem'] : array();
$tipe_field_sistem = isset($_POST['tipe_field_sistem']) ? $_POST['tipe_field_sistem'] : array();
$values_field_sistem = isset($_POST['values_field_sistem']) ? $_POST['values_field_sistem'] : array();
$keterangan_field_sistem = isset($_POST['keterangan_field_sistem']) ? $_POST['keterangan_field_sistem'] : array();
$relation_table_sistem = isset($_POST['relation_table']) ? $_POST['relation_table'] : array();
$relation_field_sistem = isset($_POST['relation_field']) ? $_POST['relation_field'] : array();

// Debug logging - write to file to see what data we receive
$debug_data = "=== DEBUG FORM DATA ===\n";
$debug_data .= "Table Name: " . $nama_tabel_sistem . "\n";
$debug_data .= "Table Title: " . $judul_tabel_sistem . "\n";
$debug_data .= "Field Labels: " . print_r($judul_field_sistem, true) . "\n";
$debug_data .= "Field Names: " . print_r($nama_field_sistem, true) . "\n";
$debug_data .= "Field Types: " . print_r($tipe_field_sistem, true) . "\n";
$debug_data .= "Field Values: " . print_r($values_field_sistem, true) . "\n";
$debug_data .= "Field Properties: " . print_r($keterangan_field_sistem, true) . "\n";
$debug_data .= "Relation Tables: " . print_r($relation_table_sistem, true) . "\n";
$debug_data .= "Relation Fields: " . print_r($relation_field_sistem, true) . "\n";
$debug_data .= "Field Count: " . count($judul_field_sistem) . "\n";
$debug_data .= "========================\n\n";
file_put_contents('debug_log.txt', $debug_data, FILE_APPEND);

// Basic validation
if (empty($nama_tabel_sistem) || empty($judul_tabel_sistem)) {
    echo "<script>alert('Table name and title are required');window.location.href='../crud.php';</script>";
    exit;
}

if (empty($judul_field_sistem) || empty($nama_field_sistem)) {
    echo "<script>alert('At least one field is required');window.location.href='../crud.php';</script>";
    exit;
}

// Function to get a unique table name by checking for existing tables
function getUniqueTableName($koneksi, $tableName) {
    $originalName = $tableName;
    $counter = 1;
    
    // Check if table exists
    $checkTable = mysqli_query($koneksi, "SHOW TABLES LIKE '$tableName'");
    
    while (mysqli_num_rows($checkTable) > 0) {
        // If table exists, rename the existing table
        $backupName = $originalName . '_backup';
        if ($counter > 1) {
            $backupName = $originalName . '_backup' . $counter;
        }
        
        // Check if backup name already exists
        $checkBackup = mysqli_query($koneksi, "SHOW TABLES LIKE '$backupName'");
        if (mysqli_num_rows($checkBackup) == 0) {
            // Rename existing table to backup name
            mysqli_query($koneksi, "RENAME TABLE `$tableName` TO `$backupName`");
            break;
        }
        $counter++;
    }
    
    return $tableName;
}

// Function to get a unique file name by checking for existing files
function getUniqueFileName($basePath, $fileName, $extension = '.php') {
    $originalName = $fileName;
    $counter = 1;
    $fullPath = $basePath . $fileName . $extension;
    
    while (file_exists($fullPath)) {
        // If file exists, rename the existing file
        $backupName = $originalName . '_backup';
        if ($counter > 1) {
            $backupName = $originalName . '_backup' . $counter;
        }
        
        $backupPath = $basePath . $backupName . $extension;
        if (!file_exists($backupPath)) {
            // Rename existing file to backup name
            rename($fullPath, $backupPath);
            break;
        }
        $counter++;
    }
    
    return $fileName;
}

// Function to get a unique folder name by checking for existing folders
function getUniqueFolderName($basePath, $folderName) {
    $originalName = $folderName;
    $counter = 1;
    $fullPath = $basePath . $folderName;
    
    while (is_dir($fullPath)) {
        // If folder exists, rename the existing folder
        $backupName = $originalName . '_backup';
        if ($counter > 1) {
            $backupName = $originalName . '_backup' . $counter;
        }
        
        $backupPath = $basePath . $backupName;
        if (!is_dir($backupPath)) {
            // Rename existing folder to backup name
            rename($fullPath, $backupPath);
            break;
        }
        $counter++;
    }
    
    return $folderName;
}

if (isset($_POST['tambah'])) {
    try {
        // Completely disable any output during processing
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start with a fresh buffer to capture everything
        ob_start();
        
        // Handle duplicate tables
        $nama_tabel_sistem = getUniqueTableName($koneksi, $nama_tabel_sistem);
        
        // Handle duplicate laporan files
        $nama_tabel_sistem = getUniqueFileName("laporan/", $nama_tabel_sistem);
        
        // Handle duplicate folders for Panel
        $judul_tabel_sistem = getUniqueFolderName("Panel/", $judul_tabel_sistem);
        if (!is_dir("Panel/".$judul_tabel_sistem)) {
            mkdir("Panel/".$judul_tabel_sistem, 0755, true);
        }
        
        // Handle duplicate folders for images
        $judul_tabel_sistem_images = getUniqueFolderName("images/", $judul_tabel_sistem);
        if (!is_dir("images/".$judul_tabel_sistem_images)) {
            mkdir("images/".$judul_tabel_sistem_images, 0755, true);
        }
        
        $total = count($judul_field_sistem);

        // First, create the table with basic structure
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `$nama_tabel_sistem` (`id` INT(11) AUTO_INCREMENT PRIMARY KEY)";
        $tabel = mysqli_query($koneksi, $createTableSQL);
        if (!$tabel) {
            throw new Exception("Error creating table: " . mysqli_error($koneksi));
        }
        
        // Add input_date field automatically to every table
        $checkInputDate = mysqli_query($koneksi, "SHOW COLUMNS FROM `$nama_tabel_sistem` LIKE 'input_date'");
        if (mysqli_num_rows($checkInputDate) == 0) {
            $tabel = mysqli_query($koneksi,"ALTER TABLE `$nama_tabel_sistem` ADD `input_date` DATETIME DEFAULT CURRENT_TIMESTAMP");
        }

        // Now process all custom fields (skip id and input_date as they're already handled)
        $debug_data .= "=== PROCESSING FIELDS ===\n";
        for ($i=0; $i < $total; $i++) {
            $debug_data .= "Processing field $i: " . (isset($nama_field_sistem[$i]) ? $nama_field_sistem[$i] : 'EMPTY') . "\n";
            
            // Skip the primary key field (id) and input_date field as they're already created
            if (isset($nama_field_sistem[$i]) && ($nama_field_sistem[$i] == 'id' || $nama_field_sistem[$i] == 'input_date')) {
                $debug_data .= "  -> Skipping auto field: " . $nama_field_sistem[$i] . "\n";
                continue;
            }
            
            // Skip empty fields
            if (!isset($nama_field_sistem[$i]) || empty($nama_field_sistem[$i]) || !isset($tipe_field_sistem[$i]) || empty($tipe_field_sistem[$i])) {
                $debug_data .= "  -> Skipping empty field at index $i\n";
                continue;
            }
            
            $field_name = $nama_field_sistem[$i];
            $field_type = $tipe_field_sistem[$i];
            $field_length = isset($values_field_sistem[$i]) ? $values_field_sistem[$i] : '';
            $field_property = isset($keterangan_field_sistem[$i]) ? $keterangan_field_sistem[$i] : '';
            $field_display_name = isset($judul_field_sistem[$i]) ? $judul_field_sistem[$i] : $field_name;
            
            $debug_data .= "  -> Adding field: $field_name ($field_type) - '$field_display_name'\n";
            
            // Build SQL for adding field
            $add_field_sql = "";
            
            if ($field_property == "index") {
                if (!empty($field_length)) {
                    $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` $field_type($field_length) COMMENT '$field_display_name', ADD INDEX(`$field_name`)";
                } else {
                    $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` $field_type COMMENT '$field_display_name', ADD INDEX(`$field_name`)";
                }
            } else {
                // Add regular fields
                if ($field_type == "relation") {
                    // Relation fields are always int(11) with index
                    // Comment format: display_name|table_name|field_view_name
                    $ref_table = isset($relation_table_sistem[$i]) ? $relation_table_sistem[$i] : str_replace('id_', '', $field_name);
                    $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';
                    $relation_comment = "$field_display_name|$ref_table|$ref_field";
                    $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` int(11) COMMENT '$relation_comment', ADD INDEX(`$field_name`)";
                } else if ($field_type == "year" || $field_type == "date" || $field_type == "datetime" || $field_type == "time" || $field_type == "timestamp") {
                    $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` $field_type COMMENT '$field_display_name'";
                } else if ($field_type == "file") {
                    $file_comment = "$field_display_name|file";
                    $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` text COMMENT '$file_comment'";
                } else if ($field_type == "text") {
                    $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` text COMMENT '$field_display_name'";
                } else if ($field_type == "boolean") {
                    $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` tinyint(1) COMMENT '$field_display_name'";
                } else {
                    // For fields with length specification
                    if (!empty($field_length)) {
                        $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` $field_type($field_length) COMMENT '$field_display_name'";
                    } else {
                        // Default length for varchar if not specified
                        if ($field_type == "varchar") {
                            $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` varchar(255) COMMENT '$field_display_name'";
                        } else if ($field_type == "int") {
                            $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` int(11) COMMENT '$field_display_name'";
                        } else {
                            $add_field_sql = "ALTER TABLE `$nama_tabel_sistem` ADD `$field_name` $field_type COMMENT '$field_display_name'";
                        }
                    }
                }
                
                // Add field property constraints
                if ($field_property == "not_null") {
                    $add_field_sql .= " NOT NULL";
                } else if ($field_property == "unique") {
                    $add_field_sql .= ", ADD UNIQUE(`$field_name`)";
                }
            }
            
            if (!empty($add_field_sql)) {
                $debug_data .= "  -> SQL: $add_field_sql\n";
                $result = mysqli_query($koneksi, $add_field_sql);
                if (!$result) {
                    $debug_data .= "  -> ERROR: " . mysqli_error($koneksi) . "\n";
                } else {
                    $debug_data .= "  -> SUCCESS\n";
                }
            }
        }
        $debug_data .= "=== END PROCESSING ===\n\n";
        file_put_contents('debug_log.txt', $debug_data, FILE_APPEND);

        // File generation with direct content creation (bypassing problematic includes)
        // This approach avoids the HTML comment output issue completely
        
        // Generate index.php file directly
        $index_content = generateIndexFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$judul_tabel_sistem."/index.php", $index_content);
        
        // Generate cetak.php file directly
        $cetak_content = generateCetakFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$judul_tabel_sistem."/cetak.php", $cetak_content);
        
        // Generate form.php file directly
        $form_content = generateFormFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $values_field_sistem, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$judul_tabel_sistem."/form.php", $form_content);
        
        // Generate proses.php file directly
        $proses_content = generateProsesFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $total);
        file_put_contents("Panel/".$judul_tabel_sistem."/proses.php", $proses_content);
        
        // Generate hapus.php file directly
        $hapus_content = generateHapusFile($judul_tabel_sistem, $nama_tabel_sistem, $nama_field_sistem[0]);
        file_put_contents("Panel/".$judul_tabel_sistem."/hapus.php", $hapus_content);
        
        // Generate laporan file
        $laporan_content = generateLaporanFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("laporan/".$nama_tabel_sistem.".php", $laporan_content);
        
        // Discard our main buffer
        ob_end_clean();
        
        // Clear any remaining output buffers and send clean response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Send success response without any unwanted output
        header('Content-Type: text/html; charset=utf-8');
        echo "<script>alert('Table created successfully!');window.location.href='../crud.php';</script>";
        exit;
        
    } catch (Exception $e) {
        // Clean all output buffers in case of error
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=utf-8');
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');window.location.href='../crud.php';</script>";
        exit;
    }
}

// Clean any remaining output at the very end
while (ob_get_level()) {
    ob_end_clean();
}

// File generation functions that create content directly without problematic includes
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
                <form method=\"POST\" action=\"laporan/".$nama_tabel_sistem.".php\" target=\"_blank\">
                    <div class=\"row\">
                        <div class=\"col-sm-5\">
                            <input class=\"form-control\" placeholder=\"Dari Tanggal\" type=\"date\"  name=\"dari\" required>
                        </div>
                        <div class=\"col-sm-6\">
                            <input class=\"form-control\" placeholder=\"Sampai Tanggal\" type=\"date\"  name=\"sampai\" required>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            \$no = 1;";
    
    // Build SQL query with explicit primary key selection to avoid JOIN conflicts (same as index)
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
            } else {
                $content .= "
                                <td><?=\$data['".$nama_field_sistem[$i]."'];?></td>";
            }
        }
    }
    
    $content .= "
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
    \$sql    = mysqli_query(\$koneksi,\"SELECT * FROM ".$nama_tabel_sistem." WHERE ".$nama_field_sistem[0]."='\$id'\");
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
                                <input class=\"form-control\" type=\"date\" name=\"".$nama_field_sistem[$i]."\" value=\"<?=date('Y-m-d', strtotime(\$data['".$nama_field_sistem[$i]."']));?>\" required>
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
                                <select class=\"form-control\" name=\"".$nama_field_sistem[$i]."\" required>
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

\$sql = mysqli_query(\$koneksi,\"INSERT INTO ".$nama_tabel_sistem." SET ".$nama_field_sistem[0]."=NULL";
    
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
\$sql = mysqli_query(\$koneksi,\"UPDATE ".$nama_tabel_sistem." SET ".$nama_field_sistem[0]."='\$id'";
    
    for ($i = 0; $i < $total; $i++) {
        if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id' && $nama_field_sistem[$i] != 'input_date') {
            $content .= ", ".$nama_field_sistem[$i]."='\$".$nama_field_sistem[$i]."'";
        }
    }
    
    $content .= " WHERE ".$nama_field_sistem[0]."='\$id'\");
echo \"<script>alert('Data berhasil dirubah!');document.location='../index.php?page=".$judul_tabel_sistem."'</script>\";
}
?>";
    
    return $content;
}

function generateHapusFile($judul_tabel_sistem, $nama_tabel_sistem, $primary_key) {
    $content = "
<?php 
\$id = \$_GET['id'];
\$sql = mysqli_query(\$koneksi,\"DELETE FROM ".$nama_tabel_sistem." WHERE ".$primary_key."='\$id'\");
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
\$sql_query = \"SELECT * FROM ".$nama_tabel_sistem."\";
\$where_conditions = array();

if (!empty(\$tanggal_dari)) {
    \$where_conditions[] = \"DATE(input_date) >= '\".$tanggal_dari.\"'\";
}
if (!empty(\$tanggal_sampai)) {
    \$where_conditions[] = \"DATE(input_date) <= '\".$tanggal_sampai.\"'\";
}

if (!empty(\$where_conditions)) {
    \$sql_query .= \" WHERE \" . implode(\" AND \", \$where_conditions);
}

\$sql_query .= \" ORDER BY input_date DESC\";
\$sql = mysqli_query(\$koneksi, \$sql_query);

while (\$data = mysqli_fetch_array(\$sql)) {
    \$html .= \"<tr>
        <td align='center'>\".$no++.\"</td>";
        
// Add data fields
for ($i = 0; $i < $total; $i++) {
    if (isset($nama_field_sistem[$i]) && $nama_field_sistem[$i] != 'id') {
        $field_type = isset($tipe_field_sistem[$i]) ? $tipe_field_sistem[$i] : 'text';
        
        if ($field_type == 'date') {
            $content .= "
        <td class='modern-td'>\".tgl(\$data['".$nama_field_sistem[$i]."']).\"|</td>";
        } elseif ($field_type == 'file') {
            $content .= "
        <td class='modern-td'>\".(!\$data['".$nama_field_sistem[$i]."'] ? 'No file' : \$data['".$nama_field_sistem[$i]."']).\"|</td>";
        } else {
            $content .= "
        <td class='modern-td'>\".\$data['".$nama_field_sistem[$i]."'].\"|</td>";
        }
    }
}

$content .= "
        <td class='modern-td-date'>\".date(\"d/m/Y H:i\", strtotime(\$data['input_date'])).\"|</td>
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