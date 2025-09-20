<?php
// Initialize variables to prevent undefined variable warnings
if (!isset($data)) {
    $data = array(
        'table_display_name' => '',
        'new_table_name' => ''
    );
}
if (!isset($rows)) {
    $rows = array(
        'field_labels' => '',
        'field_names' => '',
        'field_lengths' => ''
    );
}
if (!isset($folder)) {
    $folder = 'azzam';
}
?>

<style>
/* Hide plugin containers */
.select2-container,
.selectize-control,
.chosen-container,
.bootstrap-select {
    display: none !important;
}

/* Clean Edit Form Container */
.edit-form-container {
    padding: 0;
}

/* Simple Form Styles */
.modern-form {
    background: transparent !important;
    padding: 0;
    border: none;
    width: 100%;
    max-height: none !important;
    position: static !important;
    overflow: visible !important;
}

.modern-form-container {
    width: 100%;
    display: block;
}

/* Table Information Section */
.table-info-section {
    margin-bottom: 20px;
    padding: 15px;
    background: #e3f2fd;
    border-radius: 8px;
    border-left: 4px solid #2196F3;
}

.table-info-section h4 {
    margin: 0 0 5px 0;
    color: #1976D2;
    font-size: 1.1rem;
}

.table-info-section p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

/* Section Headers */
.section-header {
    color: #333;
    margin-bottom: 15px;
    font-size: 1rem;
    font-weight: 600;
}

.modern-input {
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    width: 100%;
    transition: all 0.2s ease;
}

.modern-input:focus {
    outline: none;
    border-color: #2196F3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
}

.modern-input:read-only {
    background: #f5f5f5;
    color: #666;
}

/* Fields Container */
.fields-container {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    max-height: 300px;
    overflow-y: auto;
}

.fields-container::-webkit-scrollbar {
    width: 6px;
}

.fields-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.fields-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.fields-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Field Header */
.field-header {
    display: grid;
    grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto;
    gap: clamp(8px, 2vw, 16px);
    background: #333;
    color: white;
    padding: clamp(8px, 2vw, 12px);
    font-size: clamp(10px, 2vw, 12px);
    font-weight: 600;
    overflow-x: auto;
    min-width: 600px;
}

/* Field Row */
.field-row {
    display: grid;
    grid-template-columns: 2fr 2fr 1.5fr 1fr 1.5fr auto;
    gap: clamp(8px, 2vw, 16px);
    padding: clamp(6px, 1.5vw, 10px);
    background: white;
    border-bottom: 1px solid #eee;
    align-items: center;
    overflow-x: auto;
    min-width: 600px;
}

.field-row.primary-key {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
}

/* Field Input */
.field-input {
    padding: clamp(4px, 1vw, 8px) clamp(6px, 1.5vw, 10px);
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: clamp(11px, 2vw, 13px);
    width: 100%;
    box-sizing: border-box;
    min-height: 32px;
}

.field-input:focus {
    outline: none;
    border-color: #2196F3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
}

/* Simple Select Styling */
.simple-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 8px center;
    background-repeat: no-repeat;
    background-size: 16px;
    padding-right: 32px;
    cursor: pointer;
}

.simple-select:disabled {
    background-color: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

/* Buttons */
.add-field-btn {
    background: #4CAF50;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin: 15px auto;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    width: fit-content;
}

.add-field-btn:hover {
    background: #45a049;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
}

.remove-btn {
    background: #f44336;
    color: white;
    padding: 6px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-btn:hover {
    background: #d32f2f;
}

.lock-icon {
    color: #ff9800;
    font-size: 20px;
}

/* Form Footer */
.form-footer {
    padding: 15px;
    background: #f8f8f8;
    border-top: 1px solid #ddd;
    margin-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.footer-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
}

.reset-btn {
    background: #6c757d;
    color: white;
}

.reset-btn:hover {
    background: #5a6268;
}

.submit-btn {
    background: #4CAF50;
    color: white;
}

.submit-btn:hover {
    background: #45a049;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .simple-cards {
        grid-template-columns: 1fr !important;
        gap: 15px;
    }
    
    .simple-card {
        min-height: auto !important;
    }
    
    .field-header {
        display: none;
    }
    
    .field-row {
        grid-template-columns: 1fr;
        gap: 8px;
        min-width: auto;
        padding: 12px;
        margin-bottom: 16px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    
    .field-row > div {
        margin-bottom: 8px;
    }
    
    .field-row > div::before {
        content: attr(data-label) ": ";
        font-weight: 600;
        font-size: 0.75rem;
        color: #2196F3;
        display: block;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .field-row > div:last-child::before {
        display: none;
    }
}
</style>

<script>
// Prevent any plugin initialization
document.addEventListener('DOMContentLoaded', function() {
    // Disable common select plugins
    if (window.jQuery) {
        $.fn.select2 = function() { return this; };
        $.fn.selectize = function() { return this; };
        $.fn.chosen = function() { return this; };
    }
    
    // Remove any existing plugin containers
    setTimeout(function() {
        document.querySelectorAll('.select2-container, .selectize-control, .chosen-container').forEach(function(el) {
            el.remove();
        });
    }, 100);
});

// Auto-generate table name from display name
function generateTableName() {
    const displayName = document.getElementById('table_display_name').value;
    const tableName = displayName
        .toLowerCase()                    // Convert to lowercase
        .replace(/[^a-z0-9\s]/g, '')     // Remove special characters except spaces
        .trim()                          // Remove leading/trailing spaces
        .replace(/\s+/g, '_');           // Replace spaces with underscores
    
    document.getElementById('new_table_name').value = tableName;
}

// Handle field type change for relation
function handleFieldTypeChange(selectElement) {
    const fieldRow = selectElement.closest('.field-row');
    const fieldType = selectElement.value;
    
    // Get or create relation UI container
    let relationContainer = fieldRow.querySelector('.relation-container');
    
    if (fieldType === 'relation') {
        if (!relationContainer) {
            // Create relation UI
            relationContainer = document.createElement('div');
            relationContainer.className = 'relation-container';
            relationContainer.innerHTML = `
                <div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border: 1px solid #4CAF50; border-radius: 4px;">
                    <div style="margin-bottom: 8px;">
                        <label style="font-size: 12px; color: #333; font-weight: 600;">Reference Table:</label>
                        <select name="relation_table[]" class="field-input simple-select" onchange="loadTableFields(this)" style="margin-top: 4px;">
                            <option value="">Select Table</option>
                            ${getAvailableTables()}
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 12px; color: #333; font-weight: 600;">Display Field:</label>
                        <select name="relation_field[]" class="field-input simple-select" style="margin-top: 4px;">
                            <option value="">Select Field</option>
                        </select>
                    </div>
                </div>
            `;
            fieldRow.appendChild(relationContainer);
        }
        
        // Auto-set properties for relation
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
        // Remove relation UI if exists
        if (relationContainer) {
            relationContainer.remove();
        }
        
        // Reset properties
        const lengthInput = fieldRow.querySelector('input[name="field_lengths[]"]');
        const propertySelect = fieldRow.querySelector('select[name="field_properties[]"]');
        
        if (lengthInput) {
            lengthInput.readOnly = false;
            lengthInput.style.background = 'white';
        }
        
        if (propertySelect) {
            propertySelect.disabled = false;
            propertySelect.style.background = 'white';
        }
    }
}

// Get available tables from database
function getAvailableTables() {
    let optionsHtml = '';
    
    // Fetch tables synchronously for initial load
    fetch('crud.php?action=get_available_tables', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.tables) {
            data.tables.forEach(table => {
                optionsHtml += `<option value="${table.name}">${table.label}</option>`;
            });
            
            // Update all existing relation table selects
            document.querySelectorAll('select[name="relation_table[]"]').forEach(select => {
                const currentValue = select.value;
                select.innerHTML = '<option value="">Select Table</option>' + optionsHtml;
                if (currentValue) select.value = currentValue;
            });
        }
    })
    .catch(error => {
        console.error('Error loading tables:', error);
    });
    
    return optionsHtml;
}

// Load fields for selected table
function loadTableFields(selectElement) {
    const tableName = selectElement.value;
    const fieldRow = selectElement.closest('.field-row');
    const relationFieldSelect = fieldRow.querySelector('select[name="relation_field[]"]');
    const nameInput = fieldRow.querySelector('input[name="field_names[]"]');
    
    if (tableName && nameInput) {
        // Auto-generate field name: id_table_name
        nameInput.value = 'id_' + tableName;
    }
    
    if (tableName) {
        // AJAX call to get table fields
        fetch('crud.php?action=get_table_fields', {
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
                    option.textContent = field.label || field.name;
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
</script>

<form method="POST" action="<?=$folder;?>/proses.php" class="modern-form-container no-plugins">
    <div class="modern-form">
        <div class="edit-form-container">
                <!-- Table Information Header -->
                <div class="table-info-section">
                    <h4>Create New Table</h4>
                    <p>Configure table structure and field properties below</p>
                </div>
                
                <!-- Table Information Section -->
                <div style="margin-bottom: 20px;">
                    <h5 class="section-header">Table Information</h5>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                        <div>
                            <input 
                                type="text" 
                                name="table_display_name" 
                                id="table_display_name"
                                class="modern-input" 
                                value="<?=isset($data['table_display_name']) ? $data['table_display_name'] : '';?>" 
                                required
                                placeholder="Table display name"
                                oninput="generateTableName()"
                            >
                        </div>
                        <div>
                            <input 
                                type="text" 
                                name="new_table_name" 
                                id="new_table_name"
                                class="modern-input" 
                                value="<?=isset($data['new_table_name']) ? $data['new_table_name'] : '';?>" 
                                required
                                pattern="[a-z_]+"
                                placeholder="table_name (auto generated)"
                                readonly
                                style="background: #f5f5f5; color: #666;"
                            >
                        </div>
                    </div>
                </div>
                
                <!-- Field Configuration Section -->
                <div style="margin-bottom: 20px;">
                    <h5 class="section-header">Table Fields</h5>
                    <div class="fields-container">
                        <!-- Field Headers -->
                        <div class="field-header">
                            <div>Label</div>
                            <div>Name</div>
                            <div>Type</div>
                            <div>Length</div>
                            <div>Property</div>
                            <div>Action</div>
                        </div>
                        
                        <!-- Primary Key Field -->
                        <div class="field-row primary-key">
                            <div>
                                <input type="text" name="field_labels[]" value="ID" class="field-input" readonly>
                            </div>
                            <div>
                                <input type="text" name="field_names[]" value="id" class="field-input" readonly>
                            </div>
                            <div>
                                <select name="field_types[]" class="field-input simple-select" disabled>
                                    <option value="int" selected>Integer</option>
                                </select>
                                <input type="hidden" name="field_types[]" value="int">
                            </div>
                            <div>
                                <input type="text" name="field_lengths[]" value="11" class="field-input" readonly>
                            </div>
                            <div>
                                <select name="field_properties[]" class="field-input simple-select" disabled>
                                    <option value="primary" selected>Primary Key</option>
                                </select>
                                <input type="hidden" name="field_properties[]" value="primary">
                            </div>
                            <div style="text-align: center;">
                                <i class="material-icons lock-icon">lock</i>
                            </div>
                        </div>
                        
                        <!-- Auto Input Date Field -->
                        <div class="field-row" style="background: #e8f5e8; border: 1px solid #4caf50;">
                            <div>
                                <input type="text" value="Input Date" class="field-input" readonly style="background: #f0fdf4;">
                            </div>
                            <div>
                                <input type="text" value="input_date" class="field-input" readonly style="background: #f0fdf4;">
                            </div>
                            <div>
                                <select class="field-input simple-select" disabled style="background: #f0fdf4;">
                                    <option value="datetime" selected>DateTime</option>
                                </select>
                                <input type="hidden" name="field_types[]" value="datetime">
                            </div>
                            <div>
                                <input type="text" value="CURRENT_TIMESTAMP" class="field-input" readonly style="background: #f0fdf4;">
                                <input type="hidden" name="field_lengths[]" value="">
                            </div>
                            <div>
                                <select class="field-input simple-select" disabled style="background: #f0fdf4;">
                                    <option value="auto" selected>Auto Added</option>
                                </select>
                                <input type="hidden" name="field_properties[]" value="auto">
                            </div>
                            <div style="text-align: center;">
                                <i class="material-icons" style="color: #4caf50;">schedule</i>
                            </div>
                        </div>
                        
                        <!-- Dynamic Fields Container -->
                        <div class="before-add-more"></div>
                        
                        <!-- Add Field Button -->
                        <button type="button" class="add-more add-field-btn">
                            <i class="material-icons">add</i>
                            Add Field
                        </button>
                    </div>
                </div>
                
                <!-- Form Footer -->
                <div class="form-footer">
                    <button type="reset" class="footer-btn reset-btn">
                        <i class="material-icons">refresh</i>
                        Reset
                    </button>
                    <button type="submit" name="tambah" class="footer-btn submit-btn">
                        <i class="material-icons">save</i>
                        Generate
                    </button>
                </div>
            </div>
        </div>
</form>

<!-- Dynamic Field Template -->
<div class="copy" style="display: none;">
    <div class="field-row">
        <div data-label="Label">
            <input 
                type="text" 
                name="field_labels[]" 
                class="field-input" 
                placeholder="Field Display Name"
                required
                oninput="generateFieldName(this)"
            >
        </div>
        <div data-label="Name">
            <input 
                type="text" 
                name="field_names[]" 
                class="field-input" 
                placeholder="field_system_name" 
                pattern="[a-z_]+"
                required
            >
        </div>
        <div data-label="Type">
                                        <select name="field_types[]" class="field-input simple-select" required onchange="handleFieldTypeChange(this)">
                                <option value="">Select Type</option>
                                <option value="int">Integer</option>
                                <option value="varchar">Varchar</option>
                                <option value="text">Text</option>
                                <option value="date">Date</option>
                                <option value="time">Time</option>
                                <option value="datetime">DateTime</option>
                                <option value="timestamp">Timestamp</option>
                                <option value="year">Year</option>
                                <option value="enum">Enum</option>
                                <option value="boolean">Boolean</option>
                                <option value="decimal">Decimal</option>
                                <option value="file">File</option>
                                <option value="relation">Relation</option>
                            </select>
        </div>
        <div data-label="Length">
            <input 
                type="text" 
                name="field_lengths[]" 
                class="field-input" 
                placeholder="Length"
            >
        </div>
        <div data-label="Property">
            <select name="field_properties[]" class="field-input simple-select">
                <option value="">Select Property</option>
                <option value="index">Index</option>
                <option value="unique">Unique</option>
                <option value="nullable">Nullable</option>
                <option value="not_null">Not Null</option>
            </select>
        </div>
        <div data-label="Action" style="text-align: center;">
            <button type="button" class="remove remove-btn">
                <i class="material-icons">delete</i>
            </button>
        </div>
    </div>
</div>