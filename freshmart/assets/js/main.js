// Main JavaScript for FreshMart Pro
document.addEventListener('DOMContentLoaded', function() {
    initializeCartSystem();
    initializeSearch();
    initializeForms();
    initializeCustomModals();
});

// Cart Management System
function initializeCartSystem() {
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const productPrice = this.dataset.productPrice;
            
            addToCart(productId, productName, productPrice, 1);
        });
    });
    
    // Cart count update
    updateCartCount();
}

function addToCart(productId, productName, productPrice, quantity = 1) {
    const formData = new FormData();
    formData.append('add_to_cart', 'true');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        showToast(`${productName} added to cart!`, 'success');
        updateCartCount();
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showToast('Error adding product to cart', 'error');
    });
}

function updateCartCount() {
    fetch('includes/cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartBadges = document.querySelectorAll('.cart-badge');
            cartBadges.forEach(badge => {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            });
        })
        .catch(error => console.error('Error updating cart count:', error));
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            performSearch(this.value);
        }, 300));
    }
}

function performSearch(query) {
    if (query.length < 2) return;
    
    // This would typically make an AJAX request to search endpoint
    console.log('Searching for:', query);
}

// Form handling
function initializeForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                
                // Re-enable button after 3 seconds in case of error
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = submitButton.innerHTML.replace('<i class="fas fa-spinner fa-spin mr-2"></i>', '');
                }, 3000);
            }
        });
    });
}

// Custom Modal System
function initializeCustomModals() {
    // Create modal container if not exists
    if (!document.getElementById('customModal')) {
        const modalHTML = `
            <div id="customModal" class="modal-overlay hidden">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3 id="modalTitle">Confirmation</h3>
                        <button class="modal-close" onclick="closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p id="modalMessage">Are you sure?</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
                        <button class="btn btn-danger" id="modalConfirm">Confirm</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}

function showConfirmModal(title, message, confirmCallback) {
    const modal = document.getElementById('customModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalConfirm = document.getElementById('modalConfirm');
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Remove existing event listeners
    const newConfirm = modalConfirm.cloneNode(true);
    modalConfirm.parentNode.replaceChild(newConfirm, modalConfirm);
    
    // Add new event listener
    newConfirm.addEventListener('click', function() {
        confirmCallback();
        closeModal();
    });
    
    modal.classList.remove('hidden');
}

function closeModal() {
    const modal = document.getElementById('customModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('customModal');
    if (e.target === modal) {
        closeModal();
    }
});

// Toast Notification System
function showToast(message, type = 'info') {
    // Create toast container if not exists
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icons[type] || icons.info}"></i>
        </div>
        <div class="toast-content">
            <div class="font-medium">${message}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Animate in
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 300);
    }, 5000);
}

// Utility functions
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

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// API functions
class FreshMartAPI {
    static async get(endpoint) {
        try {
            const response = await fetch(endpoint);
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    static async post(endpoint, data) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
}

// Export functions for global use
window.showToast = showToast;
window.showConfirmModal = showConfirmModal;
window.closeModal = closeModal;
window.formatCurrency = formatCurrency;