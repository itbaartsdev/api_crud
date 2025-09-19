<?php
// Pastikan menggunakan koneksi database local dengan path yang benar
if (file_exists('conf/koneksi.php')) {
    include 'conf/koneksi.php';
} else if (file_exists('../conf/koneksi.php')) {
    include '../conf/koneksi.php';
} else if (file_exists('../../conf/koneksi.php')) {
    include '../../conf/koneksi.php';
} else {
    die(json_encode(['success' => false, 'message' => 'Database configuration not found']));
}

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

// Protect system tables
$systemTables = ['user', 'setting', 'mysql', 'information_schema', 'performance_schema', 'sys'];
if (in_array(strtolower($tableName), $systemTables)) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete system tables']);
    exit;
}

try {
    // Check if table exists
    $checkQuery = "SHOW TABLES LIKE '$tableName'";
    $checkResult = mysqli_query($koneksi, $checkQuery);
    
    if (mysqli_num_rows($checkResult) === 0) {
        echo json_encode(['success' => false, 'message' => 'Table does not exist']);
        exit;
    }
    
    // Start transaction
    mysqli_autocommit($koneksi, false);
    
    // Drop the table
    $dropQuery = "DROP TABLE IF EXISTS `$tableName`";
    $dropResult = mysqli_query($koneksi, $dropQuery);
    
    if (!$dropResult) {
        throw new Exception('Failed to drop table: ' . mysqli_error($koneksi));
    }
    
    // Helper function to convert table name to display name (same as index.php)
    function tableNameToDisplayName($tableName) {
        // Convert underscore to space and capitalize each word
        return ucwords(str_replace('_', ' ', $tableName));
    }
    
    // Delete corresponding Panel folder with backup handling
    $displayName = tableNameToDisplayName($tableName);
    $panelPath = '../Panel/' . $displayName;
    $folderDeleted = false;
    
    // Check all possible folder names and use the one that exists
    $possiblePaths = [
        '../Panel/' . $displayName,  // This should be the correct path
        '../Panel/' . ucfirst($tableName),
        '../Panel/' . $tableName,
        '../../Panel/' . $displayName,
        '../../Panel/' . ucfirst($tableName),
        '../../Panel/' . $tableName
    ];
    $imagesPaths = [
        '../images/' . $displayName,  // This should be the correct path
        '../images/' . ucfirst($tableName),
        '../images/' . $tableName,
        '../../images/' . $displayName,
        '../../images/' . ucfirst($tableName),
        '../../images/' . $tableName
    ];
    
    foreach ($possiblePaths as $path) {
        if (is_dir($path)) {
            $panelPath = $path; // Use the path that actually exists
            break;
        }
    }
    
    foreach ($imagesPaths as $path) {
        if (is_dir($path)) {
            $imagesPath = $path; // Use the path that actually exists
            break;
        }
    }
    
    if (is_dir($panelPath)) {
        // Delete folder permanently
        if (deleteDirectory($panelPath) && deleteDirectory($imagesPath)) {
            $folderDeleted = true;
            $folderMessage = "Panel folder deleted";
        } else {
            $folderMessage = "Warning: Could not delete Panel folder or images folder";
        }
    } else {
        $folderDeleted = true;
        $folderMessage = "No Panel folder or images folder found";
    }
    
    // Delete corresponding laporan file
    $laporanPath = '../laporan/' . $tableName . '.php';
    $laporanMessage = "";
    
    if (file_exists($laporanPath)) {
        // Delete laporan file permanently
        if (unlink($laporanPath)) {
            $laporanMessage = " Laporan file deleted";
        } else {
            $laporanMessage = " Warning: Could not delete laporan file";
        }
    } else {
        $laporanMessage = " No laporan file found";
    }
    
    // Commit transaction
    mysqli_commit($koneksi);
    mysqli_autocommit($koneksi, true);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Table deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($koneksi);
    mysqli_autocommit($koneksi, true);
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Function to recursively delete directory
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}
?>