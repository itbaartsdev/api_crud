<?php
// Prevent any HTML output and ensure clean JSON response
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

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
    
    // Get table structure
    $structureQuery = "DESCRIBE `$tableName`";
    $structureResult = mysqli_query($koneksi, $structureQuery);
    
    if (!$structureResult) {
        throw new Exception('Failed to get table structure: ' . mysqli_error($koneksi));
    }
    
    $fields = [];
    while ($row = mysqli_fetch_assoc($structureResult)) {
        // Skip id and input_date fields for relation
        if ($row['Field'] === 'id' || $row['Field'] === 'input_date') continue;
        
        $fields[] = [
            'name' => $row['Field'],
            'label' => ucwords(str_replace('_', ' ', $row['Field'])), // Convert field name to readable label
            'type' => $row['Type']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 