<!-- Customer Header Component -->
<?php
// This file should be included after establishing $customer_id and $username in session
// It will display navigation, wallet balance, and user menu

// Fetch wallet balance if not already loaded
if (!isset($wallet_balance)) {
    $wallet_stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $wallet_stmt->execute([$customer_id]);
    $wallet_balance = $wallet_stmt->fetchColumn();
}
?>
<header class="site-header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <a href="/project/Choco world/customer/dashboard.php" class="header-logo">
                <img src="/project/Choco world/images/logo.png" alt="Choco World">
                <span>Choco World</span>
            </a>
            
            <!-- Main Navigation -->
            <nav class="main-nav">
                <a href="/project/Choco world/customer/dashboard.php" class="nav-link">🏠 Home</a>
                <a href="/project/Choco world/pages/about.php" class="nav-link">ℹ️ About</a>
                <a href="/project/Choco world/customer/products/browse.php" class="nav-link">🍫 Products</a>
                <a href="/project/Choco world/pages/categories.php" class="nav-link">📂 Categories</a>
                <a href="/project/Choco world/pages/contact.php" class="nav-link">📞 Contact</a>
            </nav>
            
            <!-- User Actions -->
            <div class="header-actions">
                <!-- Wallet Balance -->
                <div class="wallet-display">
                    <span class="wallet-icon">💰</span>
                    <span class="wallet-amount">$<?php echo number_format($wallet_balance, 2); ?></span>
                </div>
                
                <!-- Cart -->
                <a href="/project/Choco world/customer/products/cart.php" class="header-icon-btn" title="Shopping Cart">
                    🛒
                    <span class="cart-badge" id="cart-count">0</span>
                </a>
                
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
                        <a href="/project/Choco world/customer/profile/settings.php" class="dropdown-item">⚙️ Settings</a>
                        <a href="/project/Choco world/customer/orders/list.php" class="dropdown-item">📦 My Orders</a>
                        <div class="dropdown-divider"></div>
                        <a href="/project/Choco world/auth/logout.php" class="dropdown-item logout">🚪 Logout</a>
                    </div>
                </div>
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
            <a href="/project/Choco world/customer/dashboard.php" class="mobile-nav-link">🏠 Home</a>
            <a href="/project/Choco world/pages/about.php" class="mobile-nav-link">ℹ️ About</a>
            <a href="/project/Choco world/customer/products/browse.php" class="mobile-nav-link">🍫 Products</a>
            <a href="/project/Choco world/pages/categories.php" class="mobile-nav-link">📂 Categories</a>
            <a href="/project/Choco world/pages/contact.php" class="mobile-nav-link">📞 Contact</a>
            <div class="mobile-divider"></div>
            <a href="/project/Choco world/customer/profile/settings.php" class="mobile-nav-link">⚙️ Settings</a>
            <a href="/project/Choco world/customer/orders/list.php" class="mobile-nav-link">📦 My Orders</a>
            <a href="/project/Choco world/auth/logout.php" class="mobile-nav-link">🚪 Logout</a>
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
    fetch('../products/cart.php?count=true')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('cart-count');
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(() => {});
}

// Load cart count on page load
if (document.getElementById('cart-count')) {
    updateCartCount();
}
</script>
