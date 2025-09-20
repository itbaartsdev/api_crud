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
    echo "<script>alert('Table name and title are required');window.location.href='crud.php';</script>";
    exit;
}

if (empty($judul_field_sistem) || empty($nama_field_sistem)) {
    echo "<script>alert('At least one field is required');window.location.href='crud.php';</script>";
    exit;
}

// Periksa batas CRUD sebelum membuat tabel baru
include '../github_fetch.php';
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

                    // Fixed bug: properly access $relation_field_sistem[$i] with proper array check and default fallback
                    $ref_field = 'nama'; // Default fallback
                    if (isset($relation_field_sistem) && is_array($relation_field_sistem) && array_key_exists($i, $relation_field_sistem)) {
                        if (!empty($relation_field_sistem[$i]) && $relation_field_sistem[$i] !== '') {
                            $ref_field = $relation_field_sistem[$i];
                        }
                    }

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
?>
