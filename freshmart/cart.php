<?php
require_once 'includes/functions.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'] ?? 1;
        
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
    }
    
    if (isset($_POST['apply_voucher'])) {
        $voucher_code = sanitize($_POST['voucher_code']);
        // Voucher logic would go here
        $_SESSION['voucher_code'] = $voucher_code;
        $_SESSION['success'] = "Voucher applied!";
        redirect('cart.php');
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
}

// Clear cart
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    unset($_SESSION['voucher_code']);
    $_SESSION['success'] = "Cart cleared!";
    redirect('cart.php');
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
$voucher_discount = 0; // Voucher logic would go here
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
                        
                        <form method="POST">
                            <div class="divide-y divide-gray-200">
                                <?php foreach ($cart_products as $item): ?>
                                <div class="p-6 flex items-center space-x-4">
                                    <img src="uploads/<?php echo $item['foto_utama'] ?: 'default-product.jpg'; ?>" 
                                         alt="<?php echo $item['nama_produk']; ?>" 
                                         class="w-20 h-20 object-cover rounded-lg"
                                         onerror="this.src='https://via.placeholder.com/80x80?text=No+Image'">
                                    
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-800"><?php echo $item['nama_produk']; ?></h3>
                                        <p class="text-gray-600"><?php echo formatRupiah($item['harga_jual']); ?></p>
                                        <p class="text-sm text-gray-500">Stock: <?php echo $item['stok']; ?></p>
                                    </div>
                                    
                                    <div class="flex items-center space-x-4">
                                        <div class="flex items-center border border-gray-300 rounded-lg">
                                            <button type="button" 
                                                    class="decrease-quantity w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100"
                                                    data-product-id="<?php echo $item['id']; ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   name="quantities[<?php echo $item['id']; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item['stok']; ?>"
                                                   class="quantity-input w-16 text-center border-0 py-2">
                                            <button type="button" 
                                                    class="increase-quantity w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100"
                                                    data-product-id="<?php echo $item['id']; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="text-lg font-semibold text-gray-800 w-24 text-right">
                                            <?php echo formatRupiah($item['total']); ?>
                                        </div>
                                        
                                        <a href="cart.php?remove=<?php echo $item['id']; ?>" 
                                           class="text-red-500 hover:text-red-700 p-2"
                                           onclick="return confirm('Are you sure you want to remove this item?')">
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
                                    <a href="cart.php?clear=1" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Are you sure you want to clear your entire cart?')">
                                        <i class="fas fa-trash mr-2"></i>Clear Cart
                                    </a>
                                    
                                    <button type="submit" name="update_cart" class="btn btn-primary">
                                        <i class="fas fa-sync mr-2"></i>Update Cart
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
                            <form method="POST" class="space-y-3">
                                <div class="form-group">
                                    <label class="form-label">Voucher Code</label>
                                    <div class="flex space-x-2">
                                        <input type="text" 
                                               name="voucher_code" 
                                               value="<?php echo $_SESSION['voucher_code'] ?? ''; ?>" 
                                               class="form-input flex-1" 
                                               placeholder="Enter voucher code">
                                        <button type="submit" name="apply_voucher" class="btn btn-outline">
                                            Apply
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Price Breakdown -->
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal (<?php echo $total_items; ?> items)</span>
                                    <span class="font-semibold"><?php echo formatRupiah($subtotal); ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping</span>
                                    <span class="font-semibold"><?php echo formatRupiah($shipping_cost); ?></span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax (10%)</span>
                                    <span class="font-semibold"><?php echo formatRupiah($tax_amount); ?></span>
                                </div>
                                
                                <?php if ($voucher_discount > 0): ?>
                                <div class="flex justify-between text-green-600">
                                    <span>Voucher Discount</span>
                                    <span class="font-semibold">-<?php echo formatRupiah($voucher_discount); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="border-t border-gray-200 pt-3 flex justify-between text-lg font-bold">
                                    <span>Total</span>
                                    <span class="text-green-600"><?php echo formatRupiah($grand_total); ?></span>
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

    <script src="assets/js/cart.js"></script>
</body>
</html>