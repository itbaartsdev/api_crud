<?php
/**
 * KONTROL_GENERATOR.PHP - Panel Generator Interface
 * Advanced interface for generating adaptive panel structures
 */

// Get available tables from database
$tables_query = mysqli_query($koneksi, "SHOW TABLES");
$available_tables = [];
while ($table = mysqli_fetch_array($tables_query)) {
    $available_tables[] = $table[0];
}

// Get existing panels
$panel_dirs = [];
if (is_dir('../../Panel')) {
    $panel_scan = scandir('../../Panel');
    foreach ($panel_scan as $dir) {
        if ($dir != '.' && $dir != '..' && is_dir('../../Panel/' . $dir)) {
            $panel_dirs[] = $dir;
        }
    }
}
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="feather icon-code"></i> Generator Panel Lanjutan</h5>
                <span class="badge badge-primary">Pembuatan Kode Adaptif</span>
            </div>
            <div class="card-body">
                <form id="generatePanelForm" method="post" action="kontrol.php">
                    <div class="row">
                        <!-- Nama Panel -->
                        <div class="col-md-6 mb-3">
                            <label for="panel_name" class="form-label">Nama Panel</label>
                            <input type="text" class="form-control" id="panel_name" name="panel_name" required 
                                   placeholder="contoh: Permintaan Barang">
                            <small class="form-text text-muted">
                                Ini akan menjadi nama direktori dan label navigasi
                            </small>
                        </div>

                        <!-- Pemilihan Tabel -->
                        <div class="col-md-6 mb-3">
                            <label for="table_name" class="form-label">Tabel Database</label>
                            <select class="form-control" id="table_name" name="table_name" required>
                                <option value="">Pilih Tabel</option>
                                <?php foreach ($available_tables as $table): ?>
                                <option value="<?= htmlspecialchars($table) ?>"><?= htmlspecialchars($table) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                Pilih tabel database untuk panel ini
                            </small>
                        </div>

                        <!-- Konfigurasi Role -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Kontrol Akses Berbasis Peran</label>
                            <div class="card border-info">
                                <div class="card-body">
                                    <div class="row">
                                        <?php
                                        $roles_config_path = '../../conf/roles_config.json';
                                        if (file_exists($roles_config_path)) {
                                            $roles_content = file_get_contents($roles_config_path);
                                            $roles_data = json_decode($roles_content, true);
                                            
                                            if (isset($roles_data['roles'])) {
                                                foreach ($roles_data['roles'] as $role_name => $role_data) {
                                                    echo "<div class=\"col-md-3 mb-2\">";
                                                    echo "<div class=\"form-check\">";
                                                    echo "<input class=\"form-check-input\" type=\"checkbox\" name=\"roles[]\" value=\"$role_name\" id=\"role_$role_name\" checked>";
                                                    echo "<label class=\"form-check-label\" for=\"role_$role_name\">";
                                                    echo "<strong>$role_name</strong>";
                                                    echo "</label>";
                                                    echo "</div>";
                                                    echo "</div>";
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                    <small class="text-muted">
                                        Peran yang dipilih akan memiliki kontrol akses kondisional yang dibuat secara otomatis
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Fitur Lanjutan -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Fitur Lanjutan</label>
                            <div class="card border-warning">
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="add_enum_status" id="add_enum_status" checked>
                                        <label class="form-check-label" for="add_enum_status">
                                            <strong>Tambahkan Field Status Enum</strong>
                                        </label>
                                        <small class="form-text text-muted d-block">
                                            Secara otomatis menambahkan field status dengan enum('proses','terima','tolak') default 'proses'
                                        </small>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="add_user_tracking" id="add_user_tracking" checked>
                                        <label class="form-check-label" for="add_user_tracking">
                                            <strong>Pelacakan Aktivitas Pengguna</strong>
                                        </label>
                                        <small class="form-text text-muted d-block">
                                            Menambahkan field created_by, updated_by, created_at, updated_at
                                        </small>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="generate_approval_workflow" id="generate_approval_workflow" checked>
                                        <label class="form-check-label" for="generate_approval_workflow">
                                            <strong>Alur Persetujuan</strong>
                                        </label>
                                        <small class="form-text text-muted d-block">
                                            Menghasilkan tombol setujui/tolak untuk peran Administrator dan Supervisor
                                        </small>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="backup_existing" id="backup_existing" checked>
                                        <label class="form-check-label" for="backup_existing">
                                            <strong>Cadangkan File yang Ada Secara Otomatis</strong>
                                        </label>
                                        <small class="form-text text-muted d-block">
                                            Membuat cadangan sebelum menghasilkan file baru (disarankan)
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pratinjau Struktur Tabel -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Pratinjau Struktur Tabel</label>
                            <div class="card border-secondary">
                                <div class="card-body">
                                    <div id="tableFieldsPreview" class="text-muted">
                                        <i class="feather icon-info"></i> Pilih tabel untuk melihat strukturnya
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Opsi Pembuatan File -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Opsi Pembuatan File</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="generate_form" id="generate_form" checked>
                                        <label class="form-check-label" for="generate_form">
                                            <strong>form.php</strong>
                                        </label>
                                        <small class="form-text text-muted d-block">Antarmuka input/edit</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="generate_index" id="generate_index" checked>
                                        <label class="form-check-label" for="generate_index">
                                            <strong>index.php</strong>
                                        </label>
                                        <small class="form-text text-muted d-block">Tampilan daftar/navigasi</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="generate_cetak" id="generate_cetak" checked>
                                        <label class="form-check-label" for="generate_cetak">
                                            <strong>cetak.php</strong>
                                        </label>
                                        <small class="form-text text-muted d-block">Fungsi cetak/laporan</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" name="generate_panel" class="btn btn-primary btn-lg">
                            <i class="feather icon-zap"></i> Buat Panel Adaptif
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="previewGeneration()">
                            <i class="feather icon-eye"></i> Pratinjau Kode
                        </button>
                        <button type="reset" class="btn btn-outline-danger btn-lg">
                            <i class="feather icon-refresh-cw"></i> Reset Formulir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Panel Samping -->
    <div class="col-lg-4">
        <!-- Status Pembuatan -->
        <div class="card">
            <div class="card-header">
                <h5><i class="feather icon-activity"></i> Status Pembuatan</h5>
            </div>
            <div class="card-body">
                <div id="generationStatus" class="text-center">
                    <i class="feather icon-clock text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-2">Siap membuat panel adaptif</p>
                </div>
                
                <div id="generationProgress" class="d-none">
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted">Membuat file panel...</small>
                </div>
            </div>
        </div>

        <!-- Panel yang Ada -->
        <div class="card">
            <div class="card-header">
                <h5><i class="feather icon-folder"></i> Panel yang Ada</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($panel_dirs)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($panel_dirs as $dir): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                        <span><?= htmlspecialchars($dir) ?></span>
                        <div>
                            <a href="index.php?page=<?= urlencode($dir) ?>" class="btn btn-sm btn-outline-primary" title="Lihat Panel">
                                <i class="feather icon-eye"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">Tidak ada panel yang ditemukan</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tindakan Cepat -->
        <div class="card">
            <div class="card-header">
                <h5><i class="feather icon-zap"></i> Tindakan Cepat</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="loadTemplateExample()">
                        <i class="feather icon-download"></i> Muat Contoh Template
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="validateConfiguration()">
                        <i class="feather icon-check-circle"></i> Validasi Konfigurasi
                    </button>
                    <a href="kontrol.php?form=backup" class="btn btn-sm btn-outline-success">
                        <i class="feather icon-shield"></i> Kelola Cadangan
                    </a>
                    <a href="kontrol.php?form=logs" class="btn btn-sm btn-outline-secondary">
                        <i class="feather icon-file-text"></i> Lihat Log Aktivitas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
$(document).ready(function() {
    // Table selection change handler
    $('#table_name').change(function() {
        const tableName = $(this).val();
        if (tableName) {
            loadTableFields(tableName);
        } else {
            $('#tableFieldsPreview').html('<i class="feather icon-info"></i> Select a table to preview its structure');
        }
    });

    // Form submission handler
    $('#generatePanelForm').submit(function(e) {
        e.preventDefault();
        generatePanel();
    });
});

function loadTableFields(tableName) {
    $('#tableFieldsPreview').html('<i class="feather icon-loader"></i> Loading table structure...');
    
    $.post('api_crud/get_table_fields.php', {table: tableName}, function(response) {
        if (response.success) {
            let html = '<div class="table-responsive">';
            html += '<table class="table table-sm">';
            html += '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr></thead>';
            html += '<tbody>';
            
            response.fields.forEach(function(field) {
                html += '<tr>';
                html += '<td><code>' + field.Field + '</code></td>';
                html += '<td><small>' + field.Type + '</small></td>';
                html += '<td><small>' + field.Null + '</small></td>';
                html += '<td><small>' + field.Key + '</small></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            $('#tableFieldsPreview').html(html);
        } else {
            $('#tableFieldsPreview').html('<div class="alert alert-danger">' + response.message + '</div>');
        }
    }, 'json').fail(function() {
        $('#tableFieldsPreview').html('<div class="alert alert-danger">Failed to load table structure</div>');
    });
}

function generatePanel() {
    const formData = new FormData($('#generatePanelForm')[0]);
    
    // Show progress
    $('#generationStatus').addClass('d-none');
    $('#generationProgress').removeClass('d-none');
    
    let progress = 0;
    const progressInterval = setInterval(function() {
        progress += 10;
        $('.progress-bar').css('width', progress + '%');
        
        if (progress >= 90) {
            clearInterval(progressInterval);
        }
    }, 200);
    
    $.ajax({
        url: 'api_crud/system/kontrol_proses.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            clearInterval(progressInterval);
            $('.progress-bar').css('width', '100%');
            
            setTimeout(function() {
                if (response.success) {
                    showNotification('success', 'Panel Generated Successfully', response.message);
                    
                    // Update status
                    $('#generationProgress').addClass('d-none');
                    $('#generationStatus').removeClass('d-none').html(
                        '<i class="feather icon-check-circle text-success" style="font-size: 48px;"></i>' +
                        '<p class="text-success mt-2">Panel generated successfully!</p>' +
                        '<small class="text-muted">Files: ' + response.files_created.join(', ') + '</small>'
                    );
                    
                    // Reset form after a delay
                    setTimeout(function() {
                        $('#generatePanelForm')[0].reset();
                        $('#tableFieldsPreview').html('<i class="feather icon-info"></i> Select a table to preview its structure');
                        
                        $('#generationStatus').html(
                            '<i class="feather icon-clock text-muted" style="font-size: 48px;"></i>' +
                            '<p class="text-muted mt-2">Ready to generate adaptive panel</p>'
                        );
                    }, 3000);
                    
                } else {
                    showNotification('error', 'Generation Failed', response.message);
                    
                    $('#generationProgress').addClass('d-none');
                    $('#generationStatus').removeClass('d-none').html(
                        '<i class="feather icon-alert-circle text-danger" style="font-size: 48px;"></i>' +
                        '<p class="text-danger mt-2">Generation failed</p>' +
                        '<small class="text-muted">' + response.message + '</small>'
                    );
                }
            }, 1000);
        },
        error: function() {
            clearInterval(progressInterval);
            showNotification('error', 'Request Failed', 'Failed to communicate with generator');
            
            $('#generationProgress').addClass('d-none');
            $('#generationStatus').removeClass('d-none').html(
                '<i class="feather icon-wifi-off text-danger" style="font-size: 48px;"></i>' +
                '<p class="text-danger mt-2">Connection failed</p>'
            );
        }
    });
}

function previewGeneration() {
    const panelName = $('#panel_name').val();
    const tableName = $('#table_name').val();
    
    if (!panelName || !tableName) {
        showNotification('warning', 'Missing Information', 'Please fill in panel name and select a table');
        return;
    }
    
    // Open preview in new window/modal
    window.open('kontrol.php?form=preview&panel=' + encodeURIComponent(panelName) + '&table=' + encodeURIComponent(tableName), '_blank');
}

function loadTemplateExample() {
    $('#panel_name').val('Contoh Panel');
    $('#table_name').val('<?= !empty($available_tables) ? $available_tables[0] : '' ?>').trigger('change');
    showNotification('info', 'Template Loaded', 'Example configuration has been loaded');
}

function validateConfiguration() {
    // Validate roles configuration
    $.get('api_crud/system/kontrol_proses.php?action=validate_config', function(response) {
        if (response.success) {
            showNotification('success', 'Configuration Valid', 'All configurations are properly set up');
        } else {
            showNotification('error', 'Configuration Error', response.message);
        }
    }, 'json').fail(function() {
        showNotification('error', 'Validation Failed', 'Failed to validate configuration');
    });
}

function showNotification(type, title, message) {
    // Simple notification system
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
    
    // Remove existing notifications
    $('.alert').remove();
    
    // Add new notification at top of page
    $('body').prepend(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>