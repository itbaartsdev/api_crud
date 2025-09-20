    </main>

    <!-- Modern Footer -->
    <footer style="background: rgba(255, 255, 255, 0.95); border-top: 1px solid rgba(255, 255, 255, 0.2); margin-top: 60px; padding: 20px 0;">
        <div style="max-width: 1400px; margin: 0 auto; padding: 0 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="material-icons" style="font-size: 16px;">storage</i>
                        </div>
                        <span style="color: #2d3748; font-size: 1rem; font-weight: 600;">CRUD Generator Hybrid</span>
                    </div>
                    <p style="color: #718096; margin: 0; font-size: 0.85rem;">Sistem Hybrid GitHub-Local</p>
                </div>
                <div style="text-align: right;">
                    <p style="color: #718096; margin: 0; font-size: 0.85rem;">© 2025 itbaarts_dev. All rights reserved.</p>
                    <p style="color: #a0aec0; margin: 4px 0 0 0; font-size: 0.75rem;">Hybrid System v1.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Core Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            console.log('CRUD Generator Hybrid System Loaded');
            
            // Initialize modals
            $('.modal').each(function() {
                if (typeof M !== 'undefined' && M.Modal) {
                    M.Modal.init(this);
                }
            });
            
            // Add field functionality
            $(".add-more").click(function(){ 
                var html = $(".copy").html();
                $(".before-add-more").before(html);
            });

            // Remove field functionality
            $("body").on("click",".remove",function(){ 
                $(this).closest('.field-row').remove();
            });
            
            // Auto-generate field name from label
            $(document).on('input', 'input[name="field_labels[]"]', function() {
                var label = $(this).val();
                var fieldName = label.toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '')
                    .replace(/\s+/g, '_')
                    .replace(/^_+|_+$/g, '');
                $(this).closest('.field-row').find('input[name="field_names[]"]').val(fieldName);
            });
            
            // Success message
            if (window.location.search.includes('success')) {
                alert('Table generated successfully!');
            }
        });
        
        // Global functions
        function showAddForm() {
            // Implementation for showing add form
            console.log('Show add form functionality');
        }
        
        function editTable(tableName) {
            if (confirm('Edit table: ' + tableName + '?')) {
                window.location.href = 'crud.php?form=Ubah&table=' + tableName;
            }
        }
        
        function deleteTable(tableName) {
            if (confirm('Are you sure you want to delete table: ' + tableName + '?\nThis action cannot be undone!')) {
                $.ajax({
                    url: 'crud.php?form=delete_table',
                    type: 'POST',
                    data: { table_name: tableName },
                    success: function(response) {
                        var result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            alert('Table deleted successfully!');
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
    </script>
    
</body>
</html>