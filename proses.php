<?php 
// Start output buffering immediately to capture any unwanted output
ob_start();

// Turn off error display for production
error_reporting(0);
ini_set('display_errors', 0);

// Define API mode to prevent session_start in connection file
define('API_MODE', true);

include 'conf/koneksi.php';

// Check if form was submitted
if (!isset($_POST['tambah'])) {
    header('Location: crud.php');
    exit;
}

// Validate and sanitize input data
$new_table_name = isset($_POST['new_table_name']) ? trim($_POST['new_table_name']) : '';
$table_display_name = isset($_POST['table_display_name']) ? trim($_POST['table_display_name']) : '';

$field_labels = isset($_POST['field_labels']) ? $_POST['field_labels'] : array();
$field_names = isset($_POST['field_names']) ? $_POST['field_names'] : array();
$field_types = isset($_POST['field_types']) ? $_POST['field_types'] : array();
$field_lengths = isset($_POST['field_lengths']) ? $_POST['field_lengths'] : array();
$field_properties = isset($_POST['field_properties']) ? $_POST['field_properties'] : array();
$relation_table_sistem = isset($_POST['relation_table']) ? $_POST['relation_table'] : array();
$relation_field_sistem = isset($_POST['relation_field']) ? $_POST['relation_field'] : array();

// Debug logging - write to file to see what data we receive
$debug_data = "=== DEBUG FORM DATA ===\n";
$debug_data .= "Table Name: " . $new_table_name . "\n";
$debug_data .= "Table Title: " . $table_display_name . "\n";
$debug_data .= "Field Labels: " . print_r($field_labels, true) . "\n";
$debug_data .= "Field Names: " . print_r($field_names, true) . "\n";
$debug_data .= "Field Types: " . print_r($field_types, true) . "\n";
$debug_data .= "Field Values: " . print_r($field_lengths, true) . "\n";
$debug_data .= "Field Properties: " . print_r($field_properties, true) . "\n";
$debug_data .= "Relation Tables: " . print_r($relation_table_sistem, true) . "\n";
$debug_data .= "Relation Fields: " . print_r($relation_field_sistem, true) . "\n";
$debug_data .= "Field Count: " . count($field_labels) . "\n";
$debug_data .= "========================\n\n";
file_put_contents('debug_log.txt', $debug_data, FILE_APPEND);

// Basic validation
if (empty($new_table_name) || empty($table_display_name)) {
    echo "<script>alert('Table name and title are required');window.location.href='crud.php';</script>";
    exit;
}

if (empty($field_labels) || empty($field_names)) {
    echo "<script>alert('At least one field is required');window.location.href='crud.php';</script>";
    exit;
}

// Validate table name to prevent SQL injection
if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $new_table_name)) {
    echo "<script>alert('Invalid table name format');window.location.href='crud.php';</script>";
    exit;
}

// Protect system tables
$systemTables = ['user', 'setting', 'mysql', 'information_schema', 'performance_schema', 'sys'];
if (in_array(strtolower($new_table_name), $systemTables)) {
    echo "<script>alert('Cannot create system tables');window.location.href='crud.php';</script>";
    exit;
}

// Periksa batas CRUD sebelum membuat tabel baru
include '../server.php';
$crudCheck = checkCrudLimit();
if (!$crudCheck['allowed']) {
    echo "<script>alert('" . addslashes($crudCheck['message']) . "');window.location.href='crud.php';</script>";
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
// Modified to properly backup folders before overwriting
function getUniqueFolderName($basePath, $folderName) {
    $originalName = $folderName;
    $counter = 1;
    $fullPath = $basePath . $folderName;
    
    if (is_dir($fullPath)) {
        // If folder exists, rename the existing folder to backup name
        $backupName = $originalName . '_backup';
        if ($counter > 1) {
            $backupName = $originalName . '_backup' . $counter;
        }
        
        $backupPath = $basePath . $backupName;
        $counter++;
        
        // Find a unique backup name
        while (is_dir($backupPath)) {
            $backupName = $originalName . '_backup' . $counter;
            $backupPath = $basePath . $backupName;
            $counter++;
        }
        
        // Rename existing folder to backup name
        rename($fullPath, $backupPath);
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
        $new_table_name = getUniqueTableName($koneksi, $new_table_name);
        
        // Handle duplicate laporan files
        $new_table_name = getUniqueFileName("laporan/", $new_table_name);
        
        // Handle duplicate folders for Panel
        $table_display_name = getUniqueFolderName("Panel/", $table_display_name);
        if (!is_dir("Panel/".$table_display_name)) {
            mkdir("Panel/".$table_display_name, 0755, true);
        }
        
        // Handle duplicate folders for images
        $table_display_name_images = getUniqueFolderName("images/", $table_display_name);
        if (!is_dir("images/".$table_display_name_images)) {
            mkdir("images/".$table_display_name_images, 0755, true);
        }
        
        $total = count($field_labels);

        // First, create the table with basic structure
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `$new_table_name` (`id` INT(11) AUTO_INCREMENT PRIMARY KEY)";
        $tabel = mysqli_query($koneksi, $createTableSQL);
        if (!$tabel) {
            throw new Exception("Error creating table: " . mysqli_error($koneksi));
        }
        
        // Add input_date field automatically to every table
        $checkInputDate = mysqli_query($koneksi, "SHOW COLUMNS FROM `$new_table_name` LIKE 'input_date'");
        if (mysqli_num_rows($checkInputDate) == 0) {
            $tabel = mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `input_date` DATETIME DEFAULT CURRENT_TIMESTAMP");
        }

        // Now process all custom fields (skip id and input_date as they're already handled)
        $debug_data .= "=== PROCESSING FIELDS ===\n";
        for ($i=0; $i < $total; $i++) {
            $debug_data .= "Processing field $i: " . (isset($field_names[$i]) ? $field_names[$i] : 'EMPTY') . "\n";
            
            // Skip the primary key field (id) and input_date field as they're already created
            if (isset($field_names[$i]) && ($field_names[$i] == 'id' || $field_names[$i] == 'input_date')) {
                $debug_data .= "  -> Skipping auto field: " . $field_names[$i] . "\n";
                continue;
            }
            
            // Skip empty fields
            if (!isset($field_names[$i]) || empty($field_names[$i]) || !isset($field_types[$i]) || empty($field_types[$i])) {
                $debug_data .= "  -> Skipping empty field at index $i\n";
                continue;
            }
            
            $field_name = $field_names[$i];
            $field_type = $field_types[$i];
            $field_length = isset($field_lengths[$i]) ? $field_lengths[$i] : '';
            $field_property = isset($field_properties[$i]) ? $field_properties[$i] : '';
            $field_display_name = isset($field_labels[$i]) ? $field_labels[$i] : $field_name;
            
            $debug_data .= "  -> Adding field: $field_name ($field_type) - '$field_display_name'\n";
            
            // Build SQL for adding field
            $add_field_sql = "";
            
            if ($field_property == "index") {
                if (!empty($field_length)) {
                    $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` $field_type($field_length) COMMENT '$field_display_name', ADD INDEX(`$field_name`)";
                } else {
                    $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` $field_type COMMENT '$field_display_name', ADD INDEX(`$field_name`)";
                }
            } else {
                // Add regular fields
                if ($field_type == "relation") {
                    // Relation fields are always int(11) with index
                    // Comment format: display_name|table_name|field_view_name
                    $ref_table = str_replace('id_', '', $field_name); // Default fallback
                    $ref_field = 'nama'; // Default fallback
                    
                    // Cari data relasi berdasarkan field name
                    // Modifikasi logika pencocokan untuk menangani ketidaksesuaian indeks
                    $ref_table = str_replace('id_', '', $field_name); // Default fallback
                    $ref_field = 'nama'; // Default fallback
                    
                    // Cari indeks field yang sesuai dalam field_names
                    $matching_index = null;
                    foreach ($field_names as $index => $fname) {
                        if ($fname === $field_name) {
                            $matching_index = $index;
                            break;
                        }
                    }
                    
                    // Jika ditemukan indeks yang cocok, gunakan data relasi dari indeks tersebut
                    if ($matching_index !== null && isset($relation_table_sistem[$matching_index]) && !empty($relation_table_sistem[$matching_index])) {
                        $ref_table = $relation_table_sistem[$matching_index];
                        $ref_field = isset($relation_field_sistem[$matching_index]) ? $relation_field_sistem[$matching_index] : 'nama';
                    } else {
                        // Fallback ke metode pencarian lama untuk kompatibilitas
                        foreach ($relation_table_sistem as $index => $table) {
                            if (!empty($table)) {
                                $relation_field_name = isset($field_names[$index]) ? $field_names[$index] : '';
                                if ($relation_field_name === $field_name) {
                                    $ref_table = $table;
                                    $ref_field = isset($relation_field_sistem[$index]) ? $relation_field_sistem[$index] : 'nama';
                                    break;
                                }
                            }
                        }
                    }

                    $relation_comment = "$field_display_name|$ref_table|$ref_field";
                    $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` int(11) COMMENT '$relation_comment', ADD INDEX(`$field_name`)";
                } else if ($field_type == "year" || $field_type == "date" || $field_type == "datetime" || $field_type == "time" || $field_type == "timestamp") {
                    $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` $field_type COMMENT '$field_display_name'";
                } else if ($field_type == "file") {
                    $file_comment = "$field_display_name|file";
                    $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` text COMMENT '$file_comment'";
                } else if ($field_type == "text") {
                    $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` text COMMENT '$field_display_name'";
                } else if ($field_type == "boolean") {
                    $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` tinyint(1) COMMENT '$field_display_name'";
                } else if ($field_type == "enum") {
                    // Handle enum fields with proper syntax
                    if (!empty($field_length)) {
                        $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` enum($field_length) COMMENT '$field_display_name'";
                    } else {
                        // Default to a simple enum if no values provided
                        $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` enum('option1','option2') COMMENT '$field_display_name'";
                    }
                } else {
                    // For fields with length specification
                    if (!empty($field_length)) {
                        $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` $field_type($field_length) COMMENT '$field_display_name'";
                    } else {
                        // Default length for varchar if not specified
                        if ($field_type == "varchar") {
                            $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` varchar(255) COMMENT '$field_display_name'";
                        } else if ($field_type == "int") {
                            $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` int(11) COMMENT '$field_display_name'";
                        } else {
                            $add_field_sql = "ALTER TABLE `$new_table_name` ADD `$field_name` $field_type COMMENT '$field_display_name'";
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
                    throw new Exception("Error adding field $field_name: " . mysqli_error($koneksi));
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
        $index_content = generateIndexFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$table_display_name."/index.php", $index_content);
        
        // Generate cetak.php file directly
        $cetak_content = generateCetakFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$table_display_name."/cetak.php", $cetak_content);
        
        // Generate form.php file directly
        $form_content = generateFormFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $field_lengths, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$table_display_name."/form.php", $form_content);
        
        // Generate proses.php file directly
        $proses_content = generateProsesFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $total);
        file_put_contents("Panel/".$table_display_name."/proses.php", $proses_content);
        
        // Generate hapus.php file directly
        $hapus_content = generateHapusFile($table_display_name, $new_table_name, $field_names[0]);
        file_put_contents("Panel/".$table_display_name."/hapus.php", $hapus_content);
        
        // Generate laporan file
        $laporan_content = generateLaporanFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("laporan/".$new_table_name.".php", $laporan_content);
        
        // Discard our main buffer
        ob_end_clean();
        
        // Clear any remaining output buffers and send clean response
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Send success response without any unwanted output
        header('Content-Type: text/html; charset=utf-8');
        echo "<script>alert('Table created successfully!');window.location.href='crud.php';</script>";
        exit;
        
    } catch (Exception $e) {
        // Clean all output buffers in case of error
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=utf-8');
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');window.location.href='crud.php';</script>";
        exit;
    }
}

// Clean any remaining output at the very end
while (ob_get_level()) {
    ob_end_clean();
}

// File generation functions that create content directly without problematic includes
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

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_types[$i]) && $field_types[$i] == 'relation') {
            $field_name = $field_names[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';


            $joins .= " INNER JOIN ".$ref_table." ON ".$new_table_name.".".$field_name."=".$ref_table.".id";
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
    
    // Add field data
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';
            
            if ($field_type == 'relation') {
                // Display specific relation field that was selected
                $no = 0;
                $ref_field = isset($relation_field_sistem[$no]) ? $relation_field_sistem[$no] : 'nama';
                $no++;
                $content .= "
                                <td><?=\$data['".$ref_field."'];?></td>";
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

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_types[$i]) && $field_types[$i] == 'relation') {
            $field_name = $field_names[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';


            $joins .= " INNER JOIN ".$ref_table." ON ".$new_table_name.".".$field_name."=".$ref_table.".id";
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

    // Add field data
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';

            if ($field_type == 'relation') {
                // Display specific relation field that was selected (same as index)
                $no = 0;
                $ref_field = isset($relation_field_sistem[$no]) ? $relation_field_sistem[$no] : 'nama';
                $no++;
                $content .= "
                                <td><?=\$data['".$ref_field."'];?></td>";
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
                // Generate dropdown for relation field
                $field_name = $field_names[$i];
                $u = 0;
                $ref_table = isset($relation_table_sistem[$i]) ? $relation_table_sistem[$i] : str_replace('id_', '', $field_name);
                $ref_field = isset($relation_field_sistem[$u]) ? $relation_field_sistem[$u] : 'nama';
                $u++;
                
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

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_types[$i]) && $field_types[$i] == 'relation') {
            $field_name = $field_names[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';
            $joins .= " INNER JOIN ".$ref_table." ON ".$new_table_name.".".$field_name."=".$ref_table.".id";
        }
    }

    $sql_query .= " FROM ".$new_table_name.$joins;

    $content .= "
                            \$sql = mysqli_query(\$koneksi,\"".$sql_query."\");
                            while (\$data = mysqli_fetch_array(\$sql)) {
                            \$id = \$data['primary_id'];
                            
\$html .= \"                            <tr>
                                <td>\".\$no++.\"</td>";

    // Add field data
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';

            if ($field_type == 'relation') {
                // Display specific relation field that was selected (same as index)
                $no = 0;
                $ref_field = isset($relation_field_sistem[$no]) ? $relation_field_sistem[$no] : 'nama';
                $content .= "
                                <td>\".\$data['".$ref_field."'].\"</td>";
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