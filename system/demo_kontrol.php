<?php
/**
 * DEMO_KONTROL.PHP - Demonstration of Adaptive Panel Generator
 * 
 * This file demonstrates the usage of the advanced adaptive code generator system.
 * It shows how to use the Kontrol system to generate dynamic panel structures
 * with role-based access control integration.
 * 
 * @version 1.0
 * @author Adaptive Code Generator System
 */

// Include database connection
$db_paths = ['../conf/koneksi_api.php', '../conf/koneksi.php', 'conf/koneksi_api.php', 'conf/koneksi.php'];
$koneksi = null;
foreach ($db_paths as $path) {
    if (file_exists($path)) {
        include_once $path;
        break;
    }
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the adaptive generator
include_once 'kontrol_proses.php';

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Kontrol System Demo</title></head><body>\n";
echo "<h1>Kontrol System - Advanced Adaptive Panel Generator Demo</h1>\n";

try {
    if (!$koneksi) {
        throw new Exception('Database connection failed');
    }
    
    echo "<h2>System Information</h2>\n";
    echo "<ul>\n";
    echo "<li><strong>Database Connection:</strong> ✅ Connected</li>\n";
    
    // Check roles configuration
    $roles_config_path = '../conf/roles_config.json';
    if (file_exists($roles_config_path)) {
        $roles_content = file_get_contents($roles_config_path);
        $roles_data = json_decode($roles_content, true);
        echo "<li><strong>Roles Configuration:</strong> ✅ Available (" . count($roles_data['roles']) . " roles)</li>\n";
        
        echo "<li><strong>Configured Roles:</strong> ";
        foreach (array_keys($roles_data['roles']) as $role) {
            echo "<span style='background:#e3f2fd; padding:2px 6px; margin:2px; border-radius:3px;'>$role</span> ";
        }
        echo "</li>\n";
    } else {
        echo "<li><strong>Roles Configuration:</strong> ❌ Not found</li>\n";
    }
    
    // Check existing panels
    $panel_dirs = [];
    if (is_dir('../Panel')) {
        $panel_scan = scandir('../Panel');
        foreach ($panel_scan as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir('../Panel/' . $dir)) {
                $panel_dirs[] = $dir;
            }
        }
    }
    echo "<li><strong>Existing Panels:</strong> " . count($panel_dirs) . " panels</li>\n";
    
    // Check available tables
    $tables_query = mysqli_query($koneksi, "SHOW TABLES");
    $table_count = mysqli_num_rows($tables_query);
    echo "<li><strong>Database Tables:</strong> $table_count tables available</li>\n";
    
    echo "</ul>\n";
    
    echo "<h2>Demo: Generate Adaptive Panel</h2>\n";
    
    // Create demo table if not exists
    $demo_table_sql = "
    CREATE TABLE IF NOT EXISTS `demo_permintaan` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nama_pemohon` varchar(100) NOT NULL,
        `jenis_permintaan` varchar(100) NOT NULL,
        `deskripsi` text,
        `tanggal_permintaan` date NOT NULL,
        `jumlah` int(11) DEFAULT 1,
        `status` enum('proses','terima','tolak') DEFAULT 'proses',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `user_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $result = mysqli_query($koneksi, $demo_table_sql);
    if ($result) {
        echo "<p>✅ Demo table 'demo_permintaan' created/verified successfully.</p>\n";
        
        // Insert sample data
        $sample_data_sql = "
        INSERT IGNORE INTO `demo_permintaan` (`id`, `nama_pemohon`, `jenis_permintaan`, `deskripsi`, `tanggal_permintaan`, `jumlah`, `status`) VALUES
        (1, 'John Doe', 'Komputer', 'Permintaan komputer untuk tim development', '2025-01-15', 2, 'proses'),
        (2, 'Jane Smith', 'Printer', 'Printer untuk bagian administrasi', '2025-01-16', 1, 'terima'),
        (3, 'Mike Johnson', 'Kabel Network', 'Kabel UTP Cat6 untuk network', '2025-01-17', 10, 'proses')
        ";
        mysqli_query($koneksi, $sample_data_sql);
        echo "<p>✅ Sample data inserted.</p>\n";
        
        // Initialize the generator
        $generator = new AdaptivePanelGenerator($koneksi);
        
        // Get table fields
        $fields_query = mysqli_query($koneksi, "DESCRIBE `demo_permintaan`");
        $fields = [];
        while ($field = mysqli_fetch_array($fields_query)) {
            $fields[] = [
                'field_name' => $field['Field'],
                'field_type' => $field['Type']
            ];
        }
        
        echo "<h3>Table Structure Preview</h3>\n";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; width:100%; margin:10px 0;'>\n";
        echo "<thead><tr style='background:#f5f5f5;'><th>Field Name</th><th>Field Type</th><th>Null</th><th>Key</th></tr></thead>\n";
        echo "<tbody>\n";
        
        $fields_detail_query = mysqli_query($koneksi, "SHOW COLUMNS FROM demo_permintaan");
        while ($field_detail = mysqli_fetch_array($fields_detail_query)) {
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($field_detail['Field']) . "</code></td>";
            echo "<td>" . htmlspecialchars($field_detail['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($field_detail['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($field_detail['Key']) . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table>\n";
        
        // Generate the panel
        echo "<h3>Generating Adaptive Panel...</h3>\n";
        $result = $generator->generatePanel('Demo Permintaan', 'demo_permintaan', $fields, false);
        
        if ($result['success']) {
            echo "<div style='background:#d4edda; border:1px solid #c3e6cb; color:#155724; padding:12px; border-radius:4px; margin:10px 0;'>\n";
            echo "<strong>✅ Success!</strong> " . htmlspecialchars($result['message']) . "<br>\n";
            echo "<strong>Files Generated:</strong> " . implode(', ', $result['files_created']) . "<br>\n";
            echo "<strong>Backup Location:</strong> " . htmlspecialchars($result['backup_dir']) . "\n";
            echo "</div>\n";
            
            // Show generated files content preview
            echo "<h3>Generated Files Preview</h3>\n";
            
            $generated_files = [
                'form.php' => '../Panel/Demo Permintaan/form.php',
                'index.php' => '../Panel/Demo Permintaan/index.php',
                'cetak.php' => '../Panel/Demo Permintaan/cetak.php'
            ];
            
            foreach ($generated_files as $file_name => $file_path) {
                if (file_exists($file_path)) {
                    echo "<h4>$file_name</h4>\n";
                    echo "<details>\n";
                    echo "<summary>Click to view generated code</summary>\n";
                    echo "<pre style='background:#f8f9fa; border:1px solid #dee2e6; padding:10px; border-radius:4px; overflow-x:auto; font-size:12px;'>";
                    echo htmlspecialchars(file_get_contents($file_path));
                    echo "</pre>\n";
                    echo "</details>\n";
                } else {
                    echo "<p>❌ File $file_name not found at $file_path</p>\n";
                }
            }
            
            // Show features implemented
            echo "<h3>Adaptive Features Implemented</h3>\n";
            echo "<ul>\n";
            echo "<li>✅ <strong>Role-Based Access Control:</strong> Conditional logic for Administrator, Teknisi roles</li>\n";
            echo "<li>✅ <strong>Session Management:</strong> User authentication and authorization</li>\n";
            echo "<li>✅ <strong>Data Isolation:</strong> Users can only see/edit their own data (except Administrators)</li>\n";
            echo "<li>✅ <strong>Enum Status Field:</strong> Automatic status management (proses, terima, tolak)</li>\n";
            echo "<li>✅ <strong>Form Validation:</strong> Input validation and XSS prevention</li>\n";
            echo "<li>✅ <strong>Responsive UI:</strong> Bootstrap-compatible interface</li>\n";
            echo "<li>✅ <strong>Action Buttons:</strong> Role-based Edit/Delete/Approve buttons</li>\n";
            echo "<li>✅ <strong>Print Function:</strong> PDF report generation capability</li>\n";
            echo "<li>✅ <strong>Activity Logging:</strong> All actions logged for audit trail</li>\n";
            echo "<li>✅ <strong>Auto-Backup:</strong> Configuration backup before modifications</li>\n";
            echo "</ul>\n";
            
            // Usage instructions
            echo "<h3>Usage Instructions</h3>\n";
            echo "<ol>\n";
            echo "<li>Access the panel via: <code>index.php?page=Demo Permintaan</code></li>\n";
            echo "<li>Different roles will see different options:</li>\n";
            echo "<ul>\n";
            echo "<li><strong>Administrator:</strong> Full access - Create, Edit, Delete, Approve</li>\n";
            echo "<li><strong>Teknisi:</strong> Limited access - Create, Edit own data only</li>\n";
            echo "<li><strong>Other roles:</strong> Read-only access based on configuration</li>\n";
            echo "</ul>\n";
            echo "<li>Status workflow: proses → terima/tolak (approval by Admin/Supervisor)</li>\n";
            echo "<li>Print reports via: <code>index.php?page=Demo Permintaan&form=Cetak</code></li>\n";
            echo "</ol>\n";
            
        } else {
            echo "<div style='background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; padding:12px; border-radius:4px; margin:10px 0;'>\n";
            echo "<strong>❌ Error!</strong> " . htmlspecialchars($result['message']) . "\n";
            echo "</div>\n";
        }
        
    } else {
        echo "<p>❌ Failed to create demo table: " . mysqli_error($koneksi) . "</p>\n";
    }
    
    echo "<h2>Next Steps</h2>\n";
    echo "<ol>\n";
    echo "<li>Visit <a href='../kontrol.php' target='_blank'>kontrol.php</a> to use the interactive generator</li>\n";
    echo "<li>Configure your roles in <code>conf/roles_config.json</code></li>\n";
    echo "<li>Generate panels for your existing database tables</li>\n";
    echo "<li>Customize the generated code as needed</li>\n";
    echo "<li>View activity logs to monitor system usage</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; padding:12px; border-radius:4px; margin:10px 0;'>\n";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<p><em>Demo completed. Generated by Kontrol System - Advanced Adaptive Panel Generator.</em></p>\n";
echo "</body></html>\n";
?>