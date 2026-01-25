<?php
session_start();
require_once '../config/database.php';

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? '';
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Categories for display
// Fetch categories from database with product counts
$categories_stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
    GROUP BY c.id
    ORDER BY c.name ASC
");
$categories = $categories_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Categories - Choco World</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/pages.css">
</head>
<body>
    <?php 
    if ($role === 'customer') {
        include '../includes/customer_header.php';
    } elseif ($role === 'vendor') {
        include '../includes/vendor_header.php';
    } else {
        ?>
        <header class="site-header">
            <div class="container">
                <div class="header-content">
                    <a href="../index.php" class="header-logo">
                        <img src="../images/logo.png" alt="Choco World">
                        <span>Choco World</span>
                    </a>
                    <nav class="main-nav">
                        <a href="../index.php" class="nav-link">🏠 Home</a>
                        <a href="about.php" class="nav-link">ℹ️ About</a>
                        <a href="contact.php" class="nav-link">📞 Contact</a>
                    </nav>
                    <div class="header-actions">
                        <a href="../customer/login.php" class="btn btn-primary">Login</a>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }
    ?>

    <section class="page-hero">
        <div class="container">
            <h1>📂 Product Categories</h1>
            <p>Explore our world of chocolate variety</p>
        </div>
    </section>

    <div class="container">
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="../customer/products/browse.php?category=<?php echo $cat['id']; ?>" class="category-card">
                    <img src="../<?php echo htmlspecialchars($cat['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($cat['name']); ?>"
                         style="width: 100%; height: 200px; object-fit: cover; border-radius: 15px; margin-bottom: 1rem;"
                         onerror="this.src='../images/categories/default.jpg'">
                    <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                    <p><?php echo htmlspecialchars($cat['description']); ?></p>
                    <span class="category-count"><?php echo $cat['product_count']; ?> Products</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php 
    $logo_path = '../images/logo.png';
    include '../includes/footer.php'; 
    ?>
</body>
</html>
