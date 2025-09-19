<?php
/**
 * KONTROL_PROSES.PHP - Adaptive Panel Generator Implementation
 * 
 * This file contains the AdaptivePanelGenerator class which is responsible for
 * generating dynamic panel structures with role-based access control integration.
 * 
 * @version 1.0
 * @author Adaptive Code Generator System
 */

class AdaptivePanelGenerator {
    private $koneksi;
    private $backup_dir;
    
    /**
     * Constructor
     * @param mysqli $koneksi Database connection
     */
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
        $this->backup_dir = '../../backup/panel_' . date('Y-m-d_H-i-s');
    }
    
    /**
     * Generate adaptive panel files
     * @param string $panel_name Name of the panel
     * @param string $table_name Database table name
     * @param array $fields Table fields information
     * @param bool $backup_existing Whether to backup existing files
     * @return array Result of generation process
     */
    public function generatePanel($panel_name, $table_name, $fields, $backup_existing = true) {
        try {
            // Validate inputs
            if (empty($panel_name) || empty($table_name) || empty($fields)) {
                return [
                    'success' => false,
                    'message' => 'Panel name, table name, and fields are required'
                ];
            }
            
            // Create panel directory
            $panel_dir = '../../Panel/' . $panel_name;
            if (!is_dir($panel_dir)) {
                if (!mkdir($panel_dir, 0755, true)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create panel directory'
                    ];
                }
            }
            
            // Backup existing files if requested
            if ($backup_existing && (file_exists($panel_dir . '/form.php') || 
                                   file_exists($panel_dir . '/index.php') || 
                                   file_exists($panel_dir . '/cetak.php'))) {
                $this->createBackup($panel_dir);
            }
            
            // Generate files
            $files_created = [];
            
            // Generate form.php
            $form_content = $this->generateFormContent($panel_name, $table_name, $fields);
            if (file_put_contents($panel_dir . '/form.php', $form_content)) {
                $files_created[] = 'form.php';
            }
            
            // Generate index.php
            $index_content = $this->generateIndexContent($panel_name, $table_name, $fields);
            if (file_put_contents($panel_dir . '/index.php', $index_content)) {
                $files_created[] = 'index.php';
            }
            
            // Generate cetak.php
            $cetak_content = $this->generateCetakContent($panel_name, $table_name, $fields);
            if (file_put_contents($panel_dir . '/cetak.php', $cetak_content)) {
                $files_created[] = 'cetak.php';
            }
            
            return [
                'success' => true,
                'message' => 'Panel generated successfully',
                'files_created' => $files_created,
                'backup_dir' => $this->backup_dir
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error generating panel: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create backup of existing panel files
     * @param string $panel_dir Panel directory path
     */
    private function createBackup($panel_dir) {
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
        
        $files = ['form.php', 'index.php', 'cetak.php'];
        foreach ($files as $file) {
            $source = $panel_dir . '/' . $file;
            $destination = $this->backup_dir . '/' . $file;
            if (file_exists($source)) {
                copy($source, $destination);
            }
        }
    }
    
    /**
     * Generate form.php content
     * @param string $panel_name Panel name
     * @param string $table_name Table name
     * @param array $fields Fields information
     * @return string Generated content
     */
    private function generateFormContent($panel_name, $table_name, $fields) {
        $content = "<?php\n";
        $content .= "// Form untuk panel: $panel_name\n";
        $content .= "session_start();\n";
        $content .= "include '../../conf/koneksi.php';\n\n";
        $content .= "if (!isset(\$_SESSION['id_user'])) {\n";
        $content .= "    header('Location: ../../index.php');\n";
        $content .= "    exit();\n";
        $content .= "}\n\n";
        $content .= "\$id = isset(\$_GET['id']) ? \$_GET['id'] : '';\n";
        $content .= "\$aksi = isset(\$_GET['aksi']) ? \$_GET['aksi'] : 'tambah';\n\n";
        $content .= "if (\$aksi == 'edit' && !empty(\$id)) {\n";
        $content .= "    \$query = mysqli_query(\$koneksi, \"SELECT * FROM `$table_name` WHERE id = '\$id'\");\n";
        $content .= "    \$data = mysqli_fetch_array(\$query);\n";
        $content .= "}\n\n";
        $content .= "?>\n";
        $content .= "<!DOCTYPE html>\n";
        $content .= "<html>\n";
        $content .= "<head>\n";
        $content .= "    <title>Form - <?= htmlspecialchars('$panel_name') ?></title>\n";
        $content .= "    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n";
        $content .= "</head>\n";
        $content .= "<body>\n";
        $content .= "<div class=\"container mt-4\">\n";
        $content .= "    <h2><?= (\$aksi == 'edit') ? 'Edit' : 'Tambah' ?> <?= htmlspecialchars('$panel_name') ?></h2>\n";
        $content .= "    <form method=\"post\" action=\"proses.php\">\n";
        $content .= "        <input type=\"hidden\" name=\"aksi\" value=\"<?= \$aksi ?>\">\n";
        $content .= "        <input type=\"hidden\" name=\"id\" value=\"<?= isset(\$data['id']) ? \$data['id'] : '' ?>\">\n";
        $content .= "        <input type=\"hidden\" name=\"table\" value=\"$table_name\">\n\n";
        
        foreach ($fields as $field) {
            $field_name = $field['field_name'];
            if ($field_name !== 'id' && $field_name !== 'created_at' && $field_name !== 'updated_at') {
                $content .= "        <div class=\"form-group\">\n";
                $content .= "            <label for=\"$field_name\">" . ucfirst(str_replace('_', ' ', $field_name)) . "</label>\n";
                $content .= "            <input type=\"text\" class=\"form-control\" id=\"$field_name\" name=\"$field_name\" ";
                $content .= "value=\"<?= isset(\$data['$field_name']) ? htmlspecialchars(\$data['$field_name']) : '' ?>\" required>\n";
                $content .= "        </div>\n\n";
            }
        }
        
        $content .= "        <button type=\"submit\" class=\"btn btn-primary\">Simpan</button>\n";
        $content .= "        <a href=\"index.php\" class=\"btn btn-secondary\">Batal</a>\n";
        $content .= "    </form>\n";
        $content .= "</div>\n";
        $content .= "</body>\n";
        $content .= "</html>\n";
        
        return $content;
    }
    
    /**
     * Generate index.php content
     * @param string $panel_name Panel name
     * @param string $table_name Table name
     * @param array $fields Fields information
     * @return string Generated content
     */
    private function generateIndexContent($panel_name, $table_name, $fields) {
        $content = "<?php\n";
        $content .= "// Index untuk panel: $panel_name\n";
        $content .= "session_start();\n";
        $content .= "include '../../conf/koneksi.php';\n\n";
        $content .= "if (!isset(\$_SESSION['id_user'])) {\n";
        $content .= "    header('Location: ../../index.php');\n";
        $content .= "    exit();\n";
        $content .= "}\n\n";
        $content .= "\$query = mysqli_query(\$koneksi, \"SELECT * FROM `$table_name` ORDER BY id DESC\");\n";
        $content .= "?>\n";
        $content .= "<!DOCTYPE html>\n";
        $content .= "<html>\n";
        $content .= "<head>\n";
        $content .= "    <title><?= htmlspecialchars('$panel_name') ?></title>\n";
        $content .= "    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n";
        $content .= "</head>\n";
        $content .= "<body>\n";
        $content .= "<div class=\"container mt-4\">\n";
        $content .= "    <h2><?= htmlspecialchars('$panel_name') ?></h2>\n";
        $content .= "    <div class=\"mb-3\">\n";
        $content .= "        <a href=\"form.php\" class=\"btn btn-primary\">Tambah Data</a>\n";
        $content .= "    </div>\n";
        $content .= "    <table class=\"table table-bordered table-striped\">\n";
        $content .= "        <thead>\n";
        $content .= "            <tr>\n";
        
        foreach ($fields as $field) {
            $content .= "                <th>" . ucfirst(str_replace('_', ' ', $field['field_name'])) . "</th>\n";
        }
        
        $content .= "                <th>Aksi</th>\n";
        $content .= "            </tr>\n";
        $content .= "        </thead>\n";
        $content .= "        <tbody>\n";
        $content .= "            <?php while (\$row = mysqli_fetch_array(\$query)): ?>\n";
        $content .= "            <tr>\n";
        
        foreach ($fields as $field) {
            $field_name = $field['field_name'];
            $content .= "                <td><?= htmlspecialchars(\$row['$field_name']) ?></td>\n";
        }
        
        $content .= "                <td>\n";
        $content .= "                    <a href=\"form.php?aksi=edit&id=<?= \$row['id'] ?>\" class=\"btn btn-sm btn-warning\">Edit</a>\n";
        $content .= "                    <a href=\"proses.php?aksi=hapus&id=<?= \$row['id'] ?>&table=$table_name\" class=\"btn btn-sm btn-danger\" onclick=\"return confirm('Yakin ingin menghapus?')\">Hapus</a>\n";
        $content .= "                </td>\n";
        $content .= "            </tr>\n";
        $content .= "            <?php endwhile; ?>\n";
        $content .= "        </tbody>\n";
        $content .= "    </table>\n";
        $content .= "</div>\n";
        $content .= "</body>\n";
        $content .= "</html>\n";
        
        return $content;
    }
    
    /**
     * Generate cetak.php content
     * @param string $panel_name Panel name
     * @param string $table_name Table name
     * @param array $fields Fields information
     * @return string Generated content
     */
    private function generateCetakContent($panel_name, $table_name, $fields) {
        $content = "<?php\n";
        $content .= "// Cetak untuk panel: $panel_name\n";
        $content .= "session_start();\n";
        $content .= "include '../../conf/koneksi.php';\n\n";
        $content .= "if (!isset(\$_SESSION['id_user'])) {\n";
        $content .= "    header('Location: ../../index.php');\n";
        $content .= "    exit();\n";
        $content .= "}\n\n";
        $content .= "\$query = mysqli_query(\$koneksi, \"SELECT * FROM `$table_name` ORDER BY id DESC\");\n";
        $content .= "?>\n";
        $content .= "<!DOCTYPE html>\n";
        $content .= "<html>\n";
        $content .= "<head>\n";
        $content .= "    <title>Laporan - <?= htmlspecialchars('$panel_name') ?></title>\n";
        $content .= "    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css\" rel=\"stylesheet\">\n";
        $content .= "</head>\n";
        $content .= "<body onload=\"window.print()\">\n";
        $content .= "<div class=\"container mt-4\">\n";
        $content .= "    <h2 class=\"text-center mb-4\">Laporan <?= htmlspecialchars('$panel_name') ?></h2>\n";
        $content .= "    <table class=\"table table-bordered\">\n";
        $content .= "        <thead>\n";
        $content .= "            <tr>\n";
        
        foreach ($fields as $field) {
            $content .= "                <th>" . ucfirst(str_replace('_', ' ', $field['field_name'])) . "</th>\n";
        }
        
        $content .= "            </tr>\n";
        $content .= "        </thead>\n";
        $content .= "        <tbody>\n";
        $content .= "            <?php while (\$row = mysqli_fetch_array(\$query)): ?>\n";
        $content .= "            <tr>\n";
        
        foreach ($fields as $field) {
            $field_name = $field['field_name'];
            $content .= "                <td><?= htmlspecialchars(\$row['$field_name']) ?></td>\n";
        }
        
        $content .= "            </tr>\n";
        $content .= "            <?php endwhile; ?>\n";
        $content .= "        </tbody>\n";
        $content .= "    </table>\n";
        $content .= "    <div class=\"mt-4 text-right\">\n";
        $content .= "        Dicetak pada: <?= date('d-m-Y H:i:s') ?>\n";
        $content .= "    </div>\n";
        $content .= "</div>\n";
        $content .= "</body>\n";
        $content .= "</html>\n";
        
        return $content;
    }
}
?>