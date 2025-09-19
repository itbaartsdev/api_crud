<?php
/**
 * KONTROL_BACKUP.PHP - Backup Management Interface
 * Advanced backup and restore functionality for the adaptive generator system
 */

$backup_dir = '../../backups/kontrol_backups';
$backups = [];

// Scan for existing backups
if (is_dir($backup_dir)) {
    $backup_scan = scandir($backup_dir);
    foreach ($backup_scan as $item) {
        if ($item != '.' && $item != '..' && is_dir($backup_dir . '/' . $item)) {
            $backup_info = [
                'name' => $item,
                'path' => $backup_dir . '/' . $item,
                'date' => date('Y-m-d H:i:s', filemtime($backup_dir . '/' . $item)),
                'size' => $this->formatBytes($this->getDirSize($backup_dir . '/' . $item))
            ];
            $backups[] = $backup_info;
        }
    }
    
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function getDirSize($directory) {
    $size = 0;
    if (is_dir($directory)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
    }
    return $size;
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="feather icon-shield"></i> Backup Management</h5>
                <span class="badge badge-success">Configuration Safety</span>
            </div>
            <div class="card-body">
                <!-- Manual Backup Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6><i class="feather icon-download"></i> Create Manual Backup</h6>
                        <form id="createBackupForm" class="mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="backup_name">Backup Name</label>
                                        <input type="text" class="form-control" id="backup_name" name="backup_name" 
                                               placeholder="e.g., before_major_changes" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="backup_description">Description</label>
                                        <input type="text" class="form-control" id="backup_description" name="backup_description" 
                                               placeholder="Brief description of changes">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Items to Backup</label>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="backup_items[]" value="roles_config" id="backup_roles" checked>
                                            <label class="form-check-label" for="backup_roles">
                                                Roles Configuration
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="backup_items[]" value="all_panels" id="backup_panels" checked>
                                            <label class="form-check-label" for="backup_panels">
                                                All Panel Files
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="backup_items[]" value="navigation" id="backup_nav">
                                            <label class="form-check-label" for="backup_nav">
                                                Navigation Files
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="backup_items[]" value="database_schema" id="backup_db">
                                            <label class="form-check-label" for="backup_db">
                                                Database Schema
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success">
                                <i class="feather icon-download"></i> Create Backup
                            </button>
                        </form>
                    </div>
                </div>

                <hr>

                <!-- Existing Backups Section -->
                <div class="row">
                    <div class="col-12">
                        <h6><i class="feather icon-archive"></i> Existing Backups</h6>
                        
                        <?php if (!empty($backups)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Backup Name</th>
                                        <th>Date Created</th>
                                        <th>Size</th>
                                        <th>Contents</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($backup['name']) ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= $backup['date'] ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= $backup['size'] ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $contents = [];
                                            if (file_exists($backup['path'] . '/roles_config.json')) $contents[] = 'Roles';
                                            if (is_dir($backup['path'] . '/panel_files')) $contents[] = 'Panels';
                                            if (is_dir($backup['path'] . '/navigation')) $contents[] = 'Navigation';
                                            if (file_exists($backup['path'] . '/database_schema.sql')) $contents[] = 'DB Schema';
                                            
                                            foreach ($contents as $content) {
                                                echo "<span class=\"badge badge-secondary mr-1\">$content</span>";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="viewBackup('<?= htmlspecialchars($backup['name']) ?>')" 
                                                        title="View Contents">
                                                    <i class="feather icon-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="restoreBackup('<?= htmlspecialchars($backup['name']) ?>')" 
                                                        title="Restore">
                                                    <i class="feather icon-upload"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info" 
                                                        onclick="downloadBackup('<?= htmlspecialchars($backup['name']) ?>')" 
                                                        title="Download">
                                                    <i class="feather icon-download"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteBackup('<?= htmlspecialchars($backup['name']) ?>')" 
                                                        title="Delete">
                                                    <i class="feather icon-trash-2"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="feather icon-info"></i>
                            No backups found. Create your first backup to ensure system safety.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Side Panel -->
    <div class="col-lg-4">
        <!-- Backup Status -->
        <div class="card">
            <div class="card-header">
                <h5><i class="feather icon-activity"></i> Backup Status</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Total Backups</span>
                    <span class="badge badge-primary"><?= count($backups) ?></span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Storage Used</span>
                    <span class="badge badge-info">
                        <?php
                        $total_size = 0;
                        foreach ($backups as $backup) {
                            $total_size += getDirSize($backup['path']);
                        }
                        echo formatBytes($total_size);
                        ?>
                    </span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Last Backup</span>
                    <small class="text-muted">
                        <?= !empty($backups) ? date('d/m/Y', strtotime($backups[0]['date'])) : 'Never' ?>
                    </small>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <span>Auto-Backup</span>
                    <span class="badge badge-success">Enabled</span>
                </div>
            </div>
        </div>

        <!-- Backup Settings -->
        <div class="card">
            <div class="card-header">
                <h5><i class="feather icon-settings"></i> Backup Settings</h5>
            </div>
            <div class="card-body">
                <form id="backupSettingsForm">
                    <div class="form-group">
                        <label for="auto_backup">Auto-Backup</label>
                        <select class="form-control" id="auto_backup" name="auto_backup">
                            <option value="enabled" selected>Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                        <small class="form-text text-muted">
                            Automatically create backup before major changes
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="retention_days">Retention Period (days)</label>
                        <input type="number" class="form-control" id="retention_days" name="retention_days" 
                               value="30" min="1" max="365">
                        <small class="form-text text-muted">
                            Number of days to keep backups
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_backups">Maximum Backups</label>
                        <input type="number" class="form-control" id="max_backups" name="max_backups" 
                               value="10" min="1" max="50">
                        <small class="form-text text-muted">
                            Maximum number of backups to keep
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="feather icon-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5><i class="feather icon-zap"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="cleanupOldBackups()">
                        <i class="feather icon-trash-2"></i> Cleanup Old Backups
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="exportAllBackups()">
                        <i class="feather icon-package"></i> Export All Backups
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="validateBackups()">
                        <i class="feather icon-check-circle"></i> Validate Backups
                    </button>
                    <a href="kontrol.php?form=logs" class="btn btn-sm btn-outline-secondary">
                        <i class="feather icon-file-text"></i> View Activity Logs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup Details Modal -->
<div class="modal fade" id="backupDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Backup Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="backupDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Create backup form handler
    $('#createBackupForm').submit(function(e) {
        e.preventDefault();
        createBackup();
    });

    // Backup settings form handler
    $('#backupSettingsForm').submit(function(e) {
        e.preventDefault();
        saveBackupSettings();
    });
});

function createBackup() {
    const formData = new FormData($('#createBackupForm')[0]);
    
    showNotification('info', 'Creating Backup', 'Please wait while backup is being created...');
    
    $.ajax({
        url: 'api_crud/system/kontrol_proses.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification('success', 'Backup Created', response.message);
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showNotification('error', 'Backup Failed', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Request Failed', 'Failed to create backup');
        }
    });
}

function viewBackup(backupName) {
    $('#backupDetailsContent').html('<div class="text-center"><i class="feather icon-loader"></i> Loading backup details...</div>');
    $('#backupDetailsModal').modal('show');
    
    $.get('api_crud/system/kontrol_proses.php?action=get_backup_details&backup=' + encodeURIComponent(backupName), function(response) {
        if (response.success) {
            $('#backupDetailsContent').html(response.content);
        } else {
            $('#backupDetailsContent').html('<div class="alert alert-danger">' + response.message + '</div>');
        }
    }, 'json').fail(function() {
        $('#backupDetailsContent').html('<div class="alert alert-danger">Failed to load backup details</div>');
    });
}

function restoreBackup(backupName) {
    if (confirm('Are you sure you want to restore this backup? This will overwrite current configuration.')) {
        showNotification('info', 'Restoring Backup', 'Please wait while backup is being restored...');
        
        $.post('api_crud/system/kontrol_proses.php', {
            action: 'restore_backup',
            backup_name: backupName
        }, function(response) {
            if (response.success) {
                showNotification('success', 'Backup Restored', response.message);
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showNotification('error', 'Restore Failed', response.message);
            }
        }, 'json').fail(function() {
            showNotification('error', 'Request Failed', 'Failed to restore backup');
        });
    }
}

function downloadBackup(backupName) {
    window.open('api_crud/system/kontrol_proses.php?action=download_backup&backup=' + encodeURIComponent(backupName), '_blank');
}

function deleteBackup(backupName) {
    if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        $.post('api_crud/system/kontrol_proses.php', {
            action: 'delete_backup',
            backup_name: backupName
        }, function(response) {
            if (response.success) {
                showNotification('success', 'Backup Deleted', response.message);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', 'Delete Failed', response.message);
            }
        }, 'json').fail(function() {
            showNotification('error', 'Request Failed', 'Failed to delete backup');
        });
    }
}

function saveBackupSettings() {
    const formData = $('#backupSettingsForm').serialize();
    
    $.post('api_crud/system/kontrol_proses.php', formData + '&action=save_backup_settings', function(response) {
        if (response.success) {
            showNotification('success', 'Settings Saved', response.message);
        } else {
            showNotification('error', 'Save Failed', response.message);
        }
    }, 'json').fail(function() {
        showNotification('error', 'Request Failed', 'Failed to save settings');
    });
}

function cleanupOldBackups() {
    if (confirm('This will delete backups older than the retention period. Continue?')) {
        $.post('api_crud/system/kontrol_proses.php', {
            action: 'cleanup_backups'
        }, function(response) {
            if (response.success) {
                showNotification('success', 'Cleanup Complete', response.message);
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showNotification('error', 'Cleanup Failed', response.message);
            }
        }, 'json').fail(function() {
            showNotification('error', 'Request Failed', 'Failed to cleanup backups');
        });
    }
}

function exportAllBackups() {
    window.open('api_crud/system/kontrol_proses.php?action=export_all_backups', '_blank');
}

function validateBackups() {
    showNotification('info', 'Validating Backups', 'Checking backup integrity...');
    
    $.get('api_crud/system/kontrol_proses.php?action=validate_backups', function(response) {
        if (response.success) {
            showNotification('success', 'Validation Complete', response.message);
        } else {
            showNotification('warning', 'Validation Issues', response.message);
        }
    }, 'json').fail(function() {
        showNotification('error', 'Validation Failed', 'Failed to validate backups');
    });
}

function showNotification(type, title, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const notification = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <strong>${title}:</strong> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.alert').remove();
    $('body').prepend(notification);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>