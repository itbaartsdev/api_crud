<?php
// Suppress any errors that might cause output
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to prevent any unexpected output
ob_start();

// Define API mode to prevent session_start in connection file
define('API_MODE', true);

// Use API-friendly connection
if (file_exists('conf/koneksi_api.php')) {
    include 'conf/koneksi_api.php';
} else {
    include 'conf/koneksi.php';
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

try {
    // Check if table exists
    $checkTable = mysqli_query($koneksi, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($checkTable) === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Table does not exist']);
        exit;
    }
    
    // Get current table structure with details for comparison
    $currentFields = [];
    $structureQuery = "SELECT 
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
    $structureResult = mysqli_query($koneksi, $structureQuery);
    while ($row = mysqli_fetch_assoc($structureResult)) {
        $currentFields[$row['field_name']] = [
            'data_type' => $row['data_type'],
            'column_type' => $row['column_type'],
            'is_nullable' => $row['is_nullable'],
            'column_key' => $row['column_key'],
            'default_value' => $row['default_value'],
            'extra' => $row['extra'],
            'comment' => $row['comment']
        ];
    }
    
    // Process table name changes
    $newTableName = isset($_POST['new_table_name']) ? trim($_POST['new_table_name']) : $tableName;
    $tableDisplayName = isset($_POST['table_display_name']) ? trim($_POST['table_display_name']) : '';
    
    // Validate new table name
    if (!empty($newTableName) && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $newTableName)) {
        throw new Exception('Invalid new table name format');
    }
    
    // Process field data from form
    $fieldLabels = isset($_POST['field_labels']) ? $_POST['field_labels'] : [];
    $fieldNames = isset($_POST['field_names']) ? $_POST['field_names'] : [];
    $fieldTypes = isset($_POST['field_types']) ? $_POST['field_types'] : [];
    $fieldLengths = isset($_POST['field_lengths']) ? $_POST['field_lengths'] : [];
    $fieldProperties = isset($_POST['field_properties']) ? $_POST['field_properties'] : [];
    $relationTables = isset($_POST['relation_table']) ? $_POST['relation_table'] : [];
    $relationFields = isset($_POST['relation_field']) ? $_POST['relation_field'] : [];
    
    $newFieldsAdded = [];
    $fieldsModified = [];
    $fieldsDeleted = [];
    $totalFields = count($fieldNames);
    $hasChanges = false;
    $tableRenamed = false;
    
    // Handle table rename if needed
    if ($newTableName !== $tableName) {
        // Check if new table name already exists
        $checkNewTable = mysqli_query($koneksi, "SHOW TABLES LIKE '$newTableName'");
        if (mysqli_num_rows($checkNewTable) > 0) {
            throw new Exception("Table '$newTableName' already exists");
        }
        
        // Rename table
        $renameSQL = "RENAME TABLE `$tableName` TO `$newTableName`";
        if (mysqli_query($koneksi, $renameSQL)) {
            $tableRenamed = true;
            $hasChanges = true;
            
            // Update variables for subsequent operations
            $oldTableName = $tableName;
            $tableName = $newTableName;
        } else {
            throw new Exception("Failed to rename table: " . mysqli_error($koneksi));
        }
    }
    
    // Process each field
    for ($i = 0; $i < $totalFields; $i++) {
        if (!isset($fieldNames[$i]) || empty($fieldNames[$i])) continue;
        
        $fieldName = $fieldNames[$i];
        $fieldLabel = isset($fieldLabels[$i]) ? $fieldLabels[$i] : $fieldName;
        $fieldType = isset($fieldTypes[$i]) ? $fieldTypes[$i] : 'varchar';
        $fieldLength = isset($fieldLengths[$i]) ? $fieldLengths[$i] : '';
        $fieldProperty = isset($fieldProperties[$i]) ? $fieldProperties[$i] : '';
        
        // Check if field exists
        $fieldExists = array_key_exists($fieldName, $currentFields);
        
        if ($fieldExists) {
            // Check if field has changes
            $currentField = $currentFields[$fieldName];
            $hasFieldChanges = false;
            
            // Build expected comment for comparison
            $expectedComment = '';
            if ($fieldType == 'relation') {
                $refTable = isset($relationTables[$i]) ? $relationTables[$i] : str_replace('id_', '', $fieldName);
                $refField = isset($relationFields[$i]) ? $relationFields[$i] : 'nama';
                $expectedComment = "$fieldLabel|$refTable|$refField";
            } elseif ($fieldType == 'file') {
                $expectedComment = "$fieldLabel|file";
            } else {
                $expectedComment = $fieldLabel;
            }
            
            // Build expected column type for comparison
            $expectedColumnType = '';
            if ($fieldType == 'relation') {
                $expectedColumnType = 'int(11)';
            } elseif ($fieldType == 'enum') {
                $expectedColumnType = "enum($fieldLength)";
            } elseif ($fieldType == "year" || $fieldType == "date" || $fieldType == "datetime" || $fieldType == "time" || $fieldType == "timestamp") {
                $expectedColumnType = $fieldType;
            } elseif ($fieldType == "file") {
                $expectedColumnType = "text";
            } elseif ($fieldType == "text") {
                $expectedColumnType = "text";
            } elseif ($fieldType == "boolean") {
                $expectedColumnType = "tinyint(1)";
            } else {
                // Fields with length specification
                if (!empty($fieldLength)) {
                    $expectedColumnType = "$fieldType($fieldLength)";
                } else {
                    // Default lengths
                    if ($fieldType == "varchar") {
                        $expectedColumnType = "varchar(255)";
                    } else if ($fieldType == "int") {
                        $expectedColumnType = "int(11)";
                    } else {
                        $expectedColumnType = $fieldType;
                    }
                }
            }
            
            // Check for comment changes
            if (trim($currentField['comment']) !== trim($expectedComment)) {
                $hasFieldChanges = true;
            }
            
            // Check for column type changes (including length changes)
            if (strtolower(trim($currentField['column_type'])) !== strtolower(trim($expectedColumnType))) {
                $hasFieldChanges = true;
            }
            
            // Apply changes if any detected
            if ($hasFieldChanges) {
                // Update field with new column type and comment
                $alterSQL = "ALTER TABLE `$tableName` MODIFY COLUMN `$fieldName` $expectedColumnType COMMENT '$expectedComment'";
                
                if (mysqli_query($koneksi, $alterSQL)) {
                    $fieldsModified[] = $fieldName;
                    $hasChanges = true;
                } else {
                    throw new Exception("Failed to modify field '$fieldName': " . mysqli_error($koneksi));
                }
            }
            
            continue; // Skip to next field
        }
        
        // Build ALTER TABLE SQL for new field
        $alterSQL = "ALTER TABLE `$tableName` ADD `$fieldName` ";
        
        if ($fieldType == 'relation') {
            // Handle relation fields
            $refTable = isset($relationTables[$i]) ? $relationTables[$i] : str_replace('id_', '', $fieldName);
            $refField = isset($relationFields[$i]) ? $relationFields[$i] : 'nama';
            $relationComment = "$fieldLabel|$refTable|$refField";
            $alterSQL .= "int(11) COMMENT '$relationComment', ADD INDEX(`$fieldName`)";
        } else {
            // Handle regular fields
            if ($fieldType == "year" || $fieldType == "date" || $fieldType == "datetime" || $fieldType == "time" || $fieldType == "timestamp") {
                $alterSQL .= "$fieldType COMMENT '$fieldLabel'";
            } else if ($fieldType == "file") {
                $fileComment = "$fieldLabel|file";
                $alterSQL .= "text COMMENT '$fileComment'";
            } else if ($fieldType == "text") {
                $alterSQL .= "text COMMENT '$fieldLabel'";
            } else if ($fieldType == "boolean") {
                $alterSQL .= "tinyint(1) COMMENT '$fieldLabel'";
            } else {
                // Fields with length specification
                if (!empty($fieldLength)) {
                    $alterSQL .= "$fieldType($fieldLength) COMMENT '$fieldLabel'";
                } else {
                    // Default lengths
                    if ($fieldType == "varchar") {
                        $alterSQL .= "varchar(255) COMMENT '$fieldLabel'";
                    } else if ($fieldType == "int") {
                        $alterSQL .= "int(11) COMMENT '$fieldLabel'";
                    } else {
                        $alterSQL .= "$fieldType COMMENT '$fieldLabel'";
                    }
                }
            }
            
            // Add index if specified
            if ($fieldProperty == "index") {
                $alterSQL .= ", ADD INDEX(`$fieldName`)";
            } else if ($fieldProperty == "unique") {
                $alterSQL .= ", ADD UNIQUE(`$fieldName`)";
            }
        }
        
        // Execute ALTER TABLE for new field
        if (mysqli_query($koneksi, $alterSQL)) {
            $newFieldsAdded[] = $fieldName;
            $hasChanges = true;
        } else {
            throw new Exception("Failed to add field '$fieldName': " . mysqli_error($koneksi));
        }
    }
    
    // Check for deleted fields (fields that exist in database but not in submitted form)
    $submittedFieldNames = array_filter($fieldNames); // Remove empty values
    foreach ($currentFields as $existingFieldName => $existingFieldData) {
        // Skip system fields
        if ($existingFieldName === 'id' || $existingFieldName === 'input_date') {
            continue;
        }
        
        // If field exists in database but not in submitted form, it was deleted
        if (!in_array($existingFieldName, $submittedFieldNames)) {
            // Drop the field from database
            $dropSQL = "ALTER TABLE `$tableName` DROP COLUMN `$existingFieldName`";
            if (mysqli_query($koneksi, $dropSQL)) {
                $fieldsDeleted[] = $existingFieldName;
                $hasChanges = true;
            } else {
                throw new Exception("Failed to delete field '$existingFieldName': " . mysqli_error($koneksi));
            }
        }
    }
    
    // If there are any changes (new fields, modifications, deletions, or table rename), regenerate CRUD files
    if ($hasChanges) {
        $finalDisplayName = !empty($tableDisplayName) ? $tableDisplayName : ucwords(str_replace('_', ' ', $tableName));
        
        if ($tableRenamed) {
            // Handle complete table rename with Panel folder and laporan file
            handleTableRename($oldTableName, $tableName, $finalDisplayName, $koneksi);
        } else {
            // Regular regeneration
            regenerateCRUDFiles($tableName, $koneksi, $finalDisplayName);
        }
    }
    
    // Build success message
    $messageParts = [];
    if ($tableRenamed) {
        $messageParts[] = "Table renamed from '$oldTableName' to '$tableName'";
    }
    if (count($newFieldsAdded) > 0) {
        $messageParts[] = 'Added fields: ' . implode(', ', $newFieldsAdded);
    }
    if (count($fieldsModified) > 0) {
        $messageParts[] = 'Modified fields: ' . implode(', ', $fieldsModified);
    }
    if (count($fieldsDeleted) > 0) {
        $messageParts[] = 'Deleted fields: ' . implode(', ', $fieldsDeleted);
    }
    
    if (count($messageParts) > 0) {
        $message = 'Table updated successfully. ' . implode('. ', $messageParts) . '. CRUD files regenerated.';
    } else {
        $message = 'No changes detected';
    }
    
    // Clean any unexpected output
    ob_clean();
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'fields_added' => $newFieldsAdded,
        'fields_modified' => $fieldsModified,
        'fields_deleted' => $fieldsDeleted,
        'has_changes' => $hasChanges
    ]);
    
} catch (Exception $e) {
    // Clean any unexpected output
    ob_clean();
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Function to handle complete table rename
function handleTableRename($oldTableName, $newTableName, $newDisplayName, $koneksi) {
    try {
        // Get old and new display names
        $oldDisplayName = ucwords(str_replace('_', ' ', $oldTableName));
        
        // Rename Panel folder
        $oldPanelDir = "Panel/" . $oldDisplayName;
        $newPanelDir = "Panel/" . $newDisplayName;
        
        if (is_dir($oldPanelDir)) {
            if (!rename($oldPanelDir, $newPanelDir)) {
                error_log("Failed to rename Panel folder from '$oldPanelDir' to '$newPanelDir'");
            }
        }
        
        // Rename images folder
        $oldImagesDir = "images/" . $oldDisplayName;
        $newImagesDir = "images/" . $newDisplayName;
        
        if (is_dir($oldImagesDir)) {
            if (!rename($oldImagesDir, $newImagesDir)) {
                error_log("Failed to rename images folder from '$oldImagesDir' to '$newImagesDir'");
            }
        }
        
        // Rename laporan file
        $oldLaporanFile = "laporan/" . $oldTableName . ".php";
        $newLaporanFile = "laporan/" . $newTableName . ".php";
        
        if (file_exists($oldLaporanFile)) {
            if (!rename($oldLaporanFile, $newLaporanFile)) {
                error_log("Failed to rename laporan file from '$oldLaporanFile' to '$newLaporanFile'");
            }
        }
        
        // Regenerate all CRUD files with new names
        regenerateCRUDFiles($newTableName, $koneksi, $newDisplayName);
        
    } catch (Exception $e) {
        error_log("Failed to handle table rename: " . $e->getMessage());
    }
}

// Function to regenerate CRUD files after table update using same template system as original
function regenerateCRUDFiles($tableName, $koneksi, $customDisplayName = null) {
    try {
        // Get table display name (use custom or convert snake_case to Title Case)
        $displayName = $customDisplayName ?: ucwords(str_replace('_', ' ', $tableName));
        
        // Get complete table structure including comments
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
        
        $fieldLabels = [];
        $fieldNames = [];
        $fieldTypes = [];
        $fieldValues = [];
        $relationTables = [];
        $relationFields = [];
        
        while ($row = mysqli_fetch_assoc($infoResult)) {
            $fieldName = $row['field_name'];
            $comment = $row['comment'];
            $columnType = $row['column_type'];
            
            // Skip system fields for processing
            if ($fieldName === 'id' || $fieldName === 'input_date') {
                continue;
            }
            
            $fieldNames[] = $fieldName;
            
            // Parse comment for display name and relation info
            if (!empty($comment) && strpos($comment, '|') !== false) {
                // This is a relation field - parse comment: display_name|table_name|field_view_name
                $commentParts = explode('|', $comment);
                if (count($commentParts) === 3) {
                    $fieldLabels[] = $commentParts[0];
                    $fieldTypes[] = 'relation';
                    $fieldValues[] = '';
                    $relationTables[] = $commentParts[1];
                    $relationFields[] = $commentParts[2];
                } else {
                    $fieldLabels[] = $comment;
                    $fieldTypes[] = $row['data_type'];
                    $fieldValues[] = '';
                    $relationTables[] = '';
                    $relationFields[] = '';
                }
            } else {
                $fieldLabels[] = !empty($comment) ? $comment : ucwords(str_replace('_', ' ', $fieldName));
                
                // Check if this is an enum field
                if (strpos($columnType, 'enum') === 0) {
                    $fieldTypes[] = 'enum';
                    // Extract enum values from column_type like "enum('value1','value2','value3')"
                    preg_match('/enum\((.*)\)/', $columnType, $matches);
                    $enumValues = isset($matches[1]) ? $matches[1] : '';
                    $fieldValues[] = $enumValues;
                } else {
                    $fieldTypes[] = $row['data_type'];
                    $fieldValues[] = '';
                }
                
                $relationTables[] = '';
                $relationFields[] = '';
            }
        }
        
        $total = count($fieldNames);
        
        // Create Panel directory if not exists
        $panelDir = "Panel/" . $displayName;
        if (!is_dir($panelDir)) {
            mkdir($panelDir, 0777, true);
        }
        
        // Create images directory if not exists
        $imagesDir = "images/" . $displayName;
        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0777, true);
        }
        
        // Include generate functions from proses.php
        // Temporarily capture any output during include
        ob_start();
        include_once 'proses_functions.php';
        ob_end_clean();
        
        // Set global variables for template system (same as original CRUD generator)
        $judul_tabel_sistem = $displayName;
        $nama_tabel_sistem = $tableName;
        $judul_field_sistem = $fieldLabels;
        $nama_field_sistem = $fieldNames;
        $tipe_field_sistem = $fieldTypes;
        $values_field_sistem = $fieldValues;
        $relation_table_sistem = $relationTables;
        $relation_field_sistem = $relationFields;
        $total = count($fieldNames);
        
        // Generate files using same functions as proses.php (same template system as /data)
        $index_content = generateIndexFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents($panelDir . "/index.php", $index_content);
        
        $cetak_content = generateCetakFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents($panelDir . "/cetak.php", $cetak_content);
        
        $form_content = generateFormFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $values_field_sistem, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents($panelDir . "/form.php", $form_content);
        
        $proses_content = generateProsesFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $total);
        file_put_contents($panelDir . "/proses.php", $proses_content);
        
        $hapus_content = generateHapusFile($judul_tabel_sistem, $nama_tabel_sistem, $nama_field_sistem[0]);
        file_put_contents($panelDir . "/hapus.php", $hapus_content);
        
        $laporan_content = generateLaporanFile($judul_tabel_sistem, $nama_tabel_sistem, $judul_field_sistem, $nama_field_sistem, $tipe_field_sistem, $relation_table_sistem, $relation_field_sistem, $total);
        file_put_contents("laporan/" . $tableName . ".php", $laporan_content);
        
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to regenerate CRUD files: " . $e->getMessage());
    }
}

?> 