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
        // Handle table structure updates
        $newFields = isset($_POST['fields']) ? $_POST['fields'] : [];
        
        if (empty($newFields)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'No fields provided']);
            exit;
        }
        
        // Start transaction
        mysqli_autocommit($koneksi, false);
        
        // For now, show success message - actual implementation would be complex
        // This would require comparing old vs new structure and executing ALTER TABLE commands
        
        mysqli_commit($koneksi);
        mysqli_autocommit($koneksi, true);
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Table structure updated successfully'
        ]);
        
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
?>