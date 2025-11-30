<?php
// This file contains the common header/navigation
?>
<nav class="navbar">
    <div class="container navbar-container">
        <a href="index.php" class="logo">
            FreshMart<span>Pro</span>
        </a>
        
        <ul class="nav-links">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="products.php" class="nav-link">Products</a></li>
            <li><a href="about.php" class="nav-link">About</a></li>
            <li><a href="contact.php" class="nav-link">Contact</a></li>
            <li>
                <a href="cart.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <?php if (getCartItemCount() > 0): ?>
                        <span class="cart-badge"><?php echo getCartItemCount(); ?></span>
                    <?php endif; ?>
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