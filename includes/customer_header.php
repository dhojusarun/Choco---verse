<!-- Customer Header Component -->
<?php
// Fetch wallet balance if not already loaded and user is logged in
if (!isset($wallet_balance) && isset($customer_id) && $customer_id > 0) {
    if (isset($pdo)) {
        $wallet_stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $wallet_stmt->execute([$customer_id]);
        $wallet_balance = $wallet_stmt->fetchColumn();
    } else {
        $wallet_balance = 0;
    }
} elseif (!isset($wallet_balance)) {
    $wallet_balance = 0;
}
?>
<header class="site-header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>customer/dashboard.php" class="header-logo">
                <img src="<?php echo BASE_URL; ?>images/logo.png" alt="Choco World">
                <span>Choco World</span>
            </a>
            
            <!-- Main Navigation -->
            <nav class="main-nav">
                <a href="<?php echo BASE_URL; ?>customer/dashboard.php" class="nav-link">🏠 Home</a>
                <a href="<?php echo BASE_URL; ?>customer/products/browse.php" class="nav-link">🍫 Products</a>
                <a href="<?php echo BASE_URL; ?>pages/categories.php" class="nav-link">📂 Categories</a>
                <a href="<?php echo BASE_URL; ?>customer/products/cart.php" class="nav-link">
                    🛒 Cart <span class="cart-badge-inline" id="cart-count-nav" style="display:none; background: var(--gold); color: var(--chocolate-dark); padding: 0.1rem 0.5rem; border-radius: 50%; font-size: 0.7rem; margin-left: 0.3rem; font-weight: bold;">0</span>
                </a>
                <a href="<?php echo BASE_URL; ?>pages/about.php" class="nav-link">ℹ️ About</a>
                <a href="<?php echo BASE_URL; ?>pages/contact.php" class="nav-link">📞 Contact</a>
            </nav>
            
            <!-- User Actions -->
            <div class="header-actions">
                <!-- Wallet Balance -->
                <div class="wallet-display">
                    <span class="wallet-icon">💰</span>
                    <span class="wallet-amount">$<?php echo number_format($wallet_balance, 2); ?></span>
                </div>
                
                <?php if (isset($customer_id) && $customer_id > 0): ?>
                <!-- User Menu -->
                <div class="user-menu">
                    <button class="user-menu-btn">
                        <span class="user-avatar">👤</span>
                        <span class="user-name"><?php 
                            $query = "SELECT username FROM users WHERE id = ?";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute([$customer_id]);

                            $username = $stmt->fetchColumn();
                            echo htmlspecialchars($username);
                            ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="user-dropdown">
                        <a href="<?php echo BASE_URL; ?>customer/profile/settings.php" class="dropdown-item">⚙️ Settings</a>
                        <a href="<?php echo BASE_URL; ?>customer/orders/list.php" class="dropdown-item">📦 My Orders</a>
                        <a href="<?php echo BASE_URL; ?>customer/favorites/list.php" class="dropdown-item">❤️ My Favorites</a>
                        <a href="<?php echo BASE_URL; ?>customer/reviews/my-reviews.php" class="dropdown-item">⭐ My Reviews</a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="dropdown-item logout">🚪 Logout</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?php echo BASE_URL; ?>customer/login.php" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Login</a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="mobile-nav" id="mobileNav">
            <a href="<?php echo BASE_URL; ?>customer/dashboard.php" class="mobile-nav-link">🏠 Home</a>
            <a href="<?php echo BASE_URL; ?>pages/about.php" class="mobile-nav-link">ℹ️ About</a>
            <a href="<?php echo BASE_URL; ?>customer/products/browse.php" class="mobile-nav-link">🍫 Products</a>
            <a href="<?php echo BASE_URL; ?>pages/categories.php" class="mobile-nav-link">📂 Categories</a>
            <a href="<?php echo BASE_URL; ?>pages/contact.php" class="mobile-nav-link">📞 Contact</a>
            <div class="mobile-divider"></div>
            <a href="<?php echo BASE_URL; ?>customer/profile/settings.php" class="mobile-nav-link">⚙️ Settings</a>
            <a href="<?php echo BASE_URL; ?>customer/orders/list.php" class="mobile-nav-link">📦 My Orders</a>
            <a href="<?php echo BASE_URL; ?>customer/favorites/list.php" class="mobile-nav-link">❤️ My Favorites</a>
            <a href="<?php echo BASE_URL; ?>customer/reviews/my-reviews.php" class="mobile-nav-link">⭐ My Reviews</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="mobile-nav-link">🚪 Logout</a>
        </div>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const mobileNav = document.getElementById('mobileNav');
    mobileNav.classList.toggle('active');
}

// Update cart count
function updateCartCount() {
    fetch('<?php echo BASE_URL; ?>customer/products/cart.php?count=true')
        .then(response => response.json())
        .then(data => {
            const badgeAction = document.getElementById('cart-count');
            const badgeNav = document.getElementById('cart-count-nav');
            
            if (data.count > 0) {
                if (badgeAction) {
                    badgeAction.textContent = data.count;
                    badgeAction.style.display = 'flex';
                    badgeAction.style.animation = 'cart-bounce 0.4s ease';
                    // Reset animation so it can trigger again
                    setTimeout(() => badgeAction.style.animation = '', 400);
                }
                if (badgeNav) {
                    badgeNav.textContent = data.count;
                    badgeNav.style.display = 'inline-block';
                }
            } else {
                if (badgeAction) badgeAction.style.display = 'none';
                if (badgeNav) badgeNav.style.display = 'none';
            }
        })
        .catch(() => {});
}

// Load cart count on page load
if (document.getElementById('cart-count')) {
    updateCartCount();
}
</script>
