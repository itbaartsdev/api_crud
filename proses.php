<?php 
// Start output buffering immediately to capture any unwanted output
ob_start();

// Turn off error display for production
error_reporting(0);
ini_set('display_errors', 0);

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

// Basic validation
if (empty($new_table_name) || empty($table_display_name)) {
    echo "<script>alert('Table name and title are required');window.location.href='crud.php';</script>";
    exit;
}

if (empty($field_labels) || empty($field_names)) {
    echo "<script>alert('At least one field is required');window.location.href='crud.php';</script>";
    exit;
}

// Periksa batas CRUD sebelum membuat tabel baru
include 'server.php';
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
                    foreach ($relation_table_sistem as $index => $table) {
                        if (!empty($table)) {
                            // Cek apakah ini field relasi yang sesuai
                            $relation_field_name = isset($field_names[$index]) ? $field_names[$index] : '';
                            if ($relation_field_name === $field_name) {
                                $ref_table = $table;
                                $ref_field = isset($relation_field_sistem[$index]) ? $relation_field_sistem[$index] : 'nama';
                                break;
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
?>
