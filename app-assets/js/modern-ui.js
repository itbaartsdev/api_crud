// Modern UI Enhancements JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize modern features
    initializeModernFeatures();
    
    // Add field functionality
    initializeAddFieldFeatures();
    
    // Initialize tooltips and animations
    initializeTooltips();
    
    // Initialize loading states
    initializeLoadingStates();
});

// Initialize modern UI features
function initializeModernFeatures() {
    // Add smooth scrolling
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Add modern focus effects
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('modern-focus');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('modern-focus');
        });
    });
    
    // Add modern card hover effects
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// Initialize add field features
function initializeAddFieldFeatures() {
    // Add field button functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-more')) {
            e.preventDefault();
            addNewField();
        }
        
        if (e.target.closest('.remove')) {
            e.preventDefault();
            removeField(e.target.closest('.modern-field-grid'));
        }
    });
}

// Add new field function
function addNewField() {
    const copyTemplate = document.querySelector('.copy');
    const container = document.querySelector('.before-add-more');
    
    if (copyTemplate && container) {
        const newField = copyTemplate.cloneNode(true);
        newField.classList.remove('copy', 'hide');
        newField.classList.add('modern-field-animation');
        
        // Clear input values
        const inputs = newField.querySelectorAll('input');
        inputs.forEach(input => {
            if (input.name !== 'values_field_sistem[]') {
                input.value = '';
            }
        });
        
        // Reset selects
        const selects = newField.querySelectorAll('select');
        selects.forEach(select => {
            select.selectedIndex = 0;
        });
        
        container.appendChild(newField);
        
        // Animate the new field
        setTimeout(() => {
            newField.style.opacity = '1';
            newField.style.transform = 'translateX(0)';
        }, 10);
        
        // Add success notification
        showNotification('Field baru berhasil ditambahkan!', 'success');
    }
}

// Remove field function
function removeField(fieldElement) {
    if (fieldElement) {
        fieldElement.style.transform = 'translateX(-100%)';
        fieldElement.style.opacity = '0';
        
        setTimeout(() => {
            fieldElement.remove();
            showNotification('Field berhasil dihapus!', 'info');
        }, 300);
    }
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            createTooltip(this, this.getAttribute('title'));
        });
        
        element.addEventListener('mouseleave', function() {
            removeTooltip();
        });
    });
}

// Create tooltip
function createTooltip(element, text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'modern-tooltip';
    tooltip.textContent = text;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    setTimeout(() => {
        tooltip.classList.add('modern-tooltip-visible');
    }, 10);
}

// Remove tooltip
function removeTooltip() {
    const tooltip = document.querySelector('.modern-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Initialize loading states
function initializeLoadingStates() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            showLoadingOverlay();
        });
    });
}

// Show loading overlay
function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-spinner"></div>
        <p style="margin-top: 15px; color: #667eea; font-weight: 600;">Memproses data...</p>
    `;
    
    document.body.appendChild(overlay);
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `modern-notification ${type}`;
    notification.innerHTML = `
        <i class="material-icons">${getNotificationIcon(type)}</i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('modern-notification-visible');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('modern-notification-visible');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Get notification icon
function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'check_circle';
        case 'error': return 'error';
        case 'warning': return 'warning';
        default: return 'info';
    }
}

// Modern table enhancements
function initializeTableEnhancements() {
    const tables = document.querySelectorAll('.display');
    tables.forEach(table => {
        // Add row hover effects
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(102, 126, 234, 0.05)';
                this.style.transform = 'scale(1.01)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.transform = 'scale(1)';
            });
        });
    });
}

// Initialize on page load
window.addEventListener('load', function() {
    initializeTableEnhancements();
    
    // Add fade-in effect to page content
    const mainContent = document.querySelector('#main');
    if (mainContent) {
        mainContent.style.opacity = '0';
        mainContent.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            mainContent.style.transition = 'all 0.6s ease';
            mainContent.style.opacity = '1';
            mainContent.style.transform = 'translateY(0)';
        }, 100);
    }
});

// Modern search functionality
function initializeModernSearch() {
    const searchInputs = document.querySelectorAll('input[type="search"], input[type="text"]');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Add debounced search functionality
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                // Implement search logic here
                console.log('Searching for:', this.value);
            }, 300);
        });
    });
}

// Add modern form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required], select[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Mohon lengkapi semua field yang diperlukan!', 'error');
            }
        });
    });
}

// Validate individual field
function validateField(field) {
    const value = field.value.trim();
    const isValid = value !== '';
    
    if (isValid) {
        field.classList.remove('error');
        field.classList.add('valid');
    } else {
        field.classList.remove('valid');
        field.classList.add('error');
    }
    
    return isValid;
}

// Initialize all features when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeModernSearch();
    initializeFormValidation();
});