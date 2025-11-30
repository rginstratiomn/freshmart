<?php
require_once '../includes/functions.php';

// Authentication check for kasir
if (!isLoggedIn() || !isKasir()) {
    redirect('../auth/login.php');
}

$db = new Database();
$connection = $db->getConnection();

// Process POS transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    try {
        $connection->beginTransaction();
        
        // Generate transaction code
        $transaction_code = 'TRX-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Calculate totals from cart items
        $subtotal = 0;
        $cart_items = $_POST['cart_items'] ?? [];
        
        foreach ($cart_items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $discount_amount = $_POST['discount_amount'] ?? 0;
        $tax_amount = $subtotal * 0.1; // 10% tax
        $grand_total = $subtotal - $discount_amount + $tax_amount;
        
        // Insert transaction
        $insert_transaction = $connection->prepare("
            INSERT INTO transactions (transaction_code, kasir_id, customer_name, subtotal, discount_amount, tax_amount, grand_total, payment_method, payment_amount, change_amount, status, transaction_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', CURDATE())
        ");
        
        $payment_amount = $_POST['payment_amount'];
        $change_amount = $payment_amount - $grand_total;
        
        $customer_name = !empty($_POST['customer_name']) ? $_POST['customer_name'] : 'Walk-in Customer';
        
        $insert_transaction->execute([
            $transaction_code,
            $_SESSION['user_id'],
            $customer_name,
            $subtotal,
            $discount_amount,
            $tax_amount,
            $grand_total,
            $_POST['payment_method'],
            $payment_amount,
            $change_amount
        ]);
        
        $transaction_id = $connection->lastInsertId();
        
        // Insert transaction details and update stock
        foreach ($cart_items as $item) {
            $insert_detail = $connection->prepare("
                INSERT INTO transaction_details (transaction_id, product_id, product_name, quantity, price, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $item_subtotal = $item['price'] * $item['quantity'];
            $insert_detail->execute([
                $transaction_id,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['price'],
                $item_subtotal
            ]);
            
            // Update product stock
            $update_stock = $connection->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
            $update_stock->execute([$item['quantity'], $item['product_id']]);
            
            // Record stock movement
            $record_movement = $connection->prepare("
                INSERT INTO stock_movements (product_id, user_id, movement_type, quantity, stock_before, stock_after, reference_type, reference_id, reference_number, notes)
                SELECT ?, ?, 'out', ?, stok, stok - ?, 'transaction', ?, ?, 'POS Sale'
                FROM products WHERE id = ?
            ");
            $record_movement->execute([
                $item['product_id'],
                $_SESSION['user_id'],
                $item['quantity'],
                $item['quantity'],
                $transaction_id,
                $transaction_code,
                $item['product_id']
            ]);
        }
        
        $connection->commit();
        
        $_SESSION['pos_success'] = [
            'transaction_code' => $transaction_code,
            'grand_total' => $grand_total,
            'payment_amount' => $payment_amount,
            'change_amount' => $change_amount
        ];
        
        redirect('success.php?transaction_id=' . $transaction_id);
        
    } catch (PDOException $e) {
        $connection->rollBack();
        error_log("POS Transaction error: " . $e->getMessage());
        $error = "Transaction failed: " . $e->getMessage();
    }
}

// Get products for POS
$products_query = "SELECT * FROM products WHERE status = 'active' AND stok > 0 ORDER BY nama_produk";
$products_stmt = $connection->query($products_query);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - FreshMart Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .pos-product:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        #posCart {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Product Selection -->
        <div class="w-2/3 bg-white border-r border-gray-200 overflow-auto">
            <div class="p-4 border-b border-gray-200 bg-blue-600 text-white">
                <h1 class="text-2xl font-bold">POS System - FreshMart Pro</h1>
                <p class="text-blue-100">Kasir: <?php echo $_SESSION['full_name']; ?></p>
            </div>
            
            <div class="p-4 border-b border-gray-200">
                <div class="flex space-x-4">
                    <input type="text" id="productSearch" placeholder="Search products by name or barcode..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button id="scanBarcode" class="btn btn-primary">
                        <i class="fas fa-barcode mr-2"></i>Scan
                    </button>
                </div>
            </div>
            
            <div class="p-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="productGrid">
                <?php foreach($products as $product): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-4 cursor-pointer hover:shadow-md transition-all duration-200 pos-product"
                     data-product-id="<?php echo $product['id']; ?>"
                     data-product-name="<?php echo $product['nama_produk']; ?>"
                     data-product-price="<?php echo $product['harga_jual']; ?>"
                     data-product-stock="<?php echo $product['stok']; ?>">
                                        <img src="../uploads/<?php echo $product['foto_utama'] ?: 'default-product.jpg'; ?>" 
                         alt="<?php echo $product['nama_produk']; ?>" 
                         class="w-full h-24 object-cover rounded-lg mb-2"
                         onerror="this.src='https://via.placeholder.com/150x100?text=No+Image'">
                    <h3 class="font-semibold text-gray-800 text-sm mb-1 truncate"><?php echo $product['nama_produk']; ?></h3>
                    <p class="text-green-600 font-bold text-lg"><?php echo formatRupiah($product['harga_jual']); ?></p>
                    <p class="text-gray-500 text-xs">Stock: <?php echo $product['stok']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Cart & Checkout -->
        <div class="w-1/3 bg-gray-50 flex flex-col">
            <div class="p-4 border-b border-gray-200 bg-white">
                <h2 class="text-xl font-semibold text-gray-800">Current Transaction</h2>
                <p class="text-gray-500 text-sm"><?php echo date('d M Y H:i:s'); ?></p>
            </div>
            
            <form method="POST" class="flex-1 flex flex-col">
                <div class="flex-1 overflow-auto p-4">
                    <div id="posCart" class="space-y-3">
                        <!-- Cart items will be added here dynamically -->
                    </div>
                    
                    <div id="emptyCart" class="text-center py-8 text-gray-500">
                        <i class="fas fa-shopping-cart text-4xl mb-4"></i>
                        <p>No items in cart</p>
                        <p class="text-sm">Click on products to add them</p>
                    </div>
                </div>
                
                <div class="p-4 border-t border-gray-200 bg-white space-y-4">
                    <!-- Customer Info -->
                    <div class="form-group">
                        <label class="form-label">Customer Name</label>
                        <input type="text" id="customerName" name="customer_name" 
                               class="form-input" placeholder="Walk-in Customer">
                    </div>
                    
                    <!-- Totals -->
                    <div class="space-y-2 bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span id="subtotal">Rp 0</span>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input type="number" id="discountAmount" name="discount_amount" value="0" min="0" 
                                   class="form-input w-24 text-right">
                            <span class="text-gray-600 text-sm">Discount</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax (10%):</span>
                            <span id="taxAmount">Rp 0</span>
                        </div>
                        
                        <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                            <span>Total:</span>
                            <span id="grandTotal" class="text-green-600">Rp 0</span>
                        </div>
                    </div>
                    
                    <!-- Payment -->
                    <div class="space-y-3">
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" id="paymentMethod" 
                                    class="form-select" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="debit">Debit Card</option>
                                <option value="credit">Credit Card</option>
                                <option value="ewallet">E-Wallet</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Payment Amount</label>
                            <input type="number" id="paymentAmount" name="payment_amount" 
                                   class="form-input" 
                                   placeholder="Enter payment amount" 
                                   required min="0" step="500">
                        </div>
                        
                        <div id="changeAmount" class="text-lg font-semibold text-blue-600 hidden">
                            Change: <span id="changeValue">Rp 0</span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" id="clearCart" class="btn btn-danger">
                            <i class="fas fa-trash mr-2"></i>Clear
                        </button>
                        <button type="submit" name="process_payment" id="processPayment" 
                                class="btn btn-primary" disabled>
                            <i class="fas fa-credit-card mr-2"></i>Process Payment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/pos.js"></script>
</body>
</html>