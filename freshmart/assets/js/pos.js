// POS System JavaScript
class POSSystem {
    constructor() {
        this.cart = [];
        this.init();
    }
    
    init() {
        this.initializeProductGrid();
        this.initializeCartHandlers();
        this.initializePaymentHandlers();
        this.updateCartDisplay();
    }
    
    initializeProductGrid() {
        const productItems = document.querySelectorAll('.pos-product');
        
        productItems.forEach(item => {
            item.addEventListener('click', () => {
                this.addToCart({
                    id: item.dataset.productId,
                    name: item.dataset.productName,
                    price: parseFloat(item.dataset.productPrice),
                    stock: parseInt(item.dataset.productStock)
                });
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('productSearch');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.filterProducts(searchInput.value);
            }, 300));
        }
    }
    
    initializeCartHandlers() {
        // Clear cart button
        const clearCartBtn = document.getElementById('clearCart');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', () => {
                if (this.cart.length > 0) {
                    if (confirm('Are you sure you want to clear the cart?')) {
                        this.clearCart();
                    }
                }
            });
        }
    }
    
    initializePaymentHandlers() {
        // Payment amount calculation
        const paymentAmountInput = document.getElementById('paymentAmount');
        const paymentMethodSelect = document.getElementById('paymentMethod');
        
        if (paymentAmountInput) {
            paymentAmountInput.addEventListener('input', () => {
                this.calculateChange();
            });
        }
        
        if (paymentMethodSelect) {
            paymentMethodSelect.addEventListener('change', () => {
                this.togglePaymentFields();
            });
        }
        
        // Discount calculation
        const discountInput = document.getElementById('discountAmount');
        if (discountInput) {
            discountInput.addEventListener('input', () => {
                this.calculateTotals();
            });
        }
    }
    
    addToCart(product) {
        const existingItem = this.cart.find(item => item.id === product.id);
        
        if (existingItem) {
            if (existingItem.quantity < product.stock) {
                existingItem.quantity++;
            } else {
                this.showMessage('Maximum stock reached for ' + product.name, 'warning');
                return;
            }
        } else {
            this.cart.push({
                ...product,
                quantity: 1
            });
        }
        
        this.updateCartDisplay();
        this.showMessage(product.name + ' added to cart', 'success');
    }
    
    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.updateCartDisplay();
    }
    
    updateCartItemQuantity(productId, newQuantity) {
        const item = this.cart.find(item => item.id === productId);
        if (item) {
            if (newQuantity <= 0) {
                this.removeFromCart(productId);
            } else if (newQuantity <= item.stock) {
                item.quantity = newQuantity;
                this.updateCartDisplay();
            } else {
                this.showMessage('Cannot exceed available stock', 'warning');
            }
        }
    }
    
    clearCart() {
        this.cart = [];
        this.updateCartDisplay();
        this.showMessage('Cart cleared', 'info');
    }
    
    updateCartDisplay() {
        const cartContainer = document.getElementById('posCart');
        const emptyCart = document.getElementById('emptyCart');
        const processPaymentBtn = document.getElementById('processPayment');
        
        if (this.cart.length === 0) {
            if (cartContainer) cartContainer.innerHTML = '';
            if (emptyCart) emptyCart.style.display = 'block';
            if (processPaymentBtn) processPaymentBtn.disabled = true;
        } else {
            if (emptyCart) emptyCart.style.display = 'none';
            if (processPaymentBtn) processPaymentBtn.disabled = false;
            
            this.renderCartItems();
        }
        
        this.calculateTotals();
        this.updateCartForm();
    }
    
    renderCartItems() {
        const cartContainer = document.getElementById('posCart');
        if (!cartContainer) return;
        
        cartContainer.innerHTML = this.cart.map(item => `
            <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                <div class="flex-1">
                    <h4 class="font-semibold text-sm">${item.name}</h4>
                    <p class="text-green-600 font-bold">${this.formatCurrency(item.price)}</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <div class="flex items-center border border-gray-300 rounded">
                        <button type="button" 
                                class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 decrease-qty"
                                data-product-id="${item.id}">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <input type="number" 
                               value="${item.quantity}" 
                               min="1" 
                               max="${item.stock}"
                               class="w-12 text-center border-0 py-1 quantity-input"
                               data-product-id="${item.id}">
                        <button type="button" 
                                class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 increase-qty"
                                data-product-id="${item.id}">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                    
                    <div class="w-20 text-right font-semibold">
                        ${this.formatCurrency(item.price * item.quantity)}
                    </div>
                    
                    <button type="button" 
                            class="text-red-500 hover:text-red-700 remove-item"
                            data-product-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
        
        // Add event listeners to dynamic elements
        this.attachCartItemEvents();
    }
    
    attachCartItemEvents() {
        // Quantity decrease buttons
        document.querySelectorAll('.decrease-qty').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.closest('button').dataset.productId;
                const item = this.cart.find(item => item.id === productId);
                if (item) {
                    this.updateCartItemQuantity(productId, item.quantity - 1);
                }
            });
        });
        
        // Quantity increase buttons
        document.querySelectorAll('.increase-qty').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.closest('button').dataset.productId;
                const item = this.cart.find(item => item.id === productId);
                if (item) {
                    this.updateCartItemQuantity(productId, item.quantity + 1);
                }
            });
        });
        
        // Quantity inputs
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const productId = e.target.dataset.productId;
                const newQuantity = parseInt(e.target.value);
                this.updateCartItemQuantity(productId, newQuantity);
            });
        });
        
        // Remove buttons
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.closest('button').dataset.productId;
                this.removeFromCart(productId);
            });
        });
    }
    
    calculateTotals() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discount = parseFloat(document.getElementById('discountAmount')?.value || 0);
        const tax = subtotal * 0.1; // 10% tax
        const grandTotal = subtotal - discount + tax;
        
        // Update display
        const subtotalElement = document.getElementById('subtotal');
        const taxElement = document.getElementById('taxAmount');
        const grandTotalElement = document.getElementById('grandTotal');
        
        if (subtotalElement) subtotalElement.textContent = this.formatCurrency(subtotal);
        if (taxElement) taxElement.textContent = this.formatCurrency(tax);
        if (grandTotalElement) grandTotalElement.textContent = this.formatCurrency(grandTotal);
        
        return { subtotal, discount, tax, grandTotal };
    }
    
    calculateChange() {
        const paymentAmount = parseFloat(document.getElementById('paymentAmount')?.value || 0);
        const totals = this.calculateTotals();
        const change = paymentAmount - totals.grandTotal;
        
        const changeElement = document.getElementById('changeAmount');
        const changeValue = document.getElementById('changeValue');
        
        if (changeElement && changeValue) {
            if (change >= 0) {
                changeElement.classList.remove('hidden');
                changeValue.textContent = this.formatCurrency(change);
            } else {
                changeElement.classList.add('hidden');
            }
        }
    }
    
    togglePaymentFields() {
        const paymentMethod = document.getElementById('paymentMethod')?.value;
        const paymentAmountInput = document.getElementById('paymentAmount');
        
        if (paymentAmountInput) {
            if (paymentMethod === 'cash') {
                paymentAmountInput.placeholder = 'Enter cash amount';
            } else {
                paymentAmountInput.placeholder = 'Enter payment amount';
            }
        }
    }
    
    updateCartForm() {
        // Update hidden form fields with cart data
        const form = document.querySelector('form');
        if (!form) return;
        
        // Remove existing cart item inputs
        const existingInputs = form.querySelectorAll('input[name^="cart_items"]');
        existingInputs.forEach(input => input.remove());
        
        // Add new cart item inputs
        this.cart.forEach((item, index) => {
            const inputs = [
                { name: `cart_items[${index}][product_id]`, value: item.id },
                { name: `cart_items[${index}][product_name]`, value: item.name },
                { name: `cart_items[${index}][price]`, value: item.price },
                { name: `cart_items[${index}][quantity]`, value: item.quantity }
            ];
            
            inputs.forEach(({ name, value }) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
        });
    }
    
    filterProducts(searchTerm) {
        const productItems = document.querySelectorAll('.pos-product');
        const searchLower = searchTerm.toLowerCase();
        
        productItems.forEach(item => {
            const productName = item.dataset.productName.toLowerCase();
            if (productName.includes(searchLower)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    showMessage(message, type = 'info') {
        // Simple message display - you might want to use a toast library
        console.log(`${type.toUpperCase()}: ${message}`);
    }
    
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
    
    debounce(func, wait) {
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
}

// Initialize POS system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.posSystem = new POSSystem();
});