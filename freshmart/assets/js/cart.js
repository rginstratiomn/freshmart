// Cart specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeCartInteractions();
});

function initializeCartInteractions() {
    // Pastikan fungsi showConfirmModal tersedia
    if (typeof showConfirmModal === 'undefined') {
        console.error('showConfirmModal is not defined. Make sure main.js is loaded first.');
        return;
    }

    // Quantity controls dengan fungsi hapus otomatis
    document.addEventListener('click', function(e) {
        // Decrease quantity
        if (e.target.closest('.decrease-quantity')) {
            const button = e.target.closest('.decrease-quantity');
            const productId = button.dataset.productId;
            const productName = button.dataset.productName;
            const input = document.querySelector(`input[name="quantities[${productId}]"]`);
            
            if (!input) return;
            
            const currentQuantity = parseInt(input.value);
            
            if (currentQuantity > 1) {
                // Decrease quantity normally
                input.value = currentQuantity - 1;
                updateCartItemTotal(productId);
            } else if (currentQuantity === 1) {
                // Show custom confirmation modal for removal
                showConfirmModal(
                    'Remove Item',
                    `Are you sure you want to remove "${productName}" from your cart?`,
                    function() {
                        // Remove the item
                        window.location.href = `cart.php?remove=${productId}`;
                    }
                );
            }
        }
        
        // Increase quantity
        if (e.target.closest('.increase-quantity')) {
            const button = e.target.closest('.increase-quantity');
            const productId = button.dataset.productId;
            const input = document.querySelector(`input[name="quantities[${productId}]"]`);
            
            if (!input) return;
            
            const maxStock = parseInt(input.max);
            const currentQuantity = parseInt(input.value);
            
            if (currentQuantity < maxStock) {
                input.value = currentQuantity + 1;
                updateCartItemTotal(productId);
            } else {
                showToast('Maximum stock reached', 'warning');
            }
        }
        
        // Remove single item
        if (e.target.closest('.remove-item')) {
            e.preventDefault();
            const link = e.target.closest('.remove-item');
            const productId = link.dataset.productId;
            const productName = link.dataset.productName;
            
            showConfirmModal(
                'Remove Item',
                `Are you sure you want to remove "${productName}" from your cart?`,
                function() {
                    window.location.href = `cart.php?remove=${productId}`;
                }
            );
        }
        
        // Clear entire cart
        if (e.target.closest('.clear-cart')) {
            e.preventDefault();
            showConfirmModal(
                'Clear Cart',
                'Are you sure you want to clear your entire cart? This action cannot be undone.',
                function() {
                    window.location.href = 'cart.php?clear=1';
                }
            );
        }
    });
    
    // Real-time quantity input updates
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.name.match(/\[(\d+)\]/)[1];
            const quantity = parseInt(this.value);
            
            if (quantity <= 0) {
                const productName = this.closest('.flex.items-center').querySelector('h3').textContent;
                showConfirmModal(
                    'Remove Item',
                    `Are you sure you want to remove "${productName}" from your cart?`,
                    function() {
                        window.location.href = `cart.php?remove=${productId}`;
                    }
                );
            } else {
                updateCartItemTotal(productId);
            }
        });
    });
}

function updateCartItemTotal(productId) {
    const input = document.querySelector(`input[name="quantities[${productId}]"]`);
    if (!input) return;
    
    const container = input.closest('.flex.items-center');
    if (!container) return;
    
    const priceElement = container.querySelector('.text-gray-600');
    const totalElement = container.querySelector('.text-lg.font-semibold');
    
    if (priceElement && totalElement) {
        const quantity = parseInt(input.value);
        const priceText = priceElement.textContent;
        // Extract price from formatted currency (e.g., "Rp 25.000" -> 25000)
        const price = parseFloat(priceText.replace(/[^\d]/g, ''));
        const total = quantity * price;
        
        // Update the displayed total
        totalElement.textContent = formatCurrency(total);
    }
}