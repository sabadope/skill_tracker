/**
 * Main JavaScript functionality for Skill Development Tracker
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    initializeMobileMenu();
    
    // Alert message auto-close
    initializeAlerts();
    
    // Modal initialization
    initializeModals();
    
    // Form validation
    initializeFormValidation();
});

/**
 * Initialize mobile menu functionality
 */
function initializeMobileMenu() {
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
}

/**
 * Initialize alert messages with auto-close
 */
function initializeAlerts() {
    const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
    
    alerts.forEach((alert) => {
        const closeButton = alert.querySelector('svg');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                alert.style.display = 'none';
            });
        }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    });
}

/**
 * Initialize modal dialogs
 */
function initializeModals() {
    // Add Skill Modal
    const addSkillBtn = document.getElementById('addSkillBtn');
    const addSkillModal = document.getElementById('addSkillModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    
    if (addSkillBtn && addSkillModal && closeModalBtn) {
        addSkillBtn.addEventListener('click', () => {
            addSkillModal.classList.remove('hidden');
        });
        
        closeModalBtn.addEventListener('click', () => {
            addSkillModal.classList.add('hidden');
        });
        
        // Close modal when clicking outside
        addSkillModal.addEventListener('click', (e) => {
            if (e.target === addSkillModal) {
                addSkillModal.classList.add('hidden');
            }
        });
    }
    
    // Assign Task Modal
    const assignTaskBtn = document.getElementById('assignTaskBtn');
    const assignTaskModal = document.getElementById('assignTaskModal');
    const closeTaskModalBtn = document.getElementById('closeTaskModalBtn');
    
    if (assignTaskBtn && assignTaskModal && closeTaskModalBtn) {
        assignTaskBtn.addEventListener('click', () => {
            assignTaskModal.classList.remove('hidden');
        });
        
        closeTaskModalBtn.addEventListener('click', () => {
            assignTaskModal.classList.add('hidden');
        });
        
        // Close modal when clicking outside
        assignTaskModal.addEventListener('click', (e) => {
            if (e.target === assignTaskModal) {
                assignTaskModal.classList.add('hidden');
            }
        });
    }
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    
                    // Add error message if not exists
                    const errorId = `${field.id}-error`;
                    if (!document.getElementById(errorId)) {
                        const errorMsg = document.createElement('p');
                        errorMsg.id = errorId;
                        errorMsg.className = 'text-red-500 text-xs mt-1';
                        errorMsg.innerText = 'This field is required';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.classList.remove('border-red-500');
                    
                    // Remove error message if exists
                    const errorMsg = document.getElementById(`${field.id}-error`);
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    });
}

/**
 * Format a date for display
 * @param {string} dateString - The date string to format
 * @returns {string} Formatted date string
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

/**
 * Calculate percentage for progress bars
 * @param {number} value - Current value
 * @param {number} max - Maximum value
 * @returns {number} Calculated percentage
 */
function calculatePercentage(value, max) {
    if (max === 0) return 0;
    return Math.round((value / max) * 100);
}

/**
 * Get color class based on skill level
 * @param {string} level - Skill level (Beginner, Intermediate, Advanced, Expert)
 * @returns {string} Tailwind CSS color class
 */
function getLevelColorClass(level) {
    const colors = {
        'Beginner': 'blue',
        'Intermediate': 'green',
        'Advanced': 'purple',
        'Expert': 'red'
    };
    
    return colors[level] || 'gray';
}

/**
 * Print current page or element
 * @param {string} elementId - Optional element ID to print (prints page if not provided)
 */
function printContent(elementId = null) {
    if (elementId) {
        const content = document.getElementById(elementId);
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write('<html><head><title>Print</title>');
        printWindow.document.write('<link rel="stylesheet" href="https://cdn.tailwindcss.com">');
        printWindow.document.write('</head><body>');
        printWindow.document.write(content.innerHTML);
        printWindow.document.write('</body></html>');
        
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    } else {
        window.print();
    }
}
