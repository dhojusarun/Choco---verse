<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choco World - Premium Chocolate Experience</title>
    <meta name="description" content="Welcome to Choco World - Your premium destination for chocolate excellence">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="header-logo">
                    <img src="images/logo.png" alt="Choco World">
                    <span>Choco World</span>
                </a>
                <nav class="main-nav">
                    <a href="index.php" class="nav-link">🏠 Home</a>
                    <a href="customer/products/browse.php" class="nav-link">🍫 Products</a>
                    <a href="pages/categories.php" class="nav-link">📂 Categories</a>
                    <a href="customer/products/cart.php" class="nav-link">🛒 Cart</a>
                    <a href="pages/about.php" class="nav-link">ℹ️ About</a>
                    <a href="pages/contact.php" class="nav-link">📞 Contact</a>
                </nav>
                <div class="header-actions">
                    <a href="customer/login.php" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Login</a>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <img src="images/logo.png" alt="Choco World Logo" class="logo">
            <h1>Choco World</h1>
            <p>Where Chocolate Dreams Come True</p>
            <div class="cta-buttons">
                <a href="customer/login.php" class="btn btn-primary">Customer Portal</a>
                <a href="vendor/login.php" class="btn btn-secondary">Vendor Portal</a>
            </div>
        </div>
    </section>
    
    <?php 
    $logo_path = 'images/logo.png';
    include 'includes/footer.php'; 
    ?>
</body>
</html>
