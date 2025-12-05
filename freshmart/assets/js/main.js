/**
 * Main JavaScript File for FreshMart Pro
 * Handles UI interactions, modals, notifications, and cart functionality
 */

// DOM Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all interactive components
    initMobileMenu();
    initDropdowns();
    initModals();
    initForms();
    initNotifications();
    
    // Add animation classes
    addAnimationClasses();
});

// Mobile Menu Toggle
function initMobileMenu() {
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            // Toggle icon
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.replace('fa-bars', 'fa-times');
            } else {
                icon.classList.replace('fa-times', 'fa-bars');
            }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
                mobileMenu.classList.add('hidden');
                const icon = mobileMenuButton.querySelector('i');
                if (icon.classList.contains('fa-times')) {
                    icon.classList.replace('fa-times', 'fa-bars');
                }
            }
        });
    }
}

// Dropdown Menus
function initDropdowns() {
    const dropdownButtons = document.querySelectorAll('.dropdown-button');
    
    dropdownButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.nextElementSibling;
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.add('hidden');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
        });
    });
}

// Modal Functions
function initModals() {
    // Open modal buttons
    const modalButtons = document.querySelectorAll('[data-modal-toggle]');
    
    modalButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal-toggle');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            }
        });
    });
    
    // Close modal buttons
    const closeButtons = document.querySelectorAll('[data-modal-hide]');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal-hide');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto'; // Restore scrolling
            }
        });
    });
    
    // Close modal when clicking outside
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modals.forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            });
        }
    });
}

// Form Validation and Handling
function initForms() {
    // Password toggle visibility
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
    
    // File input preview
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);
            const file = this.files[0];
            
            if (file && preview) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Form submission with loading state
    const forms = document.querySelectorAll('form[data-loading]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('[type="submit"]');
            if (submitButton) {
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = `
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Processing...
                `;
                submitButton.disabled = true;
                
                // Restore button after 5 seconds (in case of error)
                setTimeout(() => {
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }, 5000);
            }
        });
    });
}

// Notification System
function initNotifications() {
    // Auto-hide alerts after 5 seconds
    const autoHideAlerts = document.querySelectorAll('.alert[data-auto-hide]');
    
    autoHideAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Close alert buttons
    const closeButtons = document.querySelectorAll('.alert-close');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.alert');
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        });
    });
}

// Animation Classes
function addAnimationClasses() {
    // Add animation to cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe elements with animation class
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    animatedElements.forEach(element => {
        observer.observe(element);
    });
    
    // Add hover effects to interactive elements
    const interactiveElements = document.querySelectorAll('.btn, .card, .product-card');
    interactiveElements.forEach(element => {
        element.classList.add('transition-all', 'duration-200', 'ease-in-out');
    });
}

// Custom Confirm Modal with Icon (DEFAULT ICON: trash)
function showConfirmModal(title, message, confirmCallback, iconClass = 'fas fa-trash') {
    // Remove existing modal if any
    const existingModal = document.getElementById('customConfirmModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create modal HTML
    const modalHTML = `
        <div id="customConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full transform transition-all duration-300 scale-100 opacity-100">
                <div class="p-6 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <i class="${iconClass} text-red-600 text-xl"></i>
                    </div>
                    
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">${title}</h3>
                    <div class="text-gray-600 mb-6">${message}</div>
                    
                    <div class="flex space-x-3 justify-center">
                        <button type="button" 
                                class="btn btn-outline px-6 py-2 cancel-btn hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="button" 
                                class="btn btn-danger px-6 py-2 confirm-btn bg-red-600 hover:bg-red-700 text-white transition">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Get modal element
    const modal = document.getElementById('customConfirmModal');
    
    // Add event listener to confirm button
    document.querySelector('.confirm-btn').addEventListener('click', function() {
        modal.remove();
        if (typeof confirmCallback === 'function') {
            confirmCallback();
        }
    });
    
    // Add event listener to cancel button
    document.querySelector('.cancel-btn').addEventListener('click', function() {
        modal.remove();
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.remove();
        }
    });
    
    // Close modal with Escape key
    const closeWithEscape = function(e) {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', closeWithEscape);
        }
    };
    
    document.addEventListener('keydown', closeWithEscape);
    
    // Remove event listener when modal is removed
    modal.addEventListener('remove', function() {
        document.removeEventListener('keydown', closeWithEscape);
    });
    
    // Focus on confirm button for accessibility
    setTimeout(() => {
        document.querySelector('.cancel-btn').focus();
    }, 100);
}

// Toast Notification System
function showToast(message, type = 'success', duration = 2000) {
    // Remove existing toast if any
    const existingToast = document.getElementById('customToast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Type configuration
    const typeConfig = {
        success: { 
            bg: 'bg-green-500', 
            icon: 'fas fa-check-circle',
            border: 'border-green-200'
        },
        error: { 
            bg: 'bg-red-500', 
            icon: 'fas fa-exclamation-circle',
            border: 'border-red-200'
        },
        warning: { 
            bg: 'bg-yellow-500', 
            icon: 'fas fa-exclamation-triangle',
            border: 'border-yellow-200'
        },
        info: { 
            bg: 'bg-blue-500', 
            icon: 'fas fa-info-circle',
            border: 'border-blue-200'
        }
    };
    
    const config = typeConfig[type] || typeConfig.success;
    
    // Create toast HTML - positioned below navbar
    const toastHTML = `
        <div id="customToast" style="position: fixed; top: 80px; right: 16px; z-index: 9999;" class="${config.bg} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 border ${config.border} transform transition-all duration-300 translate-y-0 opacity-100 max-w-sm">
            <i class="${config.icon} text-lg"></i>
            <span class="flex-1">${message}</span>
            <button type="button" class="toast-close ml-2 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add toast to body
    document.body.insertAdjacentHTML('beforeend', toastHTML);
    
    // Get toast element
    const toast = document.getElementById('customToast');
    
    // Add close button event listener
    toast.querySelector('.toast-close').addEventListener('click', function() {
        hideToast(toast);
    });
    
    // Auto hide after specified duration
    let autoHide = setTimeout(() => {
        hideToast(toast);
    }, duration);
    
    // Function to hide toast with animation
    function hideToast(toastElement) {
        clearTimeout(autoHide);
        toastElement.style.transform = 'translateY(-20px)';
        toastElement.style.opacity = '0';
        setTimeout(() => {
            if (toastElement.parentNode) {
                toastElement.remove();
            }
        }, 300);
    }
    
    // Pause auto-hide on hover
    toast.addEventListener('mouseenter', () => {
        clearTimeout(autoHide);
    });
    
    toast.addEventListener('mouseleave', () => {
        autoHide = setTimeout(() => {
            hideToast(toast);
        }, duration);
    });
}

// Cart Functions
function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        // Animation effect
        element.classList.add('animate-ping');
        setTimeout(() => {
            element.classList.remove('animate-ping');
        }, 300);
        
        // Update count
        element.textContent = count;
        
        // Show/hide based on count
        if (count > 0) {
            element.classList.remove('hidden');
        } else {
            element.classList.add('hidden');
        }
    });
}

// Product Quantity Controls
function initQuantityControls() {
    document.addEventListener('click', function(e) {
        // Decrease quantity
        if (e.target.closest('.decrease-quantity')) {
            const button = e.target.closest('.decrease-quantity');
            const input = button.nextElementSibling;
            const min = parseInt(input.getAttribute('min')) || 1;
            const currentValue = parseInt(input.value) || min;
            
            if (currentValue > min) {
                input.value = currentValue - 1;
                triggerEvent(input, 'change');
            }
        }
        
        // Increase quantity
        if (e.target.closest('.increase-quantity')) {
            const button = e.target.closest('.increase-quantity');
            const input = button.previousElementSibling;
            const max = parseInt(input.getAttribute('max')) || 999;
            const currentValue = parseInt(input.value) || 1;
            
            if (currentValue < max) {
                input.value = currentValue + 1;
                triggerEvent(input, 'change');
            }
        }
    });
}

// Helper function to trigger events
function triggerEvent(element, eventName) {
    if (document.createEvent) {
        const event = document.createEvent('HTMLEvents');
        event.initEvent(eventName, false, true);
        element.dispatchEvent(event);
    } else {
        element.fireEvent('on' + eventName);
    }
}

// Price Formatting
function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(amount));
}

// Debounce Function for Performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for global use (if using modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        showConfirmModal,
        showToast,
        updateCartCount,
        formatRupiah
    };
}