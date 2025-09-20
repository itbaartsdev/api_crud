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

// Basic validation
if (empty($new_table_name) || empty($table_display_name)) {
    echo "<script>alert('Table name and title are required');window.location.href='crud.php';</script>";
    exit;
}

if (empty($field_labels) || empty($field_names)) {
    echo "<script>alert('At least one field is required');window.location.href='crud.php';</script>";
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
        for ($i=0; $i < $total; $i++) {
            // Skip the primary key field (id) and input_date field as they're already created
            if (isset($field_names[$i]) && ($field_names[$i] == 'id' || $field_names[$i] == 'input_date')) {
                continue;
            }
            
            if (isset($field_properties[$i]) && $field_properties[$i] == "index") {
                if (isset($field_types[$i]) && isset($field_lengths[$i]) && isset($field_names[$i])) {
                    mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` $field_types[$i]($field_lengths[$i]), ADD INDEX(`$field_names[$i]`)");
                }
            } else {
                // Add regular fields
                if (isset($field_types[$i]) && isset($field_names[$i]) && !empty($field_names[$i])) {
                    if ($field_types[$i] == "year") {
                        mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` $field_types[$i]");
                    }else if ($field_types[$i] == "date") {
                        mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` $field_types[$i]");
                    }else if ($field_types[$i] == "datetime") {
                        mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` $field_types[$i]");
                    }else if ($field_types[$i] == "time") {
                        mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` $field_types[$i]");
                    }else if ($field_types[$i] == "file") {
                        mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` text");
                    }else{
                        // For fields with length specification
                        if (isset($field_lengths[$i]) && !empty($field_lengths[$i])) {
                            mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` $field_types[$i]($field_lengths[$i])");
                        } else {
                            // Default length for varchar if not specified
                            if ($field_types[$i] == "varchar") {
                                mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` $field_types[$i](255)");
                            } else {
                                mysqli_query($koneksi,"ALTER TABLE `$new_table_name` ADD `$field_names[$i]` $field_types[$i]");
                            }
                        }
                    }
                }
            }
        }

        // File generation with direct content creation (bypassing problematic includes)
        // This approach avoids the HTML comment output issue completely
        
        // Generate index.php file directly
        $index_content = generateIndexFile($table_display_name, $new_table_name, $field_labels, $field_names, $total);
        file_put_contents("Panel/".$table_display_name."/index.php", $index_content);
        
        // Generate cetak.php file directly
        $cetak_content = generateCetakFile($table_display_name, $new_table_name, $field_labels, $field_names, $total);
        file_put_contents("Panel/".$table_display_name."/cetak.php", $cetak_content);
        
        // Generate form.php file directly
        $form_content = generateFormFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $field_lengths, $total);
        file_put_contents("Panel/".$table_display_name."/form.php", $form_content);
        
        // Generate proses.php file directly
        $proses_content = generateProsesFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $total);
        file_put_contents("Panel/".$table_display_name."/proses.php", $proses_content);
        
        // Generate hapus.php file directly
        $hapus_content = generateHapusFile($table_display_name, $new_table_name, $field_names[0]);
        file_put_contents("Panel/".$table_display_name."/hapus.php", $hapus_content);
        
        // Generate laporan file
        $laporan_content = generateLaporanFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $total);
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
function generateIndexFile($table_display_name, $new_table_name, $field_labels, $field_names, $total) {
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
                            \$no = 1;
                            \$sql = mysqli_query(\$koneksi,\"SELECT * FROM ".$new_table_name."
                                                         \");
                            while (\$data = mysqli_fetch_array(\$sql)) {
                            \$id = \$data['".$field_names[0]."'];
                            ?>
                            <tr>
                                <td><?=\$no++;?></td>";
    
    // Add field data
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $content .= "
                                <td><?=\$data['".$field_names[$i]."'];?></td>";
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

function generateCetakFile($table_display_name, $new_table_name, $field_labels, $field_names, $total) {
    $content = "
<div class=\"row\">
    <!-- Zero config table start -->
    <div class=\"col-sm-12\">
        <div class=\"card\">
            <div class=\"card-header\">
                <form method=\"POST\" action=\"laporan/".$new_table_name.".php\" target=\"_blank\">
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
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $content .= "
                                <th>".$field_labels[$i]."</th>";
        }
    }
    
    $content .= "
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            \$no = 1;
                            \$sql = mysqli_query(\$koneksi,\"SELECT * FROM ".$new_table_name."
                                                         \");
                            while (\$data = mysqli_fetch_array(\$sql)) {
                            \$id = \$data['".$field_names[0]."'];
                            ?>
                            <tr>
                                <td><?=\$no++;?></td>";
    
    // Add field data
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $content .= "
                                <td><?=\$data['".$field_names[$i]."'];?></td>";
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

function generateFormFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $field_lengths, $total) {
    $content = "
<?php 
if (\$_GET['form'] == \"Ubah\") {
    \$sql    = mysqli_query(\$koneksi,\"SELECT * FROM ".$new_table_name." WHERE ".$field_names[0]."='\$id'\");
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
    
    // Add form fields
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';
            
            if ($field_type == 'date') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <input class=\"form-control\" type=\"date\" name=\"".$field_names[$i]."\" value=\"<?=date('Y-m-d', strtotime(\$data['".$field_names[$i]."']));?>\" required>
                            </div>
                        </div>";
            } elseif ($field_type == 'file') {
                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <div class=\"custom-file\">
                                    <input type=\"file\" class=\"custom-file-input\" name=\"".$field_names[$i]."\">
                                    <label class=\"custom-file-label\">Choose file</label>
                                </div>
                            </div>
                        </div>";
            } else {
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
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';
            
            if ($field_type == 'file') {
                $content .= "
\$file_".$field_names[$i]."  = \$_FILES['".$field_names[$i]."']['name'];
\$tmp_".$field_names[$i]."   = \$_FILES['".$field_names[$i]."']['tmp_name'];
move_uploaded_file(\$tmp_".$field_names[$i].", '../images/".$table_display_name."/'.\$file_".$field_names[$i].");
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
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $content .= ", ".$field_names[$i]."='\$".$field_names[$i]."'";
        }
    }
    
    $content .= "\");
echo \"<script>alert('Data berhasil disimpan!');document.location='../index.php?page=".$table_display_name."'</script>\";
}

if (isset(\$_POST['ubah'])) {
\$id = \$_POST['id'];
\$sql = mysqli_query(\$koneksi,\"UPDATE ".$new_table_name." SET ".$field_names[0]."='\$id'";
    
    for ($i = 0; $i < $total; $i++) {
        if (isset($field_names[$i]) && $field_names[$i] != 'id') {
            $content .= ", ".$field_names[$i]."='\$".$field_names[$i]."'";
        }
    }
    
    $content .= " WHERE ".$field_names[0]."='\$id'\");
echo \"<script>alert('Data berhasil dirubah!');document.location='../index.php?page=".$table_display_name."'</script>\";
}
?>";
    
    return $content;
}

function generateHapusFile($table_display_name, $new_table_name, $primary_key) {
    $content = "
<?php 
\$id = \$_GET['id'];
\$sql = mysqli_query(\$koneksi,\"DELETE FROM ".$new_table_name." WHERE ".$primary_key."='\$id'\");
echo \"<script>alert('Data berhasil dihapus.');window.location='index.php?page=\".$table_display_name.\"';</script>\"; 
?>";
    
    return $content;
}

function generateLaporanFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $total) {
    $content = "
<?php
\$title = \" Laporan ".$table_display_name."\";

include '../modul/pdf/head.php';

\$tanggal_dari = isset(\$_GET['tanggal_dari']) ? \$_GET['tanggal_dari'] : \"\";
\$tanggal_sampai = isset(\$_GET['tanggal_sampai']) ? \$_GET['tanggal_sampai'] : \"\";

\$html .= \"
<div class='modern-report-container'>
    <div class='modern-report-header'>
        <div class='modern-report-title'>
            <h2>Laporan ".$table_display_name."</h2>
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
    if (isset($field_names[$i]) && $field_names[$i] != 'id') {
        $content .= "
                    <th class='modern-th'>".$field_labels[$i]."</th>";
    }
}

$content .= "
                    <th class='modern-th-date'>Tanggal Input</th>
                </tr>
            </thead>
            <tbody>
\";

\$no = 1;
\$sql_query = \"SELECT * FROM ".$new_table_name."\";
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
    if (isset($field_names[$i]) && $field_names[$i] != 'id') {
        $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';
        
        if ($field_type == 'date') {
            $content .= "
        <td class='modern-td'>\".tgl(\$data['".$field_names[$i]."']).\"|</td>";
        } else {
            $content .= "
        <td class='modern-td'>\".\$data['".$field_names[$i]."'].\"|</td>";
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