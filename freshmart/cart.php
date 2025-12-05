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
        
        echo json_encode(['success' => true, 'message' => 'Product added to cart!']);
        exit;
    }
    
    if (isset($_POST['update_quantity'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if (isset($_POST['apply_voucher'])) {
        $voucher_id = (int)$_POST['voucher_id'];
        
        $db = new Database();
        $connection = $db->getConnection();
        
        try {
            // Get voucher details
            $voucher_query = "SELECT * FROM vouchers WHERE id = ? AND is_active = 1";
            $voucher_stmt = $connection->prepare($voucher_query);
            $voucher_stmt->execute([$voucher_id]);
            $voucher = $voucher_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$voucher) {
                $_SESSION['error'] = "Voucher not found or inactive";
                redirect('cart.php');
                exit;
            }
            
            // Check date validity
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

// Get available vouchers
$available_vouchers = [];
if (!empty($cart_products)) {
    $db = new Database();
    $connection = $db->getConnection();
    
    $now = date('Y-m-d H:i:s');
    $voucher_query = "SELECT * FROM vouchers 
                      WHERE is_active = 1 
                      AND start_date <= ? 
                      AND end_date >= ?
                      AND min_purchase <= ?
                      ORDER BY discount_value DESC";
    
    $voucher_stmt = $connection->prepare($voucher_query);
    $voucher_stmt->execute([$now, $now, $subtotal]);
    $available_vouchers = $voucher_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate totals
$shipping_cost = 15000;
$tax_amount = $subtotal * 0.1;
$voucher_discount = 0;

// Calculate voucher discount if applied
if (isset($_SESSION['voucher'])) {
    $voucher = $_SESSION['voucher'];
    
    if ($voucher['discount_type'] === 'percentage') {
        $voucher_discount = $subtotal * ($voucher['discount_value'] / 100);
        if ($voucher['max_discount'] && $voucher_discount > $voucher['max_discount']) {
            $voucher_discount = $voucher['max_discount'];
        }
    } else {
        $voucher_discount = $voucher['discount_value'];
    }
    
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
                            
                            <a href="#" class="btn btn-danger clear-cart">
                                <i class="fas fa-trash mr-2"></i>Clear Cart
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="card sticky top-4">
                        <div class="card-header">
                            <h2 class="card-title">Order Summary</h2>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Voucher Section -->
                            <?php if (isset($_SESSION['voucher'])): ?>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-1">
                                                <i class="fas fa-ticket-alt text-green-600 mr-2"></i>
                                                <span class="font-semibold text-green-800"><?php echo $_SESSION['voucher']['code']; ?></span>
                                            </div>
                                            <p class="text-green-600 text-sm">
                                                <?php echo $_SESSION['voucher']['nama_voucher']; ?>
                                            </p>
                                            <p class="text-green-700 text-sm font-semibold mt-1">
                                                Save <?php echo $_SESSION['voucher']['discount_type'] === 'percentage' ? 
                                                      $_SESSION['voucher']['discount_value'] . '%' : 
                                                      formatRupiah($_SESSION['voucher']['discount_value']); ?>
                                            </p>
                                        </div>
                                        <form method="POST" class="inline">
                                            <button type="submit" name="remove_voucher" class="text-red-500 hover:text-red-700 transition">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($available_vouchers)): ?>
                                    <button type="button" 
                                            onclick="showVoucherModal()" 
                                            class="w-full border-2 border-dashed border-blue-300 rounded-lg p-4 hover:border-blue-500 hover:bg-blue-50 transition text-left">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                <i class="fas fa-ticket-alt text-blue-600"></i>
                                            </div>
                                            <div class="flex-1">
                                                <p class="font-semibold text-gray-800">Choose Promo</p>
                                                <p class="text-sm text-gray-600"><?php echo count($available_vouchers); ?> voucher available</p>
                                            </div>
                                            <i class="fas fa-chevron-right text-gray-400"></i>
                                        </div>
                                    </button>
                                <?php else: ?>
                                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-4 text-center">
                                        <i class="fas fa-ticket-alt text-gray-300 text-2xl mb-2"></i>
                                        <p class="text-sm text-gray-500">No vouchers available</p>
                                        <p class="text-xs text-gray-400 mt-1">Add more items to unlock vouchers</p>
                                    </div>
                                <?php endif; ?>
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

    <!-- Voucher Modal -->
    <div id="voucherModal" class="modal-overlay hidden">
        <div class="modal-container max-w-2xl">
            <div class="modal-header">
                <h3 class="text-xl font-bold">Choose Promo</h3>
                <button onclick="closeVoucherModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div class="modal-body max-h-96 overflow-y-auto">
                <?php if (!empty($available_vouchers)): ?>
                    <div class="space-y-3">
                        <?php foreach ($available_vouchers as $voucher): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition cursor-pointer voucher-item"
                             onclick="applyVoucher(<?php echo $voucher['id']; ?>)">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-ticket-alt text-white text-2xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($voucher['nama_voucher']); ?></h4>
                                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($voucher['description']); ?></p>
                                    <div class="flex items-center text-sm">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded font-semibold mr-2">
                                            <?php echo $voucher['code']; ?>
                                        </span>
                                        <span class="text-green-600 font-semibold">
                                            Save <?php echo $voucher['discount_type'] === 'percentage' ? 
                                                  $voucher['discount_value'] . '%' : 
                                                  formatRupiah($voucher['discount_value']); ?>
                                        </span>
                                    </div>
                                    <?php if ($voucher['min_purchase'] > 0): ?>
                                    <p class="text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Min. purchase: <?php echo formatRupiah($voucher['min_purchase']); ?>
                                    </p>
                                    <?php endif; ?>
                                    <?php if ($voucher['discount_type'] === 'percentage' && $voucher['max_discount']): ?>
                                    <p class="text-xs text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Max discount: <?php echo formatRupiah($voucher['max_discount']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-shrink-0">
                                    <button class="btn btn-primary btn-sm">
                                        Use
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-ticket-alt text-gray-300 text-5xl mb-4"></i>
                        <p class="text-gray-500">No vouchers available at the moment</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        // Cart Data
        const cartData = {
            shipping: <?php echo $shipping_cost; ?>,
            taxRate: 0.1
        };
        
        // Format currency
        function formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(amount));
        }
        
        // Update order summary
        function updateOrderSummary() {
            let subtotal = 0;
            let totalItems = 0;
            
            document.querySelectorAll('[data-product-id]').forEach(row => {
                const productId = row.dataset.productId;
                const input = row.querySelector('.quantity-input');
                const priceElement = row.querySelector('.product-price');
                
                if (input && priceElement) {
                    const quantity = parseInt(input.value) || 0;
                    const price = parseFloat(priceElement.dataset.price) || 0;
                    const itemTotal = price * quantity;
                    
                    const totalElement = row.querySelector(`.item-total[data-product-id="${productId}"]`);
                    if (totalElement) {
                        totalElement.textContent = formatRupiah(itemTotal);
                    }
                    
                    subtotal += itemTotal;
                    totalItems += quantity;
                }
            });
            
            document.getElementById('totalItemsCount').textContent = totalItems;
            document.getElementById('subtotalAmount').textContent = formatRupiah(subtotal);
            
            const tax = subtotal * cartData.taxRate;
            document.getElementById('taxAmount').textContent = formatRupiah(tax);
            
            const grandTotal = subtotal + cartData.shipping + tax;
            document.getElementById('grandTotalAmount').textContent = formatRupiah(grandTotal);
        }
        
        // Update quantity via AJAX
        function updateQuantity(productId, quantity) {
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `update_quantity=1&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateOrderSummary();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to update cart', 'error');
            });
        }
        
        // Event listeners
        document.addEventListener('click', function(e) {
            // Decrease quantity
            if (e.target.closest('.decrease-quantity')) {
                const button = e.target.closest('.decrease-quantity');
                const productId = button.dataset.productId;
                const productName = button.dataset.productName;
                const input = document.querySelector(`input[data-product-id="${productId}"]`);
                
                if (!input) return;
                
                const currentQuantity = parseInt(input.value);
                
                if (currentQuantity > 1) {
                    input.value = currentQuantity - 1;
                    updateQuantity(productId, currentQuantity - 1);
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
            
            // Increase quantity
            if (e.target.closest('.increase-quantity')) {
                const button = e.target.closest('.increase-quantity');
                const productId = button.dataset.productId;
                const maxStock = parseInt(button.dataset.maxStock);
                const input = document.querySelector(`input[data-product-id="${productId}"]`);
                
                if (!input) return;
                
                const currentQuantity = parseInt(input.value);
                
                if (currentQuantity < maxStock) {
                    input.value = currentQuantity + 1;
                    updateQuantity(productId, currentQuantity + 1);
                } else {
                    showToast('Maximum stock reached', 'warning');
                }
            }
            
            // Remove item
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
            
            // Clear cart
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
        
        // Voucher Modal Functions
        function showVoucherModal() {
            document.getElementById('voucherModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeVoucherModal() {
            document.getElementById('voucherModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        function applyVoucher(voucherId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cart.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'apply_voucher';
            input.value = '1';
            
            const voucherInput = document.createElement('input');
            voucherInput.type = 'hidden';
            voucherInput.name = 'voucher_id';
            voucherInput.value = voucherId;
            
            form.appendChild(input);
            form.appendChild(voucherInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Close modal when clicking outside
        document.getElementById('voucherModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'voucherModal') {
                closeVoucherModal();
            }
        });
    </script>
</body>
</html>