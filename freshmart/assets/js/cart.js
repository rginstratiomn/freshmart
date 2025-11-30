// Cart specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeCartInteractions();
});

function initializeCartInteractions() {
    // Quantity controls dengan fungsi hapus otomatis
    document.addEventListener('click', function(e) {
        // Decrease quantity
        if (e.target.closest('.decrease-quantity')) {
            const button = e.target.closest('.decrease-quantity');
            const productId = button.dataset.productId;
            const input = document.querySelector(`input[name="quantities[${productId}]"]`);
            
            if (input && parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updateCartItemTotal(productId);
            } else if (input && parseInt(input.value) === 1) {
                // Show custom confirmation modal for removal
                showConfirmModal(
                    'Remove Item',
                    'Are you sure you want to remove this item from your cart?',
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
            const maxStock = parseInt(input.max);
            
            if (input && parseInt(input.value) < maxStock) {
                input.value = parseInt(input.value) + 1;
                updateCartItemTotal(productId);
            } else {
                showToast('Maximum stock reached', 'warning');
            }
        }
    });
    
    // Real-time quantity input updates
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.name.match(/\[(\d+)\]/)[1];
            const quantity = parseInt(this.value);
            
            if (quantity <= 0) {
                showConfirmModal(
                    'Remove Item',
                    'Are you sure you want to remove this item from your cart?',
                    function() {
                        window.location.href = `cart.php?remove=${productId}`;
                    }
                );
            } else {
                updateCartItemTotal(productId);
            }
        });
    });
    
    // Custom confirmation for clear cart and remove links
    const clearCartLinks = document.querySelectorAll('a[href*="clear=1"]');
    clearCartLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showConfirmModal(
                'Clear Cart',
                'Are you sure you want to clear your entire cart? This action cannot be undone.',
                function() {
                    window.location.href = e.target.closest('a').href;
                }
            );
        });
    });
    
    const removeLinks = document.querySelectorAll('a[href*="remove="]');
    removeLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = new URL(this.href).searchParams.get('remove');
            showConfirmModal(
                'Remove Item',
                'Are you sure you want to remove this item from your cart?',
                function() {
                    window.location.href = e.target.closest('a').href;
                }
            );
        });
    });
}

function updateCartItemTotal(productId) {
    const input = document.querySelector(`input[name="quantities[${productId}]"]`);
    const priceElement = document.querySelector(`[data-product-id="${productId}"] .product-price`);
    
    if (input && priceElement) {
        const quantity = parseInt(input.value);
        const price = parseFloat(priceElement.textContent.replace(/[^\d]/g, ''));
        const total = quantity * price;
        
        // Update the displayed total
        const totalElement = input.closest('.flex.items-center').querySelector('.text-lg');
        if (totalElement) {
            totalElement.textContent = formatCurrency(total);
        }
    }
}