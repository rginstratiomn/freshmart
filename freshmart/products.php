<?php
require_once 'includes/functions.php';

$db = new Database();
$connection = $db->getConnection();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build base query - FIXED: Gunakan subquery untuk kategori
$query = "SELECT p.*, 
                 (SELECT c.nama_kategori 
                  FROM product_categories pc 
                  JOIN categories c ON pc.category_id = c.id 
                  WHERE pc.product_id = p.id 
                  ORDER BY c.id LIMIT 1) as nama_kategori
          FROM products p 
          WHERE p.status = 'active' AND p.is_available_online = 1";

$params = [];
$conditions = [];

if(!empty($search)) {
    $conditions[] = "(p.nama_produk LIKE ? OR p.deskripsi_pendek LIKE ? OR p.sku LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if(!empty($category_id)) {
    // Untuk filter kategori, gunakan EXISTS
    $conditions[] = "EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id = ?)";
    $params[] = $category_id;
}

if(!empty($min_price)) {
    $conditions[] = "p.harga_jual >= ?";
    $params[] = $min_price;
}

if(!empty($max_price)) {
    $conditions[] = "p.harga_jual <= ?";
    $params[] = $max_price;
}

// Add conditions to query
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Add sorting
switch($sort) {
    case 'price_low':
        $query .= " ORDER BY p.harga_jual ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.harga_jual DESC";
        break;
    case 'name':
        $query .= " ORDER BY p.nama_produk ASC";
        break;
    case 'popular':
        $query .= " ORDER BY p.sales_count DESC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$query .= " LIMIT $limit OFFSET $offset";

// Execute query
try {
    $stmt = $connection->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Products query error: " . $e->getMessage());
    $products = [];
}

// Get total count for pagination - FIXED: Query yang sama tanpa pagination
$count_query = "SELECT COUNT(*) as total 
                FROM products p 
                WHERE p.status = 'active' AND p.is_available_online = 1";

$count_params = [];
$count_conditions = [];

if(!empty($search)) {
    $count_conditions[] = "(p.nama_produk LIKE ? OR p.deskripsi_pendek LIKE ? OR p.sku LIKE ?)";
    $search_term = "%$search%";
    $count_params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
}

if(!empty($category_id)) {
    $count_conditions[] = "EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id = ?)";
    $count_params[] = $category_id;
}

if(!empty($min_price)) {
    $count_conditions[] = "p.harga_jual >= ?";
    $count_params[] = $min_price;
}

if(!empty($max_price)) {
    $count_conditions[] = "p.harga_jual <= ?";
    $count_params[] = $max_price;
}

if (!empty($count_conditions)) {
    $count_query .= " AND " . implode(" AND ", $count_conditions);
}

try {
    $count_stmt = $connection->prepare($count_query);
    $count_stmt->execute($count_params);
    $total_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_products = $total_result ? $total_result['total'] : 0;
} catch (PDOException $e) {
    error_log("Count query error: " . $e->getMessage());
    $total_products = 0;
}

$total_pages = ceil($total_products / $limit);

// Get categories for filter
$categories_query = "SELECT * FROM categories WHERE is_active = 1 AND parent_id IS NULL";
$categories_stmt = $connection->query($categories_query);
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - FreshMart Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filters -->
            <div class="lg:w-1/4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filters</h3>
                    </div>
                    
                    <form method="GET" class="space-y-4">
                        <!-- Search -->
                        <div class="form-group">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   class="form-input" placeholder="Search products...">
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['nama_kategori']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="form-group">
                            <label class="form-label">Price Range</label>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" value="<?php echo $min_price; ?>" 
                                       class="form-input" placeholder="Min" min="0">
                                <input type="number" name="max_price" value="<?php echo $max_price; ?>" 
                                       class="form-input" placeholder="Max" min="0">
                            </div>
                        </div>
                        
                        <!-- Sort -->
                        <div class="form-group">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                        
                        <?php if($search || $category_id || $min_price || $max_price): ?>
                            <a href="products.php" class="btn btn-outline w-full">
                                <i class="fas fa-times mr-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="lg:w-3/4">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Products 
                        <?php if($search): ?>
                            <span class="text-lg font-normal text-gray-600">for "<?php echo htmlspecialchars($search); ?>"</span>
                        <?php endif; ?>
                    </h1>
                    <p class="text-gray-600">Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products</p>
                </div>
                
                <?php if(empty($products)): ?>
                    <div class="card text-center py-12">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No products found</h3>
                        <p class="text-gray-500 mb-4">Try adjusting your search or filter criteria</p>
                        <a href="products.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach($products as $product): ?>
                        <div class="product-card">
                            <img src="uploads/<?php echo $product['foto_utama'] ?: 'default-product.jpg'; ?>" 
                                 alt="<?php echo $product['nama_produk']; ?>" 
                                 class="product-image"
                                 onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                            <div class="product-info">
                                <h3 class="product-name"><?php echo $product['nama_produk']; ?></h3>
                                <p class="text-gray-600 text-sm mb-2"><?php echo $product['deskripsi_pendek']; ?></p>
                                <p class="text-gray-500 text-xs mb-3">
                                    <i class="fas fa-tag mr-1"></i><?php echo $product['nama_kategori'] ?: 'Uncategorized'; ?>
                                </p>
                                
                                <?php if($product['stok'] <= 0): ?>
                                    <div class="badge badge-danger mb-3">Out of Stock</div>
                                <?php elseif($product['stok'] <= $product['minimum_stok']): ?>
                                    <div class="badge badge-warning mb-3">Low Stock</div>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <span class="product-price"><?php echo formatRupiah($product['harga_jual']); ?></span>
                                    
                                    <?php if($product['stok'] > 0): ?>
                                        <button class="btn btn-primary btn-sm add-to-cart" 
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo $product['nama_produk']; ?>"
                                                data-product-price="<?php echo $product['harga_jual']; ?>">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline btn-sm" disabled>
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($product['stok'] > 0): ?>
                                    <div class="text-xs text-gray-500 mt-2">
                                        Stock: <?php echo $product['stok']; ?> <?php echo $product['satuan']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                               class="pagination-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                               class="pagination-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>