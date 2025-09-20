/**
 * Azzam Simple UI JavaScript
 * Basic interactions for the clean interface
 */

// Simple UI Controller
class SimpleUIController {
    constructor() {
        this.init();
    }

    init() {
        this.initializeComponents();
        this.bindEvents();
        console.log('✅ Azzam Simple UI initialized');
    }

    initializeComponents() {
        // Initialize basic Materialize components
        $('.dropdown-trigger').dropdown();
        $('.modal').modal();
        $('.tooltipped').tooltip();
        $('.sidenav').sidenav();
        $('.collapsible').collapsible();
    }

    bindEvents() {
        // Basic form interactions
        this.bindFormInteractions();
    }

    bindFormInteractions() {
        // Auto-generate field names from labels
        $(document).on('input', '[name="field_labels[]"]', function() {
            const row = $(this).closest('.copy').length ? 
                      $(this).closest('.copy').parent().find('[name="field_names[]"]').last() :
                      $(this).parent().parent().find('[name="field_names[]"]');
            
            const value = $(this).val().toLowerCase()
                                  .replace(/[^a-z0-9]/g, '_')
                                  .replace(/_+/g, '_')
                                  .replace(/^_|_$/g, '');
            
            if (!row.data('manually-edited')) {
                row.val(value);
            }
        });

        // Mark field name as manually edited
        $(document).on('input', '[name="field_names[]"]', function() {
            $(this).data('manually-edited', true);
        });
    }
}

// Initialize when document is ready
$(document).ready(function() {
    // Initialize Materialize components first
    M.AutoInit();
    
    // Then initialize our simple UI
    const simpleUI = new SimpleUIController();
    
    // Make it globally available
    window.simpleUIController = simpleUI;
});