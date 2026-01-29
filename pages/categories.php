<?php
session_start();
require_once '../config/database.php';

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? '';
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Fetch all categories with their product counts
$categories_stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON (c.id = p.category_id AND p.is_active = 1)
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
    <link rel="stylesheet" href="../css/categories.css">
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
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="categorySearch" placeholder="Search categories..." autocomplete="off">
            </div>
        </div>

        <div class="category-grid" id="categoryGrid">
            <?php foreach ($categories as $cat): 
                $is_out_of_stock = $cat['product_count'] == 0;
            ?>
                <a href="../customer/products/browse.php?category=<?php echo $cat['id']; ?>" 
                   class="category-card <?php echo $is_out_of_stock ? 'out-of-stock' : ''; ?>" 
                   data-name="<?php echo strtolower(htmlspecialchars($cat['name'])); ?>">
                    
                    <?php if ($is_out_of_stock): ?>
                        <div class="stock-badge">Out of Stock</div>
                    <?php endif; ?>

                    <div class="category-image-wrapper">
                        <img src="../<?php echo htmlspecialchars($cat['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($cat['name']); ?>"
                             onerror="this.src='../images/categories/default.jpg'">
                    </div>
                    <div class="category-content">
                        <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
                        <p><?php echo htmlspecialchars($cat['description']); ?></p>
                        <div class="category-stats">
                            <span class="category-count">
                                <?php if ($is_out_of_stock): ?>
                                    🚫 Out of Stock
                                <?php else: ?>
                                    🍫 <?php echo $cat['product_count']; ?> Products
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Empty State -->
        <div id="noResults" style="display: none; text-align: center; padding: 5rem 2rem; color: var(--cream); opacity: 0.7;">
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">🍫🔍</div>
            <h3>No categories found matching your search.</h3>
            <p>Try a different keyword!</p>
        </div>
    </div>

    <script>
        document.getElementById('categorySearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.category-card');
            const noResults = document.getElementById('noResults');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(searchTerm)) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        });
    </script>

    <?php 
    $logo_path = '../images/logo.png';
    include '../includes/footer.php'; 
    ?>
</body>
</html>
