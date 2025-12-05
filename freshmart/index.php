<?php
require_once 'includes/functions.php';

// Check if database is connected, if not show installation page
if (!checkDatabaseConnection()) {
    header('Location: install.php');
    exit();
}

$db = new Database();
$connection = $db->getConnection();

// Get featured products
$featured_products = [];
$categories = [];

try {
    $featured_query = "SELECT p.*, c.nama_kategori 
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.is_featured = 1 AND p.status = 'active' 
                      LIMIT 8";
    $featured_stmt = $connection->prepare($featured_query);
    $featured_stmt->execute();
    $featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get main categories
    $categories_query = "SELECT * FROM categories WHERE is_active = 1 AND parent_id IS NULL LIMIT 6";
    $categories_stmt = $connection->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error in index.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshMart Pro - Your Complete Shopping Solution</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container navbar-container">
            <a href="index.php" class="logo">
                FreshMart<span>Pro</span>
            </a>
            
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="products.php" class="nav-link">Products</a></li>
                <li>
                    <a href="cart.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Cart
                        
                    </a>
                </li>
                <?php if(isLoggedIn()): ?>
                    <?php if(isAdmin()): ?>
                        <li><a href="admin/dashboard.php" class="nav-link">Admin</a></li>
                    <?php elseif(isKasir()): ?>
                        <li><a href="pos/index.php" class="nav-link">POS</a></li>
                    <?php else: ?>
                        <li><a href="customer/dashboard.php" class="nav-link">My Account</a></li>
                    <?php endif; ?>
                    <li><a href="auth/logout.php" class="nav-link">Logout</a></li>
                <?php else: ?>
                    <li><a href="auth/login.php" class="nav-link">Login</a></li>
                    <li><a href="auth/register.php" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="hero-title">Your Complete Shopping Solution</h1>
            <p class="hero-subtitle">Fresh, Fast, Delivered - Everything you need in one place</p>
            <a href="products.php" class="btn btn-lg" style="background-color: var(--color-accent); color: white;">
                Shop Now <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="py-16 bg-white">
        <div class="container">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Shop by Category</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <?php foreach($categories as $category): ?>
                <div class="text-center group cursor-pointer" onclick="window.location.href='products.php?category=<?php echo $category['id']; ?>'">
                    <div class="bg-gray-100 rounded-full w-20 h-20 mx-auto flex items-center justify-center group-hover:bg-blue-100 transition duration-300">
                        <i class="fas fa-<?php echo $category['icon'] ?: 'shopping-basket'; ?> text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="mt-4 font-semibold text-gray-700 group-hover:text-blue-600"><?php echo $category['nama_kategori']; ?></h3>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-16 bg-gray-50">
        <div class="container">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Featured Products</h2>
            
            <?php if(empty($featured_products)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">No featured products available</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach($featured_products as $product): ?>
                    <div class="product-card">
                        <img src="uploads/<?php echo $product['foto_utama'] ?: 'default-product.jpg'; ?>" 
                             alt="<?php echo $product['nama_produk']; ?>" 
                             class="product-image"
                             onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                        <div class="product-info">
                            <h3 class="product-name"><?php echo $product['nama_produk']; ?></h3>
                            <p class="text-gray-600 text-sm mb-2"><?php echo $product['deskripsi_pendek']; ?></p>
                            <p class="text-gray-500 text-sm mb-3">Category: <?php echo $product['nama_kategori']; ?></p>
                            <div class="product-actions">
                                <span class="product-price"><?php echo formatRupiah($product['harga_jual']); ?></span>
                                <button class="btn btn-primary btn-sm add-to-cart" 
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-product-name="<?php echo $product['nama_produk']; ?>"
                                        data-product-price="<?php echo $product['harga_jual']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-12">
                    <a href="products.php" class="btn btn-primary btn-lg">
                        View All Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-white">
        <div class="container">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-green-100 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
                        <i class="fas fa-shipping-fast text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Fast Delivery</h3>
                    <p class="text-gray-600">Same day delivery available for orders before 2 PM</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-blue-100 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Quality Guarantee</h3>
                    <p class="text-gray-600">100% quality guarantee on all our products</p>
                </div>
                
                <div class="text-center">
                    <div class="bg-orange-100 rounded-full w-16 h-16 mx-auto flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">24/7 Support</h3>
                    <p class="text-gray-600">Round the clock customer support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>FreshMart<span style="color: var(--color-secondary);">Pro</span></h3>
                    <p class="text-gray-300 mt-4">Your complete shopping solution for fresh groceries and daily needs.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Customer Service</h4>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Returns & Refunds</a></li>
                        <li><a href="#">Shipping Info</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <div class="text-gray-300 space-y-2">
                        <p><i class="fas fa-map-marker-alt mr-2"></i>123 Supermarket St, Jakarta</p>
                        <p><i class="fas fa-phone mr-2"></i>(021) 1234-5678</p>
                        <p><i class="fas fa-envelope mr-2"></i>info@freshmartpro.com</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 FreshMart Pro. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>