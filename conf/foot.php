    <!-- Modern Footer -->
    <footer style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border-top: 1px solid rgba(255, 255, 255, 0.2); margin-top: 60px; padding: 40px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="material-icons" style="font-size: 18px;">storage</i>
                        </div>
                        <span style="color: #2d3748; font-size: 1.1rem; font-weight: 600;">Azzam Generator</span>
                    </div>
                    <p style="color: #718096; margin: 0; font-size: 0.9rem;">Modern Database Generator System</p>
                </div>
                <div style="text-align: right;">
                    <p style="color: #718096; margin: 0; font-size: 0.9rem;">© 2025 Azzam Generator. All rights reserved.</p>
                    <p style="color: #a0aec0; margin: 4px 0 0 0; font-size: 0.8rem;">Version 3.0.0 - Modern Edition</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Vendor JS -->
    <script src="azzam/app-assets/js/vendors.min.js"></script>
    <script src="azzam/app-assets/vendors/data-tables/js/jquery.dataTables.min.js"></script>
    <script src="azzam/app-assets/vendors/data-tables/extensions/responsive/js/dataTables.responsive.min.js"></script>
    <script src="azzam/app-assets/vendors/data-tables/js/dataTables.select.min.js"></script>
    <script src="azzam/app-assets/vendors/select2/select2.full.min.js"></script>
    
    <!-- Theme JS -->
    <script src="azzam/app-assets/js/plugins.js"></script>
    <script src="azzam/app-assets/js/search.js"></script>
    
    <!-- Custom Modern JS -->
    <script>
        $(document).ready(function() {
            // Disable i18next and prevent any localization errors
            if (typeof window.i18next !== 'undefined') {
                console.log('i18next library detected - initializing with minimal config');
                try {
                    window.i18next.init({
                        lng: 'en',
                        fallbackLng: 'en',
                        debug: false,
                        resources: {
                            en: {
                                translation: {}
                            }
                        }
                    });
                } catch (e) {
                    console.log('i18next initialization skipped:', e.message);
                }
            }
            
            // Prevent any localization-related errors
            if (typeof $.fn.localize === 'function') {
                $.fn.localize = function() { return this; };
            }
            
            // Initialize modals if needed
            $('.modal').modal({
                dismissible: true,
                opacity: 0.5,
                inDuration: 300,
                outDuration: 200
            });
            
            // Add new field functionality (for permanent forms only)
            $(".add-more").not('.form-inputs .add-more').click(function(){ 
                var html = $(".copy").not('.form-inputs .copy').html();
                $(".before-add-more").not('.form-inputs .before-add-more').before(html);
            });

            // Remove field functionality (for permanent forms only)
            $("body").on("click",".remove",function(){ 
                // Only handle if not inside form-inputs (dynamic form)
                if (!$(this).closest('.form-inputs').length) {
                    const fieldRow = $(this).closest('div[style*="grid"]');
                    
                    // Find and remove associated relation container
                    let nextElement = fieldRow.next();
                    while (nextElement.length > 0) {
                        if (nextElement.hasClass('relation-container')) {
                            nextElement.remove();
                            break;
                        } else if (nextElement.hasClass('field-row') || nextElement.attr('style')?.includes('grid')) {
                            // Found another field row, stop searching
                            break;
                        }
                        nextElement = nextElement.next();
                    }
                    
                    // Remove the field row
                    fieldRow.remove();
                }
            });
            
            // Auto-generate field name from label (for permanent forms only)
            $(document).on('input', 'input[name="judul_field_sistem[]"]', function() {
                // Only handle if not inside form-inputs (dynamic form)
                if (!$(this).closest('.form-inputs').length) {
                    var label = $(this).val();
                    var fieldName = label.toLowerCase()
                        .replace(/[^a-z0-9\s]/g, '')
                        .replace(/\s+/g, '_')
                        .replace(/^_+|_+$/g, '');
                    $(this).closest('.field-row').find('input[name="nama_field_sistem[]"]').val(fieldName);
                }
            });
            
            // Success message
            if (window.location.search.includes('success')) {
                M.toast({html: 'Table generated successfully!', classes: 'green'});
            }
            
            console.log('Azzam Simple Generator with Edit/Delete Functions Loaded - i18n disabled');
            
            // Global error handler to catch and log any JavaScript errors
            window.addEventListener('error', function(e) {
                console.warn('JavaScript Error caught:', {
                    message: e.message,
                    filename: e.filename,
                    lineno: e.lineno,
                    colno: e.colno
                });
                // Don't prevent default - just log the error
                return false;
            });
            
            // Catch unhandled promise rejections
            window.addEventListener('unhandledrejection', function(e) {
                console.warn('Unhandled Promise Rejection:', e.reason);
                // Prevent the default behavior which would log to console
                e.preventDefault();
            });
        });
        
        // Show add form function
        function showAddForm() {
            // Change card title
            $('.form-card .card-title').html('generator<br>tambah');
            
            // Store original form for restore
            if (!window.originalFormHtml) {
                window.originalFormHtml = $('.form-inputs').html();
            }
            
            // Create add form HTML
            var addFormHtml = createAddForm();
            
            // Replace form content
            $('.form-inputs').html(addFormHtml);
            
            // Initialize the add form functionality
            initializeAddFormHandlers();
        }
        
        // Initialize event handlers for the add form
        function initializeAddFormHandlers() {
            console.log('Initializing add form handlers');
            
            // Add new field functionality for dynamic form
            $('.form-inputs .add-more').off('click').on('click', function(){ 
                console.log('Add field button clicked');
                var html = $('.form-inputs .copy').html();
                $('.form-inputs .before-add-more').before(html);
            });

            // Remove field functionality for dynamic form
            $('.form-inputs').off('click', '.remove').on('click', '.remove', function(){ 
                console.log('Remove field button clicked');
                const fieldRow = $(this).closest('.field-row');
                
                // Find and remove associated relation container
                let nextElement = fieldRow.next();
                while (nextElement.length > 0) {
                    if (nextElement.hasClass('relation-container')) {
                        nextElement.remove();
                        break;
                    } else if (nextElement.hasClass('field-row')) {
                        // Found another field row, stop searching
                        break;
                    }
                    nextElement = nextElement.next();
                }
                
                // Remove the field row
                fieldRow.remove();
            });
            
            // Auto-generate field name from label for dynamic form
            $('.form-inputs').off('input', 'input[name="judul_field_sistem[]"]').on('input', 'input[name="judul_field_sistem[]"]', function() {
                var label = $(this).val();
                var fieldName = label.toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '')
                    .replace(/\s+/g, '_')
                    .replace(/^_+|_+$/g, '');
                $(this).closest('.field-row').find('input[name="nama_field_sistem[]"]').val(fieldName);
            });
            
            console.log('Add form handlers initialized successfully');
        }
        
        // Auto-generate table name for dynamic form (global scope)
        window.generateTableNameDynamic = function() {
            const displayName = document.getElementById('judul_tabel_sistem_dynamic').value;
            const tableName = displayName
                .toLowerCase()                    // Convert to lowercase
                .replace(/[^a-z0-9\s]/g, '')     // Remove special characters except spaces
                .trim()                          // Remove leading/trailing spaces
                .replace(/\s+/g, '_');           // Replace spaces with underscores
            
            document.getElementById('nama_tabel_sistem_dynamic').value = tableName;
        }
        
        // Handle field type change for relation (global scope)
        window.handleFieldTypeChange = function(selectElement) {
            const fieldRow = selectElement.closest('.field-row');
            const fieldType = selectElement.value;
            
            // Get or create relation UI container
            let relationContainer = fieldRow.querySelector('.relation-container');
            
            if (fieldType === 'relation') {
                if (!relationContainer) {
                    // Create relation UI
                    relationContainer = document.createElement('div');
                    relationContainer.className = 'relation-container';
                    relationContainer.style.gridColumn = '1 / -1'; // Span across all columns
                    relationContainer.innerHTML = `
                        <div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #4CAF50; border-radius: 4px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label style="font-size: 12px; color: #333; font-weight: 600;">Reference Table:</label>
                                    <select name="relation_table[]" class="field-input simple-select" onchange="loadTableFields(this)" style="margin-top: 4px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;">
                                        <option value="">Select Table</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #333; font-weight: 600;">Display Field:</label>
                                    <select name="relation_field[]" class="field-input simple-select" style="margin-top: 4px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;">
                                        <option value="">Select Field</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                    fieldRow.appendChild(relationContainer);
                    
                    // Load available tables
                    loadAvailableTables(relationContainer.querySelector('select[name="relation_table[]"]'));
                }
                
                // Auto-set properties for relation
                const lengthInput = fieldRow.querySelector('input[name="values_field_sistem[]"]');
                const propertySelect = fieldRow.querySelector('select[name="keterangan_field_sistem[]"]');
                
                if (lengthInput) {
                    lengthInput.value = '11';
                    lengthInput.readOnly = true;
                    lengthInput.style.background = '#f5f5f5';
                }
                
                if (propertySelect) {
                    propertySelect.value = 'index';
                    propertySelect.disabled = true;
                    propertySelect.style.background = '#f5f5f5';
                }
                
            } else {
                // Remove relation UI if exists
                if (relationContainer) {
                    relationContainer.remove();
                }
                
                // Reset properties
                const lengthInput = fieldRow.querySelector('input[name="values_field_sistem[]"]');
                const propertySelect = fieldRow.querySelector('select[name="keterangan_field_sistem[]"]');
                
                if (lengthInput) {
                    lengthInput.readOnly = false;
                    lengthInput.style.background = 'white';
                    lengthInput.value = '';
                }
                
                if (propertySelect) {
                    propertySelect.disabled = false;
                    propertySelect.style.background = 'white';
                    propertySelect.value = '';
                }
            }
        }
        
        // Load available tables for relation
        window.loadAvailableTables = function(selectElement) {
            fetch('api_crud/get_available_tables.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tables) {
                    selectElement.innerHTML = '<option value="">Select Table</option>';
                    data.tables.forEach(table => {
                        const option = document.createElement('option');
                        option.value = table.name;
                        option.textContent = table.label;
                        selectElement.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading tables:', error);
            });
        }
        
        // Load fields for selected table
        window.loadTableFields = function(selectElement) {
            const tableName = selectElement.value;
            const fieldRow = selectElement.closest('.field-row');
            const relationFieldSelect = fieldRow.querySelector('select[name="relation_field[]"]');
            const nameInput = fieldRow.querySelector('input[name="nama_field_sistem[]"]');
            
            if (tableName && nameInput) {
                // Auto-generate field name: id_table_name
                nameInput.value = 'id_' + tableName;
            }
            
            if (tableName) {
                // AJAX call to get table fields
                fetch('api_crud/get_table_fields.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'table_name=' + encodeURIComponent(tableName)
                })
                .then(response => response.json())
                .then(data => {
                    relationFieldSelect.innerHTML = '<option value="">Select Field</option>';
                    if (data.success && data.fields) {
                        data.fields.forEach(field => {
                            const option = document.createElement('option');
                            option.value = field.name;
                            option.textContent = field.label;
                            relationFieldSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading table fields:', error);
                });
            } else {
                relationFieldSelect.innerHTML = '<option value="">Select Field</option>';
            }
        }
        
        // Create add form
        function createAddForm() {
            var html = '<div class="edit-form-container">';
            html += '<div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196F3;">';
            html += '<h4 style="margin: 0 0 5px 0; color: #1976D2;">Create New Table</h4>';
            html += '<p style="margin: 0; color: #666; font-size: 14px;">Configure table structure and field properties below</p>';
            html += '</div>';
            
            html += '<form method="POST" action="crud.php?form=proses" class="modern-form-container no-plugins" onsubmit="showLoading()">';
            
            // Table Information Section
            html += '<div style="margin-bottom: 20px;">';
            html += '<h5 style="color: #333; margin-bottom: 15px; font-size: 1rem; font-weight: 600;">Table Information</h5>';
            html += '<div style="display: grid; grid-template-columns: 1fr; gap: 15px;">';
            html += '<div>';
            html += '<input type="text" name="judul_tabel_sistem" id="judul_tabel_sistem_dynamic" class="modern-input" placeholder="Table display name" required oninput="generateTableNameDynamic()" style="padding: 12px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; width: 100%; transition: all 0.2s ease;">';
            html += '</div>';
            html += '<div>';
            html += '<input type="text" name="nama_tabel_sistem" id="nama_tabel_sistem_dynamic" class="modern-input" placeholder="table_name (auto generated)" pattern="[a-z_]+" required readonly style="padding: 12px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: #f5f5f5; color: #666; width: 100%; transition: all 0.2s ease;">';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            // Fields section
            html += '<div style="margin-bottom: 20px;">';
            html += '<h5 style="color: #333; margin-bottom: 15px; font-size: 1rem; font-weight: 600;">Table Fields</h5>';
            html += '<div class="fields-container" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; max-height: 300px; overflow-y: auto;">';
            
            // Field headers
            html += '<div class="field-header" style="display: grid; grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto; gap: 16px; background: #333; color: white; padding: 12px; font-size: 12px; font-weight: 600; min-width: 600px;">';
            html += '<div>Label</div><div>Name</div><div>Type</div><div>Length</div><div>Property</div><div>Action</div>';
            html += '</div>';
            
            // Primary key field (always present)
            html += '<div class="field-row primary-key" style="display: grid; grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto; gap: 16px; padding: 10px; background: #fff3cd; border-bottom: 1px solid #eee; align-items: center; min-width: 600px;">';
            html += '<div><input type="text" name="judul_field_sistem[]" value="ID" class="field-input" readonly style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f5f5f5; color: #666;"></div>';
            html += '<div><input type="text" name="nama_field_sistem[]" value="id" class="field-input" readonly style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f5f5f5; color: #666;"></div>';
            html += '<div><select name="tipe_field_sistem[]" class="field-input simple-select" disabled style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f5f5f5; color: #666;"><option value="int" selected>Integer</option></select><input type="hidden" name="tipe_field_sistem[]" value="int"></div>';
            html += '<div><input type="text" name="values_field_sistem[]" value="11" class="field-input" readonly style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f5f5f5; color: #666;"></div>';
            html += '<div><select name="keterangan_field_sistem[]" class="field-input simple-select" disabled style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f5f5f5; color: #666;"><option value="primary" selected>Primary Key</option></select><input type="hidden" name="keterangan_field_sistem[]" value="primary"></div>';
            html += '<div style="text-align: center;"><i class="material-icons lock-icon" style="color: #ff9800; font-size: 20px;">lock</i></div>';
            html += '</div>';
            
            // Auto Input Date Field
            html += '<div class="field-row" style="display: grid; grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto; gap: 16px; padding: 10px; background: #e8f5e8; border: 1px solid #4caf50; align-items: center; min-width: 600px;">';
            html += '<div><input type="text" value="Input Date" class="field-input" readonly style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f0fdf4;"><input type="hidden" name="judul_field_sistem[]" value="Input Date"></div>';
            html += '<div><input type="text" value="input_date" class="field-input" readonly style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f0fdf4;"><input type="hidden" name="nama_field_sistem[]" value="input_date"></div>';
            html += '<div><select class="field-input simple-select" disabled style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f0fdf4;"><option value="datetime" selected>DateTime</option></select><input type="hidden" name="tipe_field_sistem[]" value="datetime"></div>';
            html += '<div><input type="text" value="CURRENT_TIMESTAMP" class="field-input" readonly style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f0fdf4;"><input type="hidden" name="values_field_sistem[]" value=""></div>';
            html += '<div><select class="field-input simple-select" disabled style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f0fdf4;"><option value="auto" selected>Auto Added</option></select><input type="hidden" name="keterangan_field_sistem[]" value="auto"></div>';
            html += '<div style="text-align: center;"><i class="material-icons" style="color: #4caf50; font-size: 20px;">schedule</i></div>';
            html += '</div>';
            
            html += '<div class="before-add-more"></div>';
            
            // Add field button
            html += '<button type="button" class="add-more add-field-btn" style="background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; margin: 15px auto; display: flex; align-items: center; gap: 8px; font-weight: 500; width: fit-content;"><i class="material-icons">add</i>Add Field</button>';
            
            html += '</div>';
            html += '</div>';
            
            // Form Footer
            html += '<div class="form-footer" style="padding: 15px; background: #f8f8f8; border-top: 1px solid #ddd; margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">';
            html += '<button type="reset" class="footer-btn reset-btn" style="padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 500; background: #6c757d; color: white;"><i class="material-icons">refresh</i>Reset</button>';
            html += '<button type="submit" name="tambah" class="footer-btn submit-btn" style="padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 500; background: #4CAF50; color: white;"><i class="material-icons">save</i>Generate</button>';
            html += '</div>';
            
            html += '</form>';
            
            // Add cancel button
            html += '<div style="margin-top: 15px; text-align: center;">';
            html += '<button onclick="cancelAdd()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>';
            html += '</div>';
            
            html += '</div>';
            
            // Add the dynamic field template
            html += '<div class="copy" style="display: none;">';
            html += '<div class="field-row" style="display: grid; grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto; gap: 16px; padding: 10px; background: white; border-bottom: 1px solid #eee; align-items: center; min-width: 600px;">';
            html += '<div data-label="Label"><input type="text" name="judul_field_sistem[]" class="field-input" placeholder="Field Display Name" required oninput="generateFieldName(this)" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;"></div>';
            html += '<div data-label="Name"><input type="text" name="nama_field_sistem[]" class="field-input" placeholder="field_system_name" pattern="[a-z_]+" required style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;"></div>';
            html += '<div data-label="Type"><select name="tipe_field_sistem[]" class="field-input simple-select" required onchange="handleFieldTypeChange(this)" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; appearance: none; background-image: url(\'data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3e%3cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'m6 8 4 4 4-4\'/%3e%3c/svg%3e\'); background-position: right 8px center; background-repeat: no-repeat; background-size: 16px; padding-right: 32px; cursor: pointer;"><option value="">Select Type</option><option value="int">Integer</option><option value="varchar">Varchar</option><option value="text">Text</option><option value="date">Date</option><option value="time">Time</option><option value="datetime">DateTime</option><option value="timestamp">Timestamp</option><option value="year">Year</option><option value="enum">Enum</option><option value="boolean">Boolean</option><option value="decimal">Decimal</option><option value="file">File</option><option value="relation">Relation</option></select></div>';
            html += '<div data-label="Length"><input type="text" name="values_field_sistem[]" class="field-input" placeholder="Length" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;"></div>';
            html += '<div data-label="Property"><select name="keterangan_field_sistem[]" class="field-input simple-select" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; appearance: none; background-image: url(\'data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3e%3cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'m6 8 4 4 4-4\'/%3e%3c/svg%3e\'); background-position: right 8px center; background-repeat: no-repeat; background-size: 16px; padding-right: 32px; cursor: pointer;"><option value="">Select Property</option><option value="index">Index</option><option value="unique">Unique</option><option value="nullable">Nullable</option><option value="not_null">Not Null</option></select></div>';
            html += '<div data-label="Action" style="text-align: center;"><button type="button" class="remove remove-btn" style="background: #f44336; color: white; padding: 6px; border: none; border-radius: 4px; cursor: pointer; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;"><i class="material-icons" style="font-size: 14px;">delete</i></button></div>';
            html += '</div>';
            html += '</div>';
            
            return html;
        }
        
        // Cancel add mode
        function cancelAdd() {
            // Restore original form
            $('.form-inputs').html(window.originalFormHtml);
            
            // Change title back
            $('.form-card .card-title').html('generator<br>tambah/ubah');
        }
        
        // Show loading overlay
        function showLoading() {
            // Create loading overlay
            var loadingHtml = '<div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px;">';
            loadingHtml += '<div style="text-align: center;">';
            loadingHtml += '<div style="width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>';
            loadingHtml += '<div>Creating table, please wait...</div>';
            loadingHtml += '</div>';
            loadingHtml += '</div>';
            
            // Add loading styles
            var loadingStyles = '<style>';
            loadingStyles += '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            loadingStyles += '</style>';
            
            $('body').append(loadingStyles + loadingHtml);
            
            return true; // Allow form submission to continue
        }
        
        // Edit table function
        function editTable(tableName) {
            if (confirm('Edit table: ' + tableName + '?\nThis will switch to edit mode.')) {
                // Get table structure via AJAX
                $.ajax({
                    url: 'crud.php?form=edit_table',
                    type: 'POST',
                    data: {
                        table_name: tableName,
                        action: 'get_structure'
                    },
                    success: function(response) {
                        // Response is already parsed by jQuery when Content-Type is application/json
                        var result = (typeof response === 'string') ? JSON.parse(response) : response;
                        if (result.success) {
                            switchToEditMode(result.table_name, result.fields);
                        } else {
                            alert('Error: ' + result.message);
                        }
                    },
                    error: function() {
                        alert('Error occurred while getting table structure.');
                    }
                });
            }
        }
        
        // Switch left card to edit mode
        function switchToEditMode(tableName, fields) {
            // Change card title
            $('.form-card .card-title').html('generator<br>ubah');
            
            // Store original form for restore
            if (!window.originalFormHtml) {
                window.originalFormHtml = $('.form-inputs').html();
            }
            
            // Create edit form HTML
            var editFormHtml = createEditForm(tableName, fields);
            
            // Replace form content
            $('.form-inputs').html(editFormHtml);
            
            // Auto-populate relation dropdowns for existing relation fields
            setTimeout(function() {
                $('.relation-container').each(function() {
                    const tableSelect = $(this).find('select[name="relation_table[]"]');
                    const fieldSelect = $(this).find('select[name="relation_field[]"]');
                    
                    // Load tables dropdown first
                    loadAvailableTablesEdit(tableSelect[0]);
                });
            }, 100);
            
            // Add event listener for field label auto-generate in edit mode
            $('.form-inputs').off('input', 'input[name="field_labels[]"]').on('input', 'input[name="field_labels[]"]', function() {
                console.log('jQuery edit label input event triggered');
                const $this = $(this);
                const $nameInput = $this.closest('.field-row').find('input[name="field_names[]"]');
                
                if ($nameInput.length && !$nameInput[0].readOnly) {
                    const labelValue = $this.val();
                    const fieldName = labelValue
                        .toLowerCase()
                        .replace(/[^a-z0-9\s]/g, '')
                        .trim()
                        .replace(/\s+/g, '_');
                    
                    console.log('jQuery generated field name:', fieldName);
                    $nameInput.val(fieldName);
                } else {
                    console.log('jQuery skipped: nameInput not found or readOnly');
                }
            });
            
            // Add cancel button to switch back
            var cancelBtn = '<div style="margin-top: 15px; text-align: center;">';
            cancelBtn += '<button onclick="cancelEdit()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">Cancel Edit</button>';
            cancelBtn += '</div>';
            $('.form-inputs').append(cancelBtn);
        }
        
        // Create edit form
        function createEditForm(tableName, fields) {
            var html = '<div class="edit-form-container">';
            html += '<div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196F3;">';
            html += '<h4 style="margin: 0 0 5px 0; color: #1976D2;">Editing Table: ' + tableName + '</h4>';
            html += '<p style="margin: 0; color: #666; font-size: 14px;">Modify the table structure below</p>';
            html += '</div>';
            
            html += '<form id="editTableForm">';
            html += '<input type="hidden" name="table_name" value="' + tableName + '">';
            
            // Table information (editable in edit mode)
            html += '<div style="margin-bottom: 20px;">';
            html += '<h5 style="color: #333; margin-bottom: 15px;">Table Information</h5>';
            html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
            
            // Display Name (editable)
            html += '<div>';
            var displayName = tableName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            html += '<label style="font-size: 12px; color: #333; font-weight: 600; margin-bottom: 4px; display: block;">Table Display Name:</label>';
            html += '<input type="text" name="table_display_name" value="' + displayName + '" class="modern-input" placeholder="Table Display Name" oninput="generateTableNameFromDisplay(this)" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;">';
            html += '</div>';
            
            // Table Name (auto-generated but editable)
            html += '<div>';
            html += '<label style="font-size: 12px; color: #333; font-weight: 600; margin-bottom: 4px; display: block;">Table Name:</label>';
            html += '<input type="text" name="new_table_name" value="' + tableName + '" class="modern-input" placeholder="table_name" pattern="[a-z_]+" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; background: #f9f9f9;">';
            html += '</div>';
            
            html += '</div>';
            html += '</div>';
            
            // Fields section
            html += '<div style="margin-bottom: 20px;">';
            html += '<h5 style="color: #333; margin-bottom: 15px;">Table Fields</h5>';
            html += '<div class="fields-container">';
            
            // Field headers
            html += '<div class="field-header">';
            html += '<div>Label</div><div>Name</div><div>Type</div><div>Length</div><div>Property</div><div>Action</div>';
            html += '</div>';
            
            // Add existing fields
            fields.forEach(function(field, index) {
                var isPrimary = field.column_key === 'PRI';
                var isReadonly = isPrimary;
                var isRelation = field.is_relation || false;
                
                html += '<div class="field-row' + (isPrimary ? ' primary-key' : '') + '">';
                
                // Field Label (use comment if available, fallback to formatted field name)
                html += '<div>';
                var labelValue = isPrimary ? 'ID' : (field.display_name || field.field_name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
                var labelEvent = isReadonly ? '' : ' oninput="generateFieldNameEdit(this)"';
                html += '<input type="text" name="field_labels[]" value="' + labelValue + '" class="field-input"' + (isReadonly ? ' readonly' : '') + labelEvent + '>';
                html += '</div>';
                
                // Field Name
                html += '<div>';
                html += '<input type="text" name="field_names[]" value="' + field.field_name + '" class="field-input"' + (isReadonly ? ' readonly' : '') + '>';
                html += '</div>';
                
                // Field Type
                html += '<div>';
                var typeValue = field.actual_field_type || field.data_type; // Use actual_field_type if available
                html += '<select name="field_types[]" class="field-input" onchange="handleFieldTypeChangeEdit(this)"' + (isReadonly ? ' disabled' : '') + '>';
                var types = ['int', 'varchar', 'text', 'date', 'time', 'datetime', 'timestamp', 'year', 'enum', 'boolean', 'decimal', 'file', 'relation'];
                types.forEach(function(type) {
                    var selected = typeValue.toLowerCase() === type ? ' selected' : '';
                    html += '<option value="' + type + '"' + selected + '>' + type.charAt(0).toUpperCase() + type.slice(1) + '</option>';
                });
                html += '</select>';
                html += '</div>';
                
                // Field Length
                html += '<div>';
                var lengthValue = '';
                if (isRelation) {
                    lengthValue = '11'; // Relations always use int(11)
                } else {
                    // Check if field has enum_values or field_length from server response
                    if (field.enum_values && field.enum_values !== '') {
                        lengthValue = field.enum_values; // Use enum values for enum fields
                    } else if (field.field_length && field.field_length !== '') {
                        lengthValue = field.field_length; // Use extracted field length
                    } else {
                        // Fallback: extract from column_type
                        var lengthMatch = field.column_type.match(/\((\d+)\)/);
                        lengthValue = lengthMatch ? lengthMatch[1] : '';
                    }
                }
                var lengthReadonly = isReadonly || isRelation ? ' readonly' : '';
                var lengthStyle = isRelation ? ' style="background: #f5f5f5;"' : '';
                html += '<input type="text" name="field_lengths[]" value="' + lengthValue + '" class="field-input"' + lengthReadonly + lengthStyle + '>';
                html += '</div>';
                
                // Field Property
                html += '<div>';
                var propertyDisabled = isReadonly || isRelation ? ' disabled' : '';
                var propertyStyle = isRelation ? ' style="background: #f5f5f5;"' : '';
                html += '<select name="field_properties[]" class="field-input"' + propertyDisabled + propertyStyle + '>';
                if (isPrimary) {
                    html += '<option value="primary" selected>Primary Key</option>';
                } else if (isRelation) {
                    html += '<option value="index" selected>Index</option>';
                } else {
                    html += '<option value="">Select Property</option>';
                    html += '<option value="index">Index</option>';
                    html += '<option value="unique">Unique</option>';
                    html += '<option value="nullable">Nullable</option>';
                    html += '<option value="not_null">Not Null</option>';
                }
                html += '</select>';
                html += '</div>';
                
                // Action
                html += '<div style="text-align: center;">';
                if (isPrimary) {
                    html += '<i class="material-icons lock-icon">lock</i>';
                } else {
                    html += '<button type="button" class="remove-btn" onclick="removeEditField(this)"><i class="material-icons">delete</i></button>';
                }
                html += '</div>';
                
                // Add relation container if this is a relation field
                if (isRelation && field.relation_data) {
                    var relationId = 'relation_' + index + '_' + Date.now();
                    html += '<div class="relation-container" style="grid-column: 1 / -1;" data-relation-id="' + relationId + '">';
                    html += '<div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #4CAF50; border-radius: 4px;">';
                    html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">';
                    html += '<div>';
                    html += '<label style="font-size: 12px; color: #333; font-weight: 600;">Reference Table:</label>';
                    html += '<select name="relation_table[]" class="field-input simple-select" onchange="loadTableFieldsEdit(this)" style="margin-top: 4px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;" data-selected-table="' + field.relation_data.ref_table + '">';
                    html += '<option value="">Select Table</option>';
                    html += '</select>';
                    html += '</div>';
                    html += '<div>';
                    html += '<label style="font-size: 12px; color: #333; font-weight: 600;">Display Field:</label>';
                    html += '<select name="relation_field[]" class="field-input simple-select" style="margin-top: 4px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;" data-selected-field="' + field.relation_data.ref_field + '">';
                    html += '<option value="">Select Field</option>';
                    html += '</select>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                }
                
                html += '</div>';
            });
            
            html += '<div class="before-add-more-edit"></div>';
            
            // Add field button
            html += '<button type="button" class="add-field-btn" onclick="addEditField()"><i class="material-icons">add</i>Add Field</button>';
            
            html += '</div>';
            html += '</div>';
            
            // Submit buttons
            html += '<div style="padding: 15px; background: #f8f8f8; border-top: 1px solid #ddd; margin-top: 20px;">';
            html += '<button type="button" onclick="saveTableChanges()" style="background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; margin-right: 10px;"><i class="material-icons" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">save</i>Save Changes</button>';
            html += '<button type="button" onclick="cancelEdit()" style="background: #6c757d; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>';
            html += '</div>';
            
            html += '</form>';
            html += '</div>';
            
            return html;
        }
        
        // Cancel edit mode
        function cancelEdit() {
            // Restore original form
            $('.form-inputs').html(window.originalFormHtml);
            
            // Change title back
            $('.form-card .card-title').html('generator<br>tambah/ubah');
        }
        
        // Add new field in edit mode
        function addEditField() {
            var html = '<div class="field-row" style="display: grid; grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto; gap: 16px; padding: 10px; background: white; border-bottom: 1px solid #eee; align-items: center; min-width: 600px;">';
            
            // Field Label
            html += '<div>';
            html += '<input type="text" name="field_labels[]" class="field-input" placeholder="Field Display Name" required oninput="generateFieldNameEdit(this)" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;">';
            html += '</div>';
            
            // Field Name
            html += '<div>';
            html += '<input type="text" name="field_names[]" class="field-input" placeholder="field_system_name" pattern="[a-z_]+" required style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;">';
            html += '</div>';
            
            // Field Type (same as form tambah)
            html += '<div>';
            html += '<select name="field_types[]" class="field-input simple-select" required onchange="handleFieldTypeChangeEdit(this)" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; appearance: none; background-image: url(\'data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3e%3cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'m6 8 4 4 4-4\'/%3e%3c/svg%3e\'); background-position: right 8px center; background-repeat: no-repeat; background-size: 16px; padding-right: 32px; cursor: pointer;">';
            html += '<option value="">Select Type</option>';
            html += '<option value="int">Integer</option>';
            html += '<option value="varchar">Varchar</option>';
            html += '<option value="text">Text</option>';
            html += '<option value="date">Date</option>';
            html += '<option value="time">Time</option>';
            html += '<option value="datetime">DateTime</option>';
            html += '<option value="timestamp">Timestamp</option>';
            html += '<option value="year">Year</option>';
            html += '<option value="enum">Enum</option>';
            html += '<option value="boolean">Boolean</option>';
            html += '<option value="decimal">Decimal</option>';
            html += '<option value="file">File</option>';
            html += '<option value="relation">Relation</option>';
            html += '</select>';
            html += '</div>';
            
            // Field Length
            html += '<div>';
            html += '<input type="text" name="field_lengths[]" class="field-input" placeholder="Length" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;">';
            html += '</div>';
            
            // Field Property
            html += '<div>';
            html += '<select name="field_properties[]" class="field-input simple-select" style="padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; appearance: none; background-image: url(\'data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3e%3cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'m6 8 4 4 4-4\'/%3e%3c/svg%3e\'); background-position: right 8px center; background-repeat: no-repeat; background-size: 16px; padding-right: 32px; cursor: pointer;">';
            html += '<option value="">Select Property</option>';
            html += '<option value="index">Index</option>';
            html += '<option value="unique">Unique</option>';
            html += '<option value="nullable">Nullable</option>';
            html += '<option value="not_null">Not Null</option>';
            html += '</select>';
            html += '</div>';
            
            // Action
            html += '<div style="text-align: center;">';
            html += '<button type="button" class="remove-btn" onclick="removeEditField(this)" style="background: #f44336; color: white; padding: 6px; border: none; border-radius: 4px; cursor: pointer; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">';
            html += '<i class="material-icons" style="font-size: 14px;">delete</i>';
            html += '</button>';
            html += '</div>';
            
            html += '</div>';
            
            $('.before-add-more-edit').before(html);
        }
        
        // Remove field in edit mode
        function removeEditField(button) {
            const fieldRow = $(button).closest('.field-row');
            
            // Find and remove associated relation container
            let nextElement = fieldRow.next();
            while (nextElement.length > 0) {
                if (nextElement.hasClass('relation-container')) {
                    nextElement.remove();
                    break;
                } else if (nextElement.hasClass('field-row')) {
                    // Found another field row, stop searching
                    break;
                }
                nextElement = nextElement.next();
            }
            
            // Remove the field row
            fieldRow.remove();
        }
        
        // Save table changes
        function saveTableChanges() {
            // Build confirmation message with details
            let confirmMessage = 'Save changes to this table?\n\nThis will:\n';
            confirmMessage += '• Update table structure\n';
            confirmMessage += '• Add new fields\n';
            confirmMessage += '• Modify existing fields\n';
            confirmMessage += '• DELETE removed fields from database\n';
            confirmMessage += '• Regenerate all Panel files\n\n';
            confirmMessage += 'WARNING: Field deletion is permanent!';
            
            if (confirm(confirmMessage)) {
                // Collect form data
                const formData = new FormData(document.getElementById('editTableForm'));
                
                // Add relation data
                const relationTables = [];
                const relationFields = [];
                $('.relation-container').each(function() {
                    const table = $(this).find('select[name="relation_table[]"]').val();
                    const field = $(this).find('select[name="relation_field[]"]').val();
                    relationTables.push(table || '');
                    relationFields.push(field || '');
                });
                
                // Append relation data to form
                relationTables.forEach(table => formData.append('relation_table[]', table));
                relationFields.forEach(field => formData.append('relation_field[]', field));
                
                // Send AJAX request
                fetch('crud.php?form=update_table', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Refresh table list
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error occurred while saving table changes.');
                });
            }
        }
        
        // Delete table function
        function deleteTable(tableName) {
            if (confirm('Are you sure you want to delete table: ' + tableName + '?\nThis will also delete the corresponding Panel folder and cannot be undone!')) {
                // Send AJAX request to delete table and folder
                $.ajax({
                    url: 'crud.php?form=delete_table',
                    type: 'POST',
                    data: {
                        table_name: tableName
                    },
                    success: function(response) {
                        // Response is already parsed by jQuery when Content-Type is application/json
                        var result = (typeof response === 'string') ? JSON.parse(response) : response;
                        if (result.success) {
                            alert('Table and folder deleted successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + result.message);
                        }
                    },
                    error: function() {
                        alert('Error occurred while deleting table.');
                    }
                });
            }
        }
        
        // Make functions global
        window.showAddForm = showAddForm;
        window.createAddForm = createAddForm;
        window.initializeAddFormHandlers = initializeAddFormHandlers;
        window.cancelAdd = cancelAdd;
        window.showLoading = showLoading;
        window.editTable = editTable;
        window.deleteTable = deleteTable;
        window.switchToEditMode = switchToEditMode;
        window.createEditForm = createEditForm;
        window.cancelEdit = cancelEdit;
        window.addEditField = addEditField;
        window.removeEditField = removeEditField;
        window.saveTableChanges = saveTableChanges;
        window.handleFieldTypeChange = handleFieldTypeChange;
        window.loadAvailableTables = loadAvailableTables;
        window.loadTableFields = loadTableFields;
        
        // Handle field type change in edit mode
        window.handleFieldTypeChangeEdit = function(selectElement) {
            const fieldRow = selectElement.closest('.field-row');
            const fieldType = selectElement.value;
            
            // Find relation container associated with this field row
            let relationContainer = null;
            let nextElement = fieldRow.nextElementSibling;
            while (nextElement) {
                if (nextElement.classList.contains('relation-container')) {
                    relationContainer = nextElement;
                    break;
                } else if (nextElement.classList.contains('field-row')) {
                    // Found another field row, stop searching
                    break;
                }
                nextElement = nextElement.nextElementSibling;
            }
            
            if (fieldType === 'relation') {
                if (!relationContainer) {
                    relationContainer = document.createElement('div');
                    relationContainer.className = 'relation-container';
                    relationContainer.style.gridColumn = '1 / -1';
                    relationContainer.innerHTML = `
                        <div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #4CAF50; border-radius: 4px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label style="font-size: 12px; color: #333; font-weight: 600;">Reference Table:</label>
                                    <select name="relation_table[]" class="field-input simple-select" onchange="loadTableFieldsEdit(this)" style="margin-top: 4px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;">
                                        <option value="">Select Table</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #333; font-weight: 600;">Display Field:</label>
                                    <select name="relation_field[]" class="field-input simple-select" style="margin-top: 4px; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%;">
                                        <option value="">Select Field</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                    fieldRow.parentNode.insertBefore(relationContainer, fieldRow.nextSibling);
                    loadAvailableTablesEdit(relationContainer.querySelector('select[name="relation_table[]"]'));
                }
                const lengthInput = fieldRow.querySelector('input[name="field_lengths[]"]');
                const propertySelect = fieldRow.querySelector('select[name="field_properties[]"]');
                if (lengthInput) { 
                    lengthInput.value = '11'; 
                    lengthInput.readOnly = true; 
                    lengthInput.style.background = '#f5f5f5'; 
                }
                if (propertySelect) { 
                    propertySelect.value = 'index'; 
                    propertySelect.disabled = true; 
                    propertySelect.style.background = '#f5f5f5'; 
                }
            } else {
                if (relationContainer) { 
                    relationContainer.remove(); 
                }
                const lengthInput = fieldRow.querySelector('input[name="field_lengths[]"]');
                const propertySelect = fieldRow.querySelector('select[name="field_properties[]"]');
                if (lengthInput) { 
                    lengthInput.readOnly = false; 
                    lengthInput.style.background = 'white'; 
                    lengthInput.value = ''; 
                }
                if (propertySelect) { 
                    propertySelect.disabled = false; 
                    propertySelect.style.background = 'white'; 
                    propertySelect.value = ''; 
                }
            }
        }
        
        // Load available tables for edit mode
        window.loadAvailableTablesEdit = function(selectElement) {
            const selectedTable = selectElement.getAttribute('data-selected-table');
            
            fetch('api_crud/get_available_tables.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    selectElement.innerHTML = '<option value="">Select Table</option>';
                    data.tables.forEach(table => {
                        const selected = (selectedTable && table.name === selectedTable) ? ' selected' : '';
                        selectElement.innerHTML += `<option value="${table.name}"${selected}>${table.label}</option>`;
                    });
                    
                    // If there's a selected table, automatically load its fields
                    if (selectedTable) {
                        selectElement.value = selectedTable;
                        // Trigger change event to load fields
                        setTimeout(() => {
                            loadTableFieldsEdit(selectElement);
                        }, 100);
                    }
                }
            })
            .catch(error => console.error('Error loading tables:', error));
        }
        
        // Load table fields for edit mode
        window.loadTableFieldsEdit = function(selectElement) {
            const tableName = selectElement.value;
            if (!tableName) return;
            
            const relationContainer = selectElement.closest('.relation-container');
            const fieldSelect = relationContainer.querySelector('select[name="relation_field[]"]');
            const selectedField = fieldSelect.getAttribute('data-selected-field');
            
            fetch('api_crud/get_table_fields.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'table_name=' + encodeURIComponent(tableName)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fieldSelect.innerHTML = '<option value="">Select Field</option>';
                    data.fields.forEach(field => {
                        const selected = (selectedField && field.name === selectedField) ? ' selected' : '';
                        fieldSelect.innerHTML += `<option value="${field.name}"${selected}>${field.label}</option>`;
                    });
                    
                    // Set selected field if exists
                    if (selectedField) {
                        fieldSelect.value = selectedField;
                    }
                    
                    // Auto-generate field name: id_tablename (only for new fields, not editing existing)
                    const fieldRow = relationContainer.previousElementSibling;
                    const nameInput = fieldRow.querySelector('input[name="field_names[]"]');
                    if (nameInput && !nameInput.readOnly && !nameInput.value) {
                        nameInput.value = 'id_' + tableName;
                    }
                }
            })
            .catch(error => console.error('Error loading fields:', error));
        }
        
        // Auto-generate field name from field label
        window.generateFieldName = function(labelInput) {
            const fieldRow = labelInput.closest('.field-row');
            const nameInput = fieldRow.querySelector('input[name="field_names[]"], input[name="nama_field_sistem[]"]');
            
            if (nameInput && !nameInput.readOnly) {
                const labelValue = labelInput.value;
                const fieldName = labelValue
                    .toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '') // Remove special characters
                    .trim()
                    .replace(/\s+/g, '_'); // Replace spaces with underscores
                
                nameInput.value = fieldName;
            }
        }
        
        // Auto-generate field name for edit mode
        window.generateFieldNameEdit = function(labelInput) {
            console.log('generateFieldNameEdit called with:', labelInput);
            const fieldRow = labelInput.closest('.field-row');
            console.log('fieldRow found:', fieldRow);
            const nameInput = fieldRow.querySelector('input[name="field_names[]"]');
            console.log('nameInput found:', nameInput, 'readOnly:', nameInput?.readOnly);
            
            if (nameInput && !nameInput.readOnly) {
                const labelValue = labelInput.value;
                const fieldName = labelValue
                    .toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '') // Remove special characters
                    .trim()
                    .replace(/\s+/g, '_'); // Replace spaces with underscores
                
                console.log('Generated field name:', fieldName);
                nameInput.value = fieldName;
            } else {
                console.log('Skipped: nameInput not found or readOnly');
            }
        }
        
        // Auto-generate table name from display name in edit mode
        window.generateTableNameFromDisplay = function(displayInput) {
            const tableNameInput = displayInput.closest('div').parentElement.querySelector('input[name="new_table_name"]');
            if (tableNameInput) {
                const displayValue = displayInput.value;
                const tableName = displayValue
                    .toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '') // Remove special characters
                    .trim()
                    .replace(/\s+/g, '_'); // Replace spaces with underscores
                
                tableNameInput.value = tableName;
            }
        }
    </script>

</body>
</html>