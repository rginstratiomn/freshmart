<?php
require_once 'includes/functions.php';

// Set timezone untuk konsistensi
date_default_timezone_set('Asia/Jakarta');

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        $_SESSION['success'] = "Product added to cart!";
        redirect('cart.php');
        exit;
    }
    
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
        }
        $_SESSION['success'] = "Cart updated successfully!";
        redirect('cart.php');
        exit;
    }
    
    if (isset($_POST['apply_voucher'])) {
        $voucher_code = sanitize($_POST['voucher_code']);
        
        // Validasi voucher
        if (empty($voucher_code)) {
            $_SESSION['error'] = "Please enter a voucher code";
            redirect('cart.php');
            exit;
        }
        
        $db = new Database();
        $connection = $db->getConnection();
        
        try {
            // Check voucher validity
            $voucher_query = "SELECT * FROM vouchers WHERE code = ? AND is_active = 1";
            $voucher_stmt = $connection->prepare($voucher_query);
            $voucher_stmt->execute([$voucher_code]);
            $voucher = $voucher_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$voucher) {
                $_SESSION['error'] = "Voucher code not found or inactive";
                redirect('cart.php');
                exit;
            }
            
            // Check date validity (FIXED - proper date comparison)
            $now = date('Y-m-d H:i:s');
            if ($now < $voucher['start_date'] || $now > $voucher['end_date']) {
                $_SESSION['error'] = "Voucher has expired or not yet valid";
                redirect('cart.php');
                exit;
            }
            
            // Check usage limit
            if ($voucher['usage_limit'] && $voucher['usage_count'] >= $voucher['usage_limit']) {
                $_SESSION['error'] = "Voucher usage limit reached";
                redirect('cart.php');
                exit;
            }
            
            // Check if customer has already used this voucher
            if (isLoggedIn() && isset($_SESSION['customer_id'])) {
                $usage_query = "SELECT COUNT(*) as used_count FROM voucher_usage 
                               WHERE voucher_id = ? AND customer_id = ?";
                $usage_stmt = $connection->prepare($usage_query);
                $usage_stmt->execute([$voucher['id'], $_SESSION['customer_id']]);
                $usage_result = $usage_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usage_result && $usage_result['used_count'] >= $voucher['usage_per_customer']) {
                    $_SESSION['error'] = "You have already used this voucher";
                    redirect('cart.php');
                    exit;
                }
            }
            
            // Check minimum purchase
            $subtotal = 0;
            if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                $product_ids = array_keys($_SESSION['cart']);
                
                // Calculate subtotal
                foreach ($product_ids as $product_id) {
                    $quantity = $_SESSION['cart'][$product_id];
                    $product_query = "SELECT harga_jual FROM products WHERE id = ?";
                    $product_stmt = $connection->prepare($product_query);
                    $product_stmt->execute([$product_id]);
                    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($product) {
                        $subtotal += $product['harga_jual'] * $quantity;
                    }
                }
            }
            
            if ($subtotal < $voucher['min_purchase']) {
                $min_purchase = formatRupiah($voucher['min_purchase']);
                $_SESSION['error'] = "Minimum purchase of $min_purchase required for this voucher";
                redirect('cart.php');
                exit;
            }
            
            // Voucher valid - store in session
            $_SESSION['voucher'] = $voucher;
            $_SESSION['success'] = "Voucher '{$voucher['code']}' applied successfully!";
            
        } catch (PDOException $e) {
            error_log("Voucher validation error: " . $e->getMessage());
            $_SESSION['error'] = "Error validating voucher. Please try again.";
        }
        
        redirect('cart.php');
        exit;
    }
    
    if (isset($_POST['remove_voucher'])) {
        unset($_SESSION['voucher']);
        $_SESSION['success'] = "Voucher removed successfully!";
        redirect('cart.php');
        exit;
    }
}

// Remove item from cart
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success'] = "Product removed from cart!";
    }
    redirect('cart.php');
    exit;
}

// Clear cart
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    unset($_SESSION['voucher']);
    $_SESSION['success'] = "Cart cleared!";
    redirect('cart.php');
    exit;
}

// Get cart products with details
$cart_products = [];
$subtotal = 0;
$total_items = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $db = new Database();
    $connection = $db->getConnection();
    
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $query = "SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'";
    $stmt = $connection->prepare($query);
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $product_total = $product['harga_jual'] * $quantity;
        
        $cart_products[] = [
            'id' => $product['id'],
            'nama_produk' => $product['nama_produk'],
            'foto_utama' => $product['foto_utama'],
            'harga_jual' => $product['harga_jual'],
            'stok' => $product['stok'],
            'quantity' => $quantity,
            'total' => $product_total
        ];
        
        $subtotal += $product_total;
        $total_items += $quantity;
    }
}

// Calculate totals
$shipping_cost = 15000; // Default shipping cost
$tax_amount = $subtotal * 0.1; // 10% tax
$voucher_discount = 0;

// Calculate voucher discount if applied
if (isset($_SESSION['voucher'])) {
    $voucher = $_SESSION['voucher'];
    
    if ($voucher['discount_type'] === 'percentage') {
        $voucher_discount = $subtotal * ($voucher['discount_value'] / 100);
        // Apply max discount if set
        if ($voucher['max_discount'] && $voucher_discount > $voucher['max_discount']) {
            $voucher_discount = $voucher['max_discount'];
        }
    } else {
        $voucher_discount = $voucher['discount_value'];
    }
    
    // Ensure discount doesn't exceed subtotal
    if ($voucher_discount > $subtotal) {
        $voucher_discount = $subtotal;
    }
}

$grand_total = $subtotal + $shipping_cost + $tax_amount - $voucher_discount;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - FreshMart Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Shopping Cart</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success mb-6">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error mb-6">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cart_products)): ?>
            <div class="card text-center py-12">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-600 mb-4">Your cart is empty</h2>
                <p class="text-gray-500 mb-6">Start shopping to add items to your cart</p>
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Cart Items (<?php echo $total_items; ?> items)</h2>
                        </div>
                        
                        <form method="POST" id="cartForm">
                            <div class="divide-y divide-gray-200">
                                <?php foreach ($cart_products as $item): ?>
                                <div class="p-6 flex items-center space-x-4" data-product-id="<?php echo $item['id']; ?>">
                                    <img src="<?php echo $item['foto_utama'] ? 'uploads/' . $item['foto_utama'] : 'https://via.placeholder.com/80x80/3B82F6/FFFFFF?text=Product'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['nama_produk']); ?>" 
                                         class="w-20 h-20 object-cover rounded-lg"
                                         onerror="this.src='https://via.placeholder.com/80x80/3B82F6/FFFFFF?text=Product'">
                                    
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($item['nama_produk']); ?></h3>
                                        <p class="text-gray-600 product-price" data-price="<?php echo $item['harga_jual']; ?>">
                                            <?php echo formatRupiah($item['harga_jual']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">Stock: <?php echo $item['stok']; ?></p>
                                    </div>
                                    
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center border border-gray-300 rounded-lg">
                                            <button type="button" 
                                                    class="decrease-quantity w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition"
                                                    data-product-id="<?php echo $item['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($item['nama_produk']); ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   name="quantities[<?php echo $item['id']; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item['stok']; ?>"
                                                   class="quantity-input w-16 text-center border-0 py-2 focus:outline-none"
                                                   data-product-id="<?php echo $item['id']; ?>"
                                                   readonly>
                                            <button type="button" 
                                                    class="increase-quantity w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition"
                                                    data-product-id="<?php echo $item['id']; ?>"
                                                    data-max-stock="<?php echo $item['stok']; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="text-lg font-semibold text-gray-800 w-32 text-right item-total" data-product-id="<?php echo $item['id']; ?>">
                                            <?php echo formatRupiah($item['total']); ?>
                                        </div>
                                        
                                        <a href="#" 
                                           class="text-red-500 hover:text-red-700 p-2 remove-item transition"
                                           data-product-id="<?php echo $item['id']; ?>"
                                           data-product-name="<?php echo htmlspecialchars($item['nama_produk']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="p-6 border-t border-gray-200 flex justify-between items-center">
                                <a href="products.php" class="btn btn-outline">
                                    <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                                </a>
                                
                                <div class="flex space-x-4">
                                    <a href="#" class="btn btn-danger clear-cart">
                                        <i class="fas fa-trash mr-2"></i>Clear Cart
                                    </a>
                                    
                                    <button type="submit" name="update_cart" class="btn btn-primary" id="updateCartBtn">
                                        <span class="btn-text">
                                            <i class="fas fa-sync mr-2"></i>Update Cart
                                        </span>
                                        <span class="btn-spinner hidden">
                                            <i class="fas fa-spinner fa-spin mr-2"></i>Updating...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="card sticky top-4">
                        <div class="card-header">
                            <h2 class="card-title">Order Summary</h2>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Voucher Code -->
                            <?php if (isset($_SESSION['voucher'])): ?>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-green-800">Voucher Applied</span>
                                        <form method="POST" class="inline">
                                            <button type="submit" name="remove_voucher" class="text-red-500 hover:text-red-700 transition">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <p class="text-green-600 text-sm">
                                        <?php echo $_SESSION['voucher']['code']; ?> - 
                                        <?php echo $_SESSION['voucher']['discount_type'] === 'percentage' ? 
                                              $_SESSION['voucher']['discount_value'] . '% off' : 
                                              formatRupiah($_SESSION['voucher']['discount_value']) . ' off'; ?>
                                    </p>
                                    <?php if ($_SESSION['voucher']['discount_type'] === 'percentage' && $_SESSION['voucher']['max_discount']): ?>
                                        <p class="text-green-500 text-xs">Max discount: <?php echo formatRupiah($_SESSION['voucher']['max_discount']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="space-y-3" id="voucherForm">
                                    <div class="form-group">
                                        <label class="form-label">Voucher Code</label>
                                        <div class="flex space-x-2">
                                            <input type="text" 
                                                   name="voucher_code" 
                                                   id="voucherCodeInput"
                                                   class="form-input flex-1" 
                                                   placeholder="Enter voucher code">
                                            <button type="submit" name="apply_voucher" class="btn btn-outline" id="applyVoucherBtn">
                                                <span class="btn-text">Apply</span>
                                                <span class="btn-spinner hidden">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </span>
                                            </button>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Try: <strong>WELCOME10</strong> (10% off, min Rp 100.000) or <strong>FREESHIP</strong> (Free shipping)
                                        </p>
                                    </div>
                                </form>
                            <?php endif; ?>
                            
                            <!-- Price Breakdown -->
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal (<span id="totalItemsCount"><?php echo $total_items; ?></span> items)</span>
                                    <span class="font-semibold" id="subtotalAmount"><?php echo formatRupiah($subtotal); ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping</span>
                                    <span class="font-semibold" id="shippingAmount"><?php echo formatRupiah($shipping_cost); ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax (10%)</span>
                                    <span class="font-semibold" id="taxAmount"><?php echo formatRupiah($tax_amount); ?></span>
                                </div>
                                
                                <?php if ($voucher_discount > 0): ?>
                                <div class="flex justify-between text-green-600" id="voucherDiscountRow">
                                    <span>Voucher Discount</span>
                                    <span class="font-semibold" id="voucherDiscountAmount">-<?php echo formatRupiah($voucher_discount); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="border-t border-gray-200 pt-3 flex justify-between text-lg font-bold">
                                    <span>Total</span>
                                    <span class="text-green-600" id="grandTotalAmount"><?php echo formatRupiah($grand_total); ?></span>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <?php if (isLoggedIn()): ?>
                                <a href="checkout.php" class="btn btn-primary w-full btn-lg">
                                    <i class="fas fa-lock mr-2"></i>Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <a href="auth/login.php?redirect=checkout.php" class="btn btn-primary w-full btn-lg">
                                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Checkout
                                </a>
                            <?php endif; ?>
                            
                            <!-- Security Notice -->
                            <div class="text-center text-sm text-gray-500 mt-4">
                                <i class="fas fa-lock text-green-500 mr-1"></i>
                                Secure checkout guaranteed
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Load main.js untuk custom modal -->
    <script src="assets/js/main.js"></script>
    <script>
        // REAL-TIME CART UPDATE & MODAL CONFIRMATIONS
        document.addEventListener('DOMContentLoaded', function() {
            // Cart data untuk perhitungan
            const cartData = {
                shipping: <?php echo $shipping_cost; ?>,
                taxRate: 0.1
            };
            
            // Format currency helper
            function formatRupiah(amount) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(amount));
            }
            
            // Calculate and update order summary
            function updateOrderSummary() {
                let subtotal = 0;
                let totalItems = 0;
                
                // Calculate from all cart items
                document.querySelectorAll('[data-product-id]').forEach(row => {
                    const productId = row.dataset.productId;
                    const input = row.querySelector('.quantity-input');
                    const priceElement = row.querySelector('.product-price');
                    
                    if (input && priceElement) {
                        const quantity = parseInt(input.value) || 0;
                        const price = parseFloat(priceElement.dataset.price) || 0;
                        const itemTotal = price * quantity;
                        
                        // Update item total
                        const totalElement = row.querySelector(`.item-total[data-product-id="${productId}"]`);
                        if (totalElement) {
                            totalElement.textContent = formatRupiah(itemTotal);
                        }
                        
                        subtotal += itemTotal;
                        totalItems += quantity;
                    }
                });
                
                // Update summary
                document.getElementById('totalItemsCount').textContent = totalItems;
                document.getElementById('subtotalAmount').textContent = formatRupiah(subtotal);
                
                const tax = subtotal * cartData.taxRate;
                document.getElementById('taxAmount').textContent = formatRupiah(tax);
                
                const grandTotal = subtotal + cartData.shipping + tax;
                document.getElementById('grandTotalAmount').textContent = formatRupiah(grandTotal);
            }
            
            // DECREASE QUANTITY
            document.addEventListener('click', function(e) {
                if (e.target.closest('.decrease-quantity')) {
                    const button = e.target.closest('.decrease-quantity');
                    const productId = button.dataset.productId;
                    const productName = button.dataset.productName;
                    const input = document.querySelector(`input[name="quantities[${productId}]"]`);
                    
                    if (!input) return;
                    
                    const currentQuantity = parseInt(input.value);
                    
                    if (currentQuantity > 1) {
                        input.value = currentQuantity - 1;
                        updateOrderSummary();
                    } else if (currentQuantity === 1) {
                        showConfirmModal(
                            'Remove Item',
                            `Remove <strong>"${productName}"</strong> from cart?`,
                            function() {
                                window.location.href = `cart.php?remove=${productId}`;
                            }
                        );
                    }
                }
            });
            
            // INCREASE QUANTITY
            document.addEventListener('click', function(e) {
                if (e.target.closest('.increase-quantity')) {
                    const button = e.target.closest('.increase-quantity');
                    const productId = button.dataset.productId;
                    const maxStock = parseInt(button.dataset.maxStock);
                    const input = document.querySelector(`input[name="quantities[${productId}]"]`);
                    
                    if (!input) return;
                    
                    const currentQuantity = parseInt(input.value);
                    
                    if (currentQuantity < maxStock) {
                        input.value = currentQuantity + 1;
                        updateOrderSummary();
                    } else {
                        showToast('Maximum stock reached', 'warning');
                    }
                }
            });
            
            // REMOVE ITEM
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-item')) {
                    e.preventDefault();
                    const link = e.target.closest('.remove-item');
                    const productId = link.dataset.productId;
                    const productName = link.dataset.productName;
                    
                    showConfirmModal(
                        'Remove Item',
                        `Remove <strong>"${productName}"</strong> from cart?`,
                        function() {
                            window.location.href = `cart.php?remove=${productId}`;
                        }
                    );
                }
            });
            
            // CLEAR CART
            document.addEventListener('click', function(e) {
                if (e.target.closest('.clear-cart')) {
                    e.preventDefault();
                    showConfirmModal(
                        'Clear Cart',
                        'Remove all items from cart?<br><strong>This cannot be undone.</strong>',
                        function() {
                            window.location.href = 'cart.php?clear=1';
                        }
                    );
                }
            });
            
            // UPDATE CART BUTTON LOADING
            const updateCartBtn = document.getElementById('updateCartBtn');
            if (updateCartBtn) {
                document.getElementById('cartForm').addEventListener('submit', function() {
                    updateCartBtn.disabled = true;
                    updateCartBtn.querySelector('.btn-text').classList.add('hidden');
                    updateCartBtn.querySelector('.btn-spinner').classList.remove('hidden');
                });
            }
            
            // APPLY VOUCHER BUTTON LOADING
            const applyVoucherBtn = document.getElementById('applyVoucherBtn');
            if (applyVoucherBtn) {
                document.getElementById('voucherForm').addEventListener('submit', function(e) {
                    const input = document.getElementById('voucherCodeInput');
                    if (!input.value.trim()) {
                        e.preventDefault();
                        showToast('Please enter a voucher code', 'warning');
                        return;
                    }
                    
                    applyVoucherBtn.disabled = true;
                    applyVoucherBtn.querySelector('.btn-text').classList.add('hidden');
                    applyVoucherBtn.querySelector('.btn-spinner').classList.remove('hidden');
                });
            }
        });
    </script>
</body>
</html>