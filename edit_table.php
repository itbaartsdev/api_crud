<?php
// Prevent any HTML output and ensure clean JSON response
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// Define API mode to prevent session_start in connection file
define('API_MODE', true);

// Pastikan menggunakan koneksi database local dengan path yang benar
if (file_exists('conf/koneksi_api.php')) {
    include 'conf/koneksi_api.php';
} else if (file_exists('conf/koneksi.php')) {
    include 'conf/koneksi.php';
} else if (file_exists('../conf/koneksi.php')) {
    include '../conf/koneksi.php';
} else if (file_exists('../../conf/koneksi.php')) {
    include '../../conf/koneksi.php';
} else {
    // Clean any output buffer before sending JSON
    ob_clean();
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Database configuration not found']));
}

// Clean any output from includes
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$tableName = isset($_POST['table_name']) ? trim($_POST['table_name']) : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (empty($tableName)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Table name is required']);
    exit;
}

// Validate table name to prevent SQL injection
if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid table name']);
    exit;
}

// Protect system tables
$systemTables = ['user', 'setting', 'mysql', 'information_schema', 'performance_schema', 'sys'];
if (in_array(strtolower($tableName), $systemTables)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Cannot edit system tables']);
    exit;
}

try {
    if ($action === 'get_structure') {
        // Get detailed table information including comments
        $infoQuery = "SELECT 
                        COLUMN_NAME as field_name,
                        DATA_TYPE as data_type,
                        COLUMN_TYPE as column_type,
                        IS_NULLABLE as is_nullable,
                        COLUMN_KEY as column_key,
                        COLUMN_DEFAULT as default_value,
                        EXTRA as extra,
                        COLUMN_COMMENT as comment
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '$tableName' 
                    ORDER BY ORDINAL_POSITION";
        
        $infoResult = mysqli_query($koneksi, $infoQuery);
        
        if (!$infoResult) {
            throw new Exception('Failed to get table information: ' . mysqli_error($koneksi));
        }
        
        $fields = [];
        while ($row = mysqli_fetch_assoc($infoResult)) {
            // Skip system fields for editing
            if ($row['field_name'] === 'id' || $row['field_name'] === 'input_date') continue;
            
            // Check field type from comment
            $isRelation = false;
            $relationData = null;
            $displayName = $row['comment'];
            $actualFieldType = $row['data_type']; // Default to database type
            
            if (!empty($row['comment']) && strpos($row['comment'], '|') !== false) {
                $commentParts = explode('|', $row['comment']);
                
                if (count($commentParts) === 3 && $row['column_key'] === 'MUL') {
                    // This is a relation field - parse comment: display_name|table_name|field_view_name
                    $isRelation = true;
                    $relationData = [
                        'ref_table' => $commentParts[1],
                        'ref_field' => $commentParts[2]
                    ];
                    $displayName = $commentParts[0]; // Use display name part only
                    $actualFieldType = 'relation';
                } else if (count($commentParts) === 2) {
                    // This is a special field type - parse comment: display_name|field_type
                    $displayName = $commentParts[0];
                    $actualFieldType = $commentParts[1]; // Override with actual field type (e.g., 'file')
                }
            }
            
            // Fallback for display name if comment is empty or not relation
            if (empty($displayName)) {
                $displayName = ucwords(str_replace('_', ' ', $row['field_name']));
            }
            
            // Extract enum values if this is an enum field
            $enumValues = '';
            $fieldLength = '';
            if (strpos($row['column_type'], 'enum') === 0) {
                // Extract enum values from column_type like "enum('value1','value2','value3')"
                preg_match('/enum\((.*)\)/', $row['column_type'], $matches);
                $enumValues = isset($matches[1]) ? $matches[1] : '';
            } else {
                // Extract length from column_type like "varchar(255)" or "int(11)"
                preg_match('/\((\d+)\)/', $row['column_type'], $matches);
                $fieldLength = isset($matches[1]) ? $matches[1] : '';
            }
            
            $fields[] = [
                'field_name' => $row['field_name'],
                'display_name' => $displayName,
                'data_type' => $row['data_type'],
                'actual_field_type' => $actualFieldType, // The real field type (file, relation, etc.)
                'column_type' => $row['column_type'],
                'field_length' => $fieldLength,
                'enum_values' => $enumValues,
                'is_nullable' => $row['is_nullable'],
                'column_key' => $row['column_key'],
                'default_value' => $row['default_value'],
                'extra' => $row['extra'],
                'comment' => $row['comment'],
                'is_relation' => $isRelation,
                'relation_data' => $relationData
            ];
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'table_name' => $tableName,
            'fields' => $fields
        ]);
        
    } else if ($action === 'update_structure') {
        // Handle table structure updates with proper backup flow
        $newFields = isset($_POST['fields']) ? $_POST['fields'] : [];
        $tableTitle = isset($_POST['table_title']) ? $_POST['table_title'] : '';

        if (empty($newFields)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'No fields provided']);
            exit;
        }

        if (empty($tableTitle)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Table title is required']);
            exit;
        }

        try {
            // Start transaction
            mysqli_autocommit($koneksi, false);

            // 1. Backup existing folder first
            $oldFolderPath = "Panel/" . $tableTitle;
            $backupFolderPath = "Panel/" . $tableTitle . "_backup";

            if (is_dir($oldFolderPath)) {
                // Find unique backup name if backup already exists
                $counter = 1;
                $finalBackupPath = $backupFolderPath;
                while (is_dir($finalBackupPath)) {
                    $finalBackupPath = "Panel/" . $tableTitle . "_backup" . $counter;
                    $counter++;
                }

                // Rename old folder to backup
                if (!rename($oldFolderPath, $finalBackupPath)) {
                    throw new Exception('Failed to create backup folder');
                }
            }

            // 2. Backup existing images folder
            $oldImagesPath = "images/" . $tableTitle;
            $backupImagesPath = "images/" . $tableTitle . "_backup";

            if (is_dir($oldImagesPath)) {
                // Find unique backup name if backup already exists
                $counter = 1;
                $finalBackupImagesPath = $backupImagesPath;
                while (is_dir($finalBackupImagesPath)) {
                    $finalBackupImagesPath = "images/" . $tableTitle . "_backup" . $counter;
                    $counter++;
                }

                // Rename old images folder to backup
                if (!rename($oldImagesPath, $finalBackupImagesPath)) {
                    throw new Exception('Failed to create images backup folder');
                }
            }

            // 3. Process the new table structure using the existing add logic
            // We need to simulate the form data structure that proses.php expects
            $formData = prepareFormDataForEdit($tableName, $tableTitle, $newFields);

            // Include the proses.php logic to create new structure
            $result = processTableCreation($koneksi, $formData);

            if (!$result['success']) {
                // Rollback transaction if creation failed
                mysqli_rollback($koneksi);
                mysqli_autocommit($koneksi, true);
                throw new Exception($result['message']);
            }

            // Commit transaction
            mysqli_commit($koneksi);
            mysqli_autocommit($koneksi, true);

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Table structure updated successfully. Old folder backed up.',
                'backup_folder' => basename($finalBackupPath),
                'backup_images_folder' => basename($finalBackupImagesPath)
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($koneksi);
            mysqli_autocommit($koneksi, true);

            ob_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    // Rollback transaction if active
    @mysqli_rollback($koneksi);
    @mysqli_autocommit($koneksi, true);

    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Helper function to prepare form data for edit operation
function prepareFormDataForEdit($tableName, $tableTitle, $fields) {
    $formData = [
        'new_table_name' => $tableName,
        'table_display_name' => $tableTitle,
        'field_labels' => [],
        'field_names' => [],
        'field_types' => [],
        'field_lengths' => [],
        'field_properties' => [],
        'relation_table' => [],
        'relation_field' => []
    ];

    // Add primary key field
    $formData['field_labels'][] = 'ID';
    $formData['field_names'][] = 'id';
    $formData['field_types'][] = 'int';
    $formData['field_lengths'][] = '11';
    $formData['field_properties'][] = 'primary';

    // Add input_date field
    $formData['field_labels'][] = 'Input Date';
    $formData['field_names'][] = 'input_date';
    $formData['field_types'][] = 'datetime';
    $formData['field_lengths'][] = '';
    $formData['field_properties'][] = 'auto';

    // Process custom fields
    foreach ($fields as $field) {
        $formData['field_labels'][] = $field['display_name'];
        $formData['field_names'][] = $field['field_name'];

        // Map actual field type to form field type
        $fieldType = $field['actual_field_type'];
        if ($fieldType === 'relation') {
            $formData['field_types'][] = 'relation';
        } else {
            $formData['field_types'][] = $fieldType;
        }

        // Handle field length/values
        if ($fieldType === 'enum' && !empty($field['enum_values'])) {
            $formData['field_lengths'][] = $field['enum_values'];
        } else {
            $formData['field_lengths'][] = $field['field_length'];
        }

        // Handle field properties
        if ($field['column_key'] === 'PRI') {
            $formData['field_properties'][] = 'primary';
        } elseif ($field['column_key'] === 'MUL') {
            $formData['field_properties'][] = 'index';
        } elseif ($field['column_key'] === 'UNI') {
            $formData['field_properties'][] = 'unique';
        } else {
            $formData['field_properties'][] = '';
        }

        // Handle relation data
        if ($fieldType === 'relation' && isset($field['relation_data'])) {
            $formData['relation_table'][] = $field['relation_data']['ref_table'];
            $formData['relation_field'][] = $field['relation_data']['ref_field'];
        } else {
            $formData['relation_table'][] = '';
            $formData['relation_field'][] = '';
        }
    }

    return $formData;
}

// Helper function to process table creation using existing logic
function processTableCreation($koneksi, $formData) {
    try {
        // Extract form data
        $new_table_name = $formData['new_table_name'];
        $table_display_name = $formData['table_display_name'];
        $field_labels = $formData['field_labels'];
        $field_names = $formData['field_names'];
        $field_types = $formData['field_types'];
        $field_lengths = $formData['field_lengths'];
        $field_properties = $formData['field_properties'];
        $relation_table_sistem = $formData['relation_table'];
        $relation_field_sistem = $formData['relation_field'];

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

            // Skip empty fields
            if (!isset($field_names[$i]) || empty($field_names[$i]) || !isset($field_types[$i]) || empty($field_types[$i])) {
                continue;
            }

            $field_name = $field_names[$i];
            $field_type = $field_types[$i];
            $field_length = isset($field_lengths[$i]) ? $field_lengths[$i] : '';
            $field_property = isset($field_properties[$i]) ? $field_properties[$i] : '';
            $field_display_name = isset($field_labels[$i]) ? $field_labels[$i] : $field_name;

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
                    $ref_table = isset($relation_table_sistem[$i]) ? $relation_table_sistem[$i] : str_replace('id_', '', $field_name);

                    // Fixed bug: properly access $relation_field_sistem[$i] with proper array check and default fallback
                    $ref_field = 'nama'; // Default fallback
                    if (isset($relation_field_sistem) && is_array($relation_field_sistem) && array_key_exists($i, $relation_field_sistem)) {
                        if (!empty($relation_field_sistem[$i]) && $relation_field_sistem[$i] !== '') {
                            $ref_field = $relation_field_sistem[$i];
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
                $result = mysqli_query($koneksi, $add_field_sql);
                if (!$result) {
                    throw new Exception("Error adding field $field_name: " . mysqli_error($koneksi));
                }
            }
        }

        // Generate files using the same logic as proses.php
        $index_content = generateIndexFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$table_display_name."/index.php", $index_content);

        $cetak_content = generateCetakFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$table_display_name."/cetak.php", $cetak_content);

        $form_content = generateFormFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $field_lengths, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("Panel/".$table_display_name."/form.php", $form_content);

        $proses_content = generateProsesFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $total);
        file_put_contents("Panel/".$table_display_name."/proses.php", $proses_content);

        $hapus_content = generateHapusFile($table_display_name, $new_table_name, $field_names[0]);
        file_put_contents("Panel/".$table_display_name."/hapus.php", $hapus_content);

        $laporan_content = generateLaporanFile($table_display_name, $new_table_name, $field_labels, $field_names, $field_types, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("laporan/".$new_table_name.".php", $laporan_content);

        return ['success' => true, 'message' => 'Table structure updated successfully'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Include the file generation functions from proses.php
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

    // Build SQL query with explicit primary key selection to avoid JOIN conflicts
    $sql_query = "SELECT * ";
    $joins = "";

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_types[$i]) && $field_types[$i] == 'relation') {
            $field_name = $field_names[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';

            $sql_query .= ", ".$ref_table.".".$ref_field;
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
                $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';
                $content .= "
                                <td><?=\$data['".$ref_field."'];?></td>";
            } elseif ($field_type == 'file') {
                // Display file with view button
                $content .= "
                                <td>
                                    <?php if (!empty(\$data['".$field_names[$i]."'])) { ?>
                                        <a href=\"images/".$table_display_name."/<?=\$data['".$field_names[$i]."'];?>\" target=\"_blank\" class=\"btn btn-sm btn-info has-ripple\">
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
                            \$no = 1;";

    // Build SQL query with explicit primary key selection to avoid JOIN conflicts (same as index)
    $sql_query = "SELECT * ";
    $joins = "";

    for ($i = 0; $i < $total; $i++) {
        if (isset($field_types[$i]) && $field_types[$i] == 'relation') {
            $field_name = $field_names[$i];
            $ref_table = str_replace('id_', '', $field_name);
            $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';

            $sql_query .= ", ".$ref_table.".".$ref_field;
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
                $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';
                $content .= "
                                <td><?=\$data['".$ref_field."'];?></td>";
            } elseif ($field_type == 'file') {
                // Display file with view button for print page
                $content .= "
                                <td>
                                    <?php if (!empty(\$data['".$field_names[$i]."'])) { ?>
                                        <a href=\"images/".$table_display_name."/<?=\$data['".$field_names[$i]."'];?>\" target=\"_blank\" class=\"btn btn-sm btn-info has-ripple\">
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
                                <input class=\"form-control\" type=\"date\" name=\"".$field_names[$i]."\" value=\"<?=date('Y-m-d', strtotime(\$data['".$field_names[$i]."']));?>\" required>
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
                $ref_table = isset($relation_table_sistem[$i]) ? $relation_table_sistem[$i] : str_replace('id_', '', $field_name);
                $ref_field = isset($relation_field_sistem[$i]) ? $relation_field_sistem[$i] : 'nama';

                $content .= "
                        <div class=\"col-lg-12\">
                            <div class=\"form-group\">
                                <label>".$field_labels[$i]."</label>
                                <select class=\"form-control\" name=\"".$field_names[$i]."\" required>
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

\$sql = mysqli_query(\$koneksi,\"INSERT INTO ".$new_table_name;

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

\$tanggal_dari = isset(\$_POST['dari']) ? \$_POST['dari'] : \"\";
\$tanggal_sampai = isset(\$_POST['sampai']) ? \$_POST['sampai'] : \"\";

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
    \$where_conditions[] = \"DATE(input_date) >= '\".\$tanggal_dari.\"'\";
}
if (!empty(\$tanggal_sampai)) {
    \$where_conditions[] = \"DATE(input_date) <= '\".\$tanggal_sampai.\"'\";
}

if (!empty(\$where_conditions)) {
    \$sql_query .= \" WHERE \" . implode(\" AND \", \$where_conditions);
}

\$sql_query .= \" ORDER BY input_date DESC\";
\$sql = mysqli_query(\$koneksi, \$sql_query);

while (\$data = mysqli_fetch_array(\$sql)) {
    \$html .= \"<tr>
        <td align='center'>\".\$no++.\"</td>";

        // Add data fields
        for ($i = 0; $i < $total; $i++) {
            if (isset($field_names[$i]) && $field_names[$i] != 'id') {
                $field_type = isset($field_types[$i]) ? $field_types[$i] : 'text';

                if ($field_type == 'date') {
                    $content .= "
        <td class='modern-td'>\".tgl(\$data['".$field_names[$i]."']).\"|</td>";
                } elseif ($field_type == 'file') {
                    $content .= "
        <td class='modern-td'>\".(!\$data['".$field_names[$i]."'] ? 'No file' : \$data['".$field_names[$i]."']).\"|</td>";
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
