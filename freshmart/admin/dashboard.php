<?php
require_once '../includes/functions.php';

// Authentication check
if (!isLoggedIn() || !isAdmin()) {
    redirect('../auth/login.php');
}

$db = new Database();
$connection = $db->getConnection();

// Get dashboard statistics
$stats = [];
$recent_orders = [];
$low_stock_products = [];

try {
    // Total Revenue
    $revenue_query = "SELECT COALESCE(SUM(grand_total), 0) as total_revenue FROM orders WHERE order_status = 'delivered'";
    $stats['total_revenue'] = $connection->query($revenue_query)->fetch(PDO::FETCH_ASSOC)['total_revenue'];
    
    // Total Orders
    $orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $stats['total_orders'] = $connection->query($orders_query)->fetch(PDO::FETCH_ASSOC)['total_orders'];
    
    // Total Customers
    $customers_query = "SELECT COUNT(*) as total_customers FROM customers";
    $stats['total_customers'] = $connection->query($customers_query)->fetch(PDO::FETCH_ASSOC)['total_customers'];
    
    // Total Products
    $products_query = "SELECT COUNT(*) as total_products FROM products WHERE status = 'active'";
    $stats['total_products'] = $connection->query($products_query)->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    // Low Stock Products
    $low_stock_query = "SELECT COUNT(*) as low_stock FROM products WHERE stok <= minimum_stok AND status = 'active'";
    $stats['low_stock'] = $connection->query($low_stock_query)->fetch(PDO::FETCH_ASSOC)['low_stock'];
    
    // Pending Orders
    $pending_orders_query = "SELECT COUNT(*) as pending_orders FROM orders WHERE order_status = 'pending'";
    $stats['pending_orders'] = $connection->query($pending_orders_query)->fetch(PDO::FETCH_ASSOC)['pending_orders'];
    
    // Today's Revenue
    $today_revenue_query = "SELECT COALESCE(SUM(grand_total), 0) as today_revenue FROM orders WHERE DATE(created_at) = CURDATE() AND order_status = 'delivered'";
    $stats['today_revenue'] = $connection->query($today_revenue_query)->fetch(PDO::FETCH_ASSOC)['today_revenue'];
    
    // Recent Orders
    $recent_orders_query = "SELECT o.*, c.full_name 
                           FROM orders o 
                           LEFT JOIN customers c ON o.customer_id = c.id 
                           ORDER BY o.created_at DESC 
                           LIMIT 5";
    $recent_orders_stmt = $connection->query($recent_orders_query);
    $recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Low Stock Products List
    $low_stock_list_query = "SELECT p.*, c.nama_kategori 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.stok <= p.minimum_stok AND p.status = 'active' 
                            ORDER BY p.stok ASC 
                            LIMIT 5";
    $low_stock_stmt = $connection->query($low_stock_list_query);
    $low_stock_products = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Error loading dashboard data";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FreshMart Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Admin Layout -->
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-blue-800 text-white">
            <div class="p-6">
                <h1 class="text-2xl font-bold">FreshMart<span class="text-green-400">Pro</span></h1>
                <p class="text-blue-200 text-sm">Admin Panel</p>
            </div>
            
            <nav class="mt-6">
                <a href="dashboard.php" class="block py-3 px-6 bg-blue-900 text-white">
                    <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                </a>
                <a href="products.php" class="block py-3 px-6 hover:bg-blue-700 text-white">
                    <i class="fas fa-box mr-3"></i>Products
                </a>
                <a href="orders.php" class="block py-3 px-6 hover:bg-blue-700 text-white">
                    <i class="fas fa-shopping-cart mr-3"></i>Orders
                </a>
                <a href="customers.php" class="block py-3 px-6 hover:bg-blue-700 text-white">
                    <i class="fas fa-users mr-3"></i>Customers
                </a>
                <a href="categories.php" class="block py-3 px-6 hover:bg-blue-700 text-white">
                    <i class="fas fa-tags mr-3"></i>Categories
                </a>
                <a href="inventory.php" class="block py-3 px-6 hover:bg-blue-700 text-white">
                    <i class="fas fa-warehouse mr-3"></i>Inventory
                </a>
                <a href="reports.php" class="block py-3 px-6 hover:bg-blue-700 text-white">
                    <i class="fas fa-chart-bar mr-3"></i>Reports
                </a>
                <a href="../auth/logout.php" class="block py-3 px-6 hover:bg-blue-700 text-white mt-20">
                    <i class="fas fa-sign-out-alt mr-3"></i>Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <div class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex justify-between items-center px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-800">Dashboard Overview</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Revenue -->
                <div class="card">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Total Revenue</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo formatRupiah($stats['total_revenue']); ?></h3>
                        </div>
                    </div>
                </div>
                
                <!-- Total Orders -->
                <div class="card">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Total Orders</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_orders']; ?></h3>
                        </div>
                    </div>
                </div>
                
                <!-- Total Customers -->
                <div class="card">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <i class="fas fa-users text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Total Customers</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_customers']; ?></h3>
                        </div>
                    </div>
                </div>
                
                <!-- Total Products -->
                <div class="card">
                    <div class="flex items-center">
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <i class="fas fa-box text-orange-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Total Products</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_products']; ?></h3>
                        </div>
                    </div>
                </div>
                
                <!-- Today's Revenue -->
                <div class="card">
                    <div class="flex items-center">
                        <div class="p-3 bg-teal-100 rounded-lg">
                            <i class="fas fa-calendar-day text-teal-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Today's Revenue</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo formatRupiah($stats['today_revenue']); ?></h3>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Orders -->
                <div class="card">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Pending Orders</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['pending_orders']; ?></h3>
                        </div>
                    </div>
                </div>
                
                <!-- Low Stock -->
                <div class="card">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Low Stock Alert</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['low_stock']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders & Low Stock -->
            <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Orders</h3>
                        <a href="orders.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                            <?php echo $order['order_number']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $order['full_name'] ?? 'Guest'; ?></td>
                                    <td><?php echo formatRupiah($order['grand_total']); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php echo $order['order_status'] === 'delivered' ? 'badge-success' : 
                                                   ($order['order_status'] === 'pending' ? 'badge-warning' : 
                                                   'badge-info'); ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Low Stock Products -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Low Stock Alert</h3>
                        <a href="inventory.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Min Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($low_stock_products as $product): ?>
                                <tr>
                                    <td><?php echo $product['nama_produk']; ?></td>
                                    <td><?php echo $product['nama_kategori']; ?></td>
                                    <td>
                                        <span class="badge badge-danger"><?php echo $product['stok']; ?></span>
                                    </td>
                                    <td><?php echo $product['minimum_stok']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="p-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="products.php?action=add" class="btn btn-outline text-center py-4">
                            <i class="fas fa-plus text-2xl mb-2"></i>
                            <div>Add Product</div>
                        </a>
                        <a href="orders.php" class="btn btn-outline text-center py-4">
                            <i class="fas fa-shopping-cart text-2xl mb-2"></i>
                            <div>Manage Orders</div>
                        </a>
                        <a href="inventory.php" class="btn btn-outline text-center py-4">
                            <i class="fas fa-warehouse text-2xl mb-2"></i>
                            <div>Inventory</div>
                        </a>
                        <a href="reports.php" class="btn btn-outline text-center py-4">
                            <i class="fas fa-chart-bar text-2xl mb-2"></i>
                            <div>Reports</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>