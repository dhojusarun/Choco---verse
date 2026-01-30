<!-- Vendor Header Component -->
<?php
// This file should be included after establishing $username in session
?>
<header class="site-header vendor-header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <a href="/project/Choco world/vendor/dashboard.php" class="header-logo">
                <img src="/project/Choco world/images/logo.png" alt="Choco World">
                <span>Vendor Portal</span>
            </a>
            
            <!-- Main Navigation -->
            <nav class="main-nav">
                <a href="/project/Choco world/vendor/dashboard.php" class="nav-link">📊 Dashboard</a>
                <a href="/project/Choco world/vendor/products/list.php" class="nav-link">🍫 My Products</a>
                <a href="/project/Choco world/vendor/orders/list.php" class="nav-link">📦 Orders</a>
                <a href="/project/Choco world/vendor/customers/list.php" class="nav-link">👥 Customers</a>
                <a href="/project/Choco world/vendor/analytics/dashboard.php" class="nav-link">📈 Analytics</a>
            </nav>
            
            <!-- User Actions -->
            <div class="header-actions">
                <!-- User Menu -->
                <div class="user-menu">
                    <button class="user-menu-btn">
                        <span class="user-avatar">🏪</span>
                        <span class="user-name">
                        <?php 
                        $query = "SELECT username FROM users WHERE id = ?";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([$vendor_id]);

                        $username = $stmt->fetchColumn();
                        echo htmlspecialchars($username);
                        ?>
                        </span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="user-dropdown">
                        <a href="/project/Choco world/vendor/settings/profile.php" class="dropdown-item">⚙️ Store Settings</a>
                        <a href="/project/Choco world/vendor/reviews/list.php" class="dropdown-item">⭐ Reviews</a>
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
            <a href="/project/Choco world/vendor/dashboard.php" class="mobile-nav-link">📊 Dashboard</a>
            <a href="/project/Choco world/vendor/products/list.php" class="mobile-nav-link">🍫 My Products</a>
            <a href="/project/Choco world/vendor/orders/list.php" class="mobile-nav-link">📦 Orders</a>
            <a href="/project/Choco world/vendor/customers/list.php" class="mobile-nav-link">👥 Customers</a>
            <a href="/project/Choco world/vendor/analytics/dashboard.php" class="mobile-nav-link">📈 Analytics</a>
            <div class="mobile-divider"></div>
            <a href="/project/Choco world/vendor/settings/profile.php" class="mobile-nav-link">⚙️ Store Settings</a>
            <a href="/project/Choco world/auth/logout.php" class="mobile-nav-link">🚪 Logout</a>
        </div>
    </div>
</header>

<script>
function toggleMobileMenu() {
    const mobileNav = document.getElementById('mobileNav');
    mobileNav.classList.toggle('active');
}
</script>
