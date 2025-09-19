<?php
// Prevent any HTML output and ensure clean JSON response
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// Define API mode to prevent session_start in connection file
define('API_MODE', true);

// Use API-friendly connection with flexible path detection
if (file_exists('conf/koneksi_api.php')) {
    include 'conf/koneksi_api.php';
} else if (file_exists('../conf/koneksi_api.php')) {
    include '../conf/koneksi_api.php';
} else if (file_exists('conf/koneksi.php')) {
    include 'conf/koneksi.php';
} else if (file_exists('../conf/koneksi.php')) {
    include '../conf/koneksi.php';
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database configuration not found']);
    exit;
}

// Clean any output from includes
ob_clean();
header('Content-Type: application/json');

try {
    // Get all tables
    $tablesQuery = "SHOW TABLES";
    $tablesResult = mysqli_query($koneksi, $tablesQuery);
    
    if (!$tablesResult) {
        throw new Exception('Failed to get tables: ' . mysqli_error($koneksi));
    }
    
    $tables = [];
    while ($row = mysqli_fetch_array($tablesResult)) {
        $tableName = $row[0];
        
        // Skip system tables
        if (in_array($tableName, ['user', 'setting'])) continue;
        
        $tables[] = [
            'name' => $tableName,
            'label' => ucwords(str_replace('_', ' ', $tableName)) // Convert table name to readable label
        ];
    }
    
    echo json_encode([
        'success' => true,
        'tables' => $tables
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 