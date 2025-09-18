<?php
include 'conf/koneksi.php';

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