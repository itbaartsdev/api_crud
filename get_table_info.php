<?php
include 'conf/koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$tableName = isset($_POST['table_name']) ? trim($_POST['table_name']) : '';

if (empty($tableName)) {
    echo json_encode(['success' => false, 'message' => 'Table name is required']);
    exit;
}

// Validate table name to prevent SQL injection
if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table name']);
    exit;
}

try {
    // Check if table exists
    $checkTable = mysqli_query($koneksi, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($checkTable) === 0) {
        echo json_encode(['success' => false, 'message' => 'Table does not exist']);
        exit;
    }
    
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
        
        $fields[] = [
            'field_name' => $row['field_name'],
            'display_name' => !empty($row['comment']) ? $row['comment'] : ucwords(str_replace('_', ' ', $row['field_name'])),
            'data_type' => $row['data_type'],
            'column_type' => $row['column_type'],
            'is_nullable' => $row['is_nullable'],
            'column_key' => $row['column_key'],
            'default_value' => $row['default_value'],
            'extra' => $row['extra'],
            'comment' => $row['comment']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'table_name' => $tableName,
        'fields' => $fields
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 