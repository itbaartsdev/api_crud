<?php
/**
 * KONTROL_PROSES.PHP - Advanced Adaptive Panel Generator Processor
 * Core processing engine for the adaptive code generator system.
 */

header('Content-Type: application/json; charset=utf-8');
ob_start();

// Database connection with flexible path detection
$db_paths = ['../../conf/koneksi_api.php', '../../conf/koneksi.php', '../conf/koneksi_api.php', '../conf/koneksi.php'];
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

/**
 * Advanced Adaptive Panel Generator Class
 */
class AdaptivePanelGenerator {
    private $koneksi;
    private $roles_config;
    private $backup_dir;
    private $log_file;
    
    public function __construct($database_connection) {
        $this->koneksi = $database_connection;
        $this->backup_dir = '../../backups/kontrol_backups';
        $this->log_file = '../../logs/kontrol_activity.log';
        $this->loadRolesConfig();
        $this->ensureDirectories();
    }
    
    private function loadRolesConfig() {
        $config_paths = ['../../conf/roles_config.json', '../conf/roles_config.json', 'conf/roles_config.json'];
        foreach ($config_paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $this->roles_config = json_decode($content, true);
                break;
            }
        }
        if (!$this->roles_config) {
            throw new Exception('Roles configuration file not found or invalid');
        }
    }
    
    private function ensureDirectories() {
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }
    
    private function logActivity($action, $details = '') {
        $timestamp = date('Y-m-d H:i:s');
        $user = isset($_SESSION['user']) ? $_SESSION['user'] : 'System';
        $log_entry = "[$timestamp] [$user] $action - $details\n";
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Generate role-based conditional logic
     */
    private function generateRoleConditions($access_type = 'view') {
        $conditions = [];
        $roles = array_keys($this->roles_config['roles']);
        
        foreach ($roles as $index => $role) {
            if ($index === 0) {
                $conditions[] = "if (\$level == \"$role\") {";
            } else {
                $conditions[] = "} elseif (\$level == \"$role\") {";
            }
            
            switch ($access_type) {
                case 'create':
                    $conditions[] = "    \$can_create = true;";
                    break;
                case 'edit':
                    $conditions[] = "    \$can_edit = " . ($role === 'Administrator' ? 'true' : 'true') . ";";
                    break;
                case 'delete':
                    $conditions[] = "    \$can_delete = " . ($role === 'Administrator' ? 'true' : 'false') . ";";
                    break;
                case 'approve':
                    $conditions[] = "    \$can_approve = " . (in_array($role, ['Administrator', 'Supervisor']) ? 'true' : 'false') . ";";
                    break;
                default:
                    $conditions[] = "    \$can_view = true;";
            }
        }
        
        $conditions[] = "} else {";
        $conditions[] = "    \$can_view = false; \$can_create = false; \$can_edit = false; \$can_delete = false; \$can_approve = false;";
        $conditions[] = "}";
        
        return implode("\n", $conditions);
    }
    
    /**
     * Generate adaptive panel files
     */
    public function generatePanel($panel_name, $table_name, $fields, $add_enum_status = false) {
        try {
            // Create backup
            $backup_dir = $this->createBackup($panel_name);
            
            // Create panel directory if not exists
            $panel_dir = "../../Panel/$panel_name";
            if (!is_dir($panel_dir)) {
                mkdir($panel_dir, 0755, true);
            }
            
            // Add status enum field if requested
            if ($add_enum_status) {
                $this->addStatusEnumField($table_name);
                // Add status field to fields array
                $fields[] = [
                    'field_name' => 'status',
                    'field_type' => "enum('proses','terima','tolak')"
                ];
            }
            
            // Generate files
            $form_content = $this->generateFormFile($panel_name, $table_name, $fields);
            $index_content = $this->generateIndexFile($panel_name, $table_name, $fields);
            $cetak_content = $this->generateCetakFile($panel_name, $table_name, $fields);
            
            // Write files
            file_put_contents("$panel_dir/form.php", $form_content);
            file_put_contents("$panel_dir/index.php", $index_content);
            file_put_contents("$panel_dir/cetak.php", $cetak_content);
            
            $this->logActivity('PANEL_GENERATED', "Panel: $panel_name, Table: $table_name");
            
            return [
                'success' => true,
                'message' => 'Panel generated successfully',
                'backup_dir' => $backup_dir,
                'files_created' => ['form.php', 'index.php', 'cetak.php']
            ];
            
        } catch (Exception $e) {
            $this->logActivity('PANEL_GENERATION_ERROR', $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error generating panel: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Add status enum field to table
     */
    private function addStatusEnumField($table_name) {
        $sql = "ALTER TABLE `$table_name` ADD COLUMN `status` ENUM('proses','terima','tolak') DEFAULT 'proses' AFTER `id`";
        $result = mysqli_query($this->koneksi, $sql);
        
        if (!$result) {
            // Check if column already exists
            $check_sql = "SHOW COLUMNS FROM `$table_name` LIKE 'status'";
            $check_result = mysqli_query($this->koneksi, $check_sql);
            if (mysqli_num_rows($check_result) == 0) {
                throw new Exception("Failed to add status column: " . mysqli_error($this->koneksi));
            }
        }
    }
    
    private function createBackup($panel_name) {
        $backup_timestamp = date('Y-m-d_H-i-s');
        $backup_subdir = $this->backup_dir . "/{$panel_name}_{$backup_timestamp}";
        
        if (!is_dir($backup_subdir)) {
            mkdir($backup_subdir, 0755, true);
        }
        
        $this->logActivity('BACKUP_CREATED', "Panel: $panel_name, Backup: $backup_subdir");
        return $backup_subdir;
    }
    
    /**
     * Generate form.php content
     */
    private function generateFormFile($panel_name, $table_name, $fields) {
        $role_conditions = $this->generateRoleConditions('edit');
        
        $content = "<?php\n";
        $content .= "// Role-based access control\n";
        $content .= "if (!isset(\$_SESSION['level'])) { header('Location: ../../conf/masuk.php'); exit; }\n";
        $content .= "\$level = \$_SESSION['level'];\n";
        $content .= $role_conditions . "\n";
        $content .= "if (!\$can_edit) { echo '<div class=\"alert alert-danger\">Access denied</div>'; exit; }\n\n";
        
        $content .= "if (\$_GET['form'] == 'Ubah') {\n";
        $content .= "    \$sql = mysqli_query(\$koneksi, \"SELECT * FROM $table_name WHERE id='{\$id}'\");\n";
        $content .= "    \$data = mysqli_fetch_array(\$sql);\n";
        $content .= "}\n?>\n";
        
        $content .= "<div class=\"row\"><div class=\"col-sm-12\"><div class=\"card\">\n";
        $content .= "<div class=\"card-header\"><h5>Form " . ucfirst($panel_name) . "</h5></div>\n";
        $content .= "<div class=\"card-body\">\n";
        $content .= "<form method=\"post\" action=\"<?=\$folder;?>/proses.php\" enctype=\"multipart/form-data\">\n";
        $content .= "<div class=\"row\">\n";
        
        foreach ($fields as $field) {
            if ($field['field_name'] !== 'id') {
                $content .= $this->generateFormField($field);
            }
        }
        
        $content .= "<div class=\"col-12\"><?=\$button;?> <button type=\"reset\" class=\"btn btn-danger\">Reset</button></div>\n";
        $content .= "</div></form></div></div></div></div>\n";
        
        return $content;
    }
    
    private function generateFormField($field) {
        $field_name = $field['field_name'];
        $field_type = $field['field_type'];
        $field_label = ucwords(str_replace('_', ' ', $field_name));
        
        $html = "<div class=\"col-lg-6\"><div class=\"form-group\">\n";
        $html .= "<label>$field_label</label>\n";
        
        if (strpos($field_type, 'enum') !== false) {
            preg_match("/enum\(([^)]+)\)/", $field_type, $matches);
            if (isset($matches[1])) {
                $enum_values = str_replace("'", "", $matches[1]);
                $enum_values = explode(',', $enum_values);
                
                $html .= "<select class=\"form-control\" name=\"$field_name\" required>\n";
                foreach ($enum_values as $value) {
                    $selected = ($value === 'proses') ? 'selected' : '';
                    $html .= "<option value=\"$value\" <?=(\$data['$field_name'] == '$value') ? 'selected' : '$selected';?>>" . ucfirst($value) . "</option>\n";
                }
                $html .= "</select>\n";
            }
        } elseif (strpos($field_type, 'text') !== false) {
            $html .= "<textarea class=\"form-control\" name=\"$field_name\" rows=\"3\"><?=\$data['$field_name'];?></textarea>\n";
        } elseif (strpos($field_type, 'date') !== false) {
            $html .= "<input class=\"form-control\" type=\"date\" name=\"$field_name\" value=\"<?=\$data['$field_name'];?>\" required>\n";
        } else {
            $html .= "<input class=\"form-control\" type=\"text\" name=\"$field_name\" value=\"<?=\$data['$field_name'];?>\" required>\n";
        }
        
        $html .= "</div></div>\n";
        return $html;
    }
    
    /**
     * Generate index.php content
     */
    private function generateIndexFile($panel_name, $table_name, $fields) {
        $role_conditions = $this->generateRoleConditions('view');
        
        $content = "<?php\n";
        $content .= "// Role-based access control\n";
        $content .= "if (!isset(\$_SESSION['level'])) { header('Location: ../../conf/masuk.php'); exit; }\n";
        $content .= "\$level = \$_SESSION['level'];\n";
        $content .= "\$user_id = \$_SESSION['user_id'];\n";
        $content .= $role_conditions . "\n\n";
        
        $content .= "// Data filtering based on role\n";
        $content .= "\$where_clause = '';\n";
        $content .= "if (\$level == 'Teknisi') {\n";
        $content .= "    \$where_clause = \" WHERE user_id = '\$user_id'\";\n";
        $content .= "}\n";
        $content .= "\$sql = mysqli_query(\$koneksi, \"SELECT * FROM $table_name\$where_clause ORDER BY id DESC\");\n";
        $content .= "?>\n";
        
        $content .= "<div class=\"row\"><div class=\"col-sm-12\"><div class=\"card\">\n";
        $content .= "<div class=\"card-header\">\n";
        $content .= "<h5>Data " . ucfirst($panel_name) . "</h5>\n";
        $content .= "<div class=\"card-header-right\">\n";
        $content .= "<?php if (\$can_create): ?>\n";
        $content .= "<a href=\"index.php?page=" . urlencode($panel_name) . "&form=Tambah\" class=\"btn btn-primary btn-sm\">\n";
        $content .= "<i class=\"feather icon-plus\"></i> Tambah Data</a>\n";
        $content .= "<?php endif; ?>\n";
        $content .= "</div></div>\n";
        
        $content .= "<div class=\"card-body\"><div class=\"table-responsive\">\n";
        $content .= "<table class=\"table table-striped\" id=\"pc-dt-simple\">\n";
        $content .= "<thead><tr><th>No</th>\n";
        
        foreach ($fields as $field) {
            if ($field['field_name'] !== 'id') {
                $field_label = ucwords(str_replace('_', ' ', $field['field_name']));
                $content .= "<th>$field_label</th>\n";
            }
        }
        $content .= "<th>Aksi</th></tr></thead>\n";
        
        $content .= "<tbody>\n";
        $content .= "<?php \$no = 1; while (\$data = mysqli_fetch_array(\$sql)) { ?>\n";
        $content .= "<tr><td><?=\$no++;?></td>\n";
        
        foreach ($fields as $field) {
            if ($field['field_name'] !== 'id') {
                $field_name = $field['field_name'];
                if (strpos($field['field_type'], 'enum') !== false) {
                    $content .= "<td><span class=\"badge badge-<?=(\$data['$field_name'] == 'proses') ? 'warning' : ((\$data['$field_name'] == 'terima') ? 'success' : 'danger');?>\"><?=ucfirst(\$data['$field_name']);?></span></td>\n";
                } else {
                    $content .= "<td><?=htmlspecialchars(\$data['$field_name']);?></td>\n";
                }
            }
        }
        
        $content .= "<td>\n";
        $content .= "<?php if (\$can_edit): ?>\n";
        $content .= "<a href=\"index.php?page=" . urlencode($panel_name) . "&form=Ubah&id=<?=\$data['id'];?>\" class=\"btn btn-info btn-sm\"><i class=\"feather icon-edit\"></i></a>\n";
        $content .= "<?php endif; ?>\n";
        $content .= "<?php if (\$can_delete && \$level == 'Administrator'): ?>\n";
        $content .= "<a href=\"index.php?page=" . urlencode($panel_name) . "&form=Hapus&id=<?=\$data['id'];?>\" class=\"btn btn-danger btn-sm\" onclick=\"return confirm('Yakin hapus?')\"><i class=\"feather icon-trash-2\"></i></a>\n";
        $content .= "<?php endif; ?>\n";
        $content .= "</td></tr>\n";
        $content .= "<?php } ?>\n";
        $content .= "</tbody></table></div></div></div></div></div>\n";
        
        return $content;
    }
    
    /**
     * Generate cetak.php content
     */
    private function generateCetakFile($panel_name, $table_name, $fields) {
        $content = "<?php\n";
        $content .= "// Role-based access control\n";
        $content .= "if (!isset(\$_SESSION['level'])) { header('Location: ../../conf/masuk.php'); exit; }\n";
        $content .= "\$level = \$_SESSION['level'];\n";
        $content .= "// Simple print view\n";
        $content .= "\$sql = mysqli_query(\$koneksi, \"SELECT * FROM $table_name ORDER BY id DESC\");\n";
        $content .= "?>\n";
        
        $content .= "<!DOCTYPE html><html><head><title>Laporan " . ucfirst($panel_name) . "</title>\n";
        $content .= "<style>table{width:100%;border-collapse:collapse;}th,td{border:1px solid #000;padding:8px;text-align:left;}</style>\n";
        $content .= "</head><body>\n";
        $content .= "<h2>LAPORAN " . strtoupper($panel_name) . "</h2>\n";
        $content .= "<table><thead><tr>\n";
        
        foreach ($fields as $field) {
            if ($field['field_name'] !== 'id') {
                $field_label = ucwords(str_replace('_', ' ', $field['field_name']));
                $content .= "<th>$field_label</th>\n";
            }
        }
        $content .= "</tr></thead><tbody>\n";
        
        $content .= "<?php while (\$data = mysqli_fetch_array(\$sql)) { ?>\n";
        $content .= "<tr>\n";
        foreach ($fields as $field) {
            if ($field['field_name'] !== 'id') {
                $field_name = $field['field_name'];
                $content .= "<td><?=htmlspecialchars(\$data['$field_name']);?></td>\n";
            }
        }
        $content .= "</tr>\n";
        $content .= "<?php } ?>\n";
        $content .= "</tbody></table>\n";
        $content .= "<script>window.print();</script>\n";
        $content .= "</body></html>\n";
        
        return $content;
    }
}

// Process requests
try {
    if (!$koneksi) {
        throw new Exception('Database connection failed');
    }
    
    $generator = new AdaptivePanelGenerator($koneksi);
    
    if (isset($_POST['generate_panel'])) {
        $panel_name = $_POST['panel_name'] ?? '';
        $table_name = $_POST['table_name'] ?? '';
        $add_enum_status = isset($_POST['add_enum_status']);
        
        if (empty($panel_name) || empty($table_name)) {
            throw new Exception('Panel name and table name are required');
        }
        
        // Get table fields
        $fields_query = mysqli_query($koneksi, "DESCRIBE `$table_name`");
        if (!$fields_query) {
            throw new Exception('Table not found: ' . $table_name);
        }
        
        $fields = [];
        while ($field = mysqli_fetch_array($fields_query)) {
            $fields[] = [
                'field_name' => $field['Field'],
                'field_type' => $field['Type']
            ];
        }
        
        $result = $generator->generatePanel($panel_name, $table_name, $fields, $add_enum_status);
        echo json_encode($result);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush();
?>