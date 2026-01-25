<?php
session_start();
// Optional login check - allow guests to browse
$customer_id = $_SESSION['user_id'] ?? null;
$is_logged_in = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer';

require_once '../../config/database.php';
$category_filter = $_GET['category'] ?? null;



// Fetch all active products with vendor info, ratings, and category name
$products_query = "
    SELECT p.*, u.username as vendor_name, c.name as category_name,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(DISTINCT r.id) as review_count,
           CASE WHEN ? IS NOT NULL THEN EXISTS(SELECT 1 FROM favorites f WHERE f.product_id = p.id AND f.customer_id = ?) ELSE 0 END as is_favorite,
           CASE WHEN ? IS NOT NULL THEN EXISTS(SELECT 1 FROM cart c WHERE c.product_id = p.id AND c.customer_id = ?) ELSE 0 END as in_cart
    FROM products p
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.is_active = 1
";

$params = [$customer_id, $customer_id, $customer_id, $customer_id];

if ($category_filter) {
    if (is_numeric($category_filter)) {
        $products_query .= " AND p.category_id = ?";
    } else {
        $products_query .= " AND c.name = ?";
    }
    $params[] = $category_filter;
}

$products_query .= "
    GROUP BY p.id
    ORDER BY p.created_at DESC
";

$products_stmt = $pdo->prepare($products_query);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll();

// Fetch all categories for filtering
$all_categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Get cart count
$cart_count = 0;
if ($is_logged_in) {
    $cart_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE customer_id = ?");
    $cart_count_stmt->execute([$customer_id]);
    $cart_count = $cart_count_stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Choco World</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/products.css">
</head>
<body>
    <?php 
    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';
    if ($is_logged_in) {
        include $root . '/includes/customer_header.php';
    } else {
        // Simple guest header or just include the main site header if it's modular
        // For now, let's include the customer_header but we need to handle its requirements
        // Actually, customer_header.php expects $customer_id and $wallet_balance
        // Let's check customer_header.php again.
        
        // If not logged in, we should probably show the index header style
        // But for consistency let's mock the variables or use a guest-friendly version.
        $customer_id = 0;
        $wallet_balance = 0;
        $username = "Guest";
        include $root . '/includes/customer_header.php';
    }
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-content">
                <div class="dashboard-header" style="border-bottom: none; margin-bottom: 1rem;">
                    <div class="dashboard-title">
                        <h1>🛍️ Browse Products</h1>
                        <p>Discover premium chocolates from artisan vendors</p>
                    </div>
                </div>
            </div>
            
            <?php if ($cart_count > 0): ?>
            <a href="cart.php" class="cart-indicator">
                🛒 Cart (<?php echo $cart_count; ?>)
            </a>
            <?php endif; ?>
            
            <div class="dashboard-content">
                <?php if (count($products) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <button class="favorite-btn <?php echo $product['is_favorite'] ? 'active' : ''; ?>" 
                                onclick="toggleFavorite(<?php echo $product['id']; ?>, this)"
                                title="<?php echo $product['is_favorite'] ? 'Remove from favorites' : 'Add to favorites'; ?>">
                            <?php echo $product['is_favorite'] ? '❤️' : '🤍'; ?>
                        </button>
                        
                        <a href="details.php?id=<?php echo $product['id']; ?>">
                            <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-image"
                                 onerror="this.src='../../images/products/default-chocolate.jpg'">
                        </a>
                        
                        <div class="product-info">
                            <a href="details.php?id=<?php echo $product['id']; ?>" style="text-decoration: none;">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            </a>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <div class="vendor-name">by <?php echo htmlspecialchars($product['vendor_name']); ?></div>
                                <?php if ($product['category_name']): ?>
                                    <span style="font-size: 0.7rem; background: rgba(212, 175, 55, 0.2); color: var(--gold); padding: 0.2rem 0.5rem; border-radius: 5px;">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-rating">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= round($product['avg_rating']) ? '★' : '☆';
                                }
                                ?>
                                <?php echo number_format($product['avg_rating'], 1); ?>
                                <small>(<?php echo $product['review_count']; ?>)</small>
                            </div>
                            
                            <p class="product-description">
                                <?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...
                            </p>
                            
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            
                            <div style="margin-bottom: 1rem;">
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="stock-badge">In Stock: <?php echo $product['stock']; ?> units</span>
                                <?php else: ?>
                                    <span class="stock-badge out-of-stock" style="background: rgba(244, 67, 54, 0.2); color: #FFCDD2; border: 1px solid rgba(244, 67, 54, 0.3);">❌ Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <?php if ($product['stock'] <= 0): ?>
                                    <button class="btn btn-secondary btn-small" disabled style="opacity: 0.6; cursor: not-allowed;">Out of Stock</button>
                                <?php elseif ($product['in_cart']): ?>
                                    <button class="btn btn-secondary btn-small" disabled>✓ In Cart</button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-small" onclick="addToCart(<?php echo $product['id']; ?>, this)">
                                        🛒 Add to Cart
                                    </button>
                                <?php endif; ?>
                                <a href="details.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-small">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; opacity: 0.7;">
                    <h3>No Products Available</h3>
                    <p>Check back later for new chocolate treats!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function addToCart(productId, btn) {
            <?php if (!$is_logged_in): ?>
                window.location.href = '../login.php';
                return;
            <?php endif; ?>
            fetch('add-to-cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '✓ In Cart';
                    btn.disabled = true;
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-secondary');
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
        
        function toggleFavorite(productId, btn) {
            <?php if (!$is_logged_in): ?>
                window.location.href = '../login.php';
                return;
            <?php endif; ?>
            fetch('../favorites/toggle.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'added') {
                        btn.innerHTML = '❤️';
                        btn.classList.add('active');
                        btn.title = 'Remove from favorites';
                    } else {
                        btn.innerHTML = '🤍';
                        btn.classList.remove('active');
                        btn.title = 'Add to favorites';
                    }
                } else {
                    alert(data.message);
                }
            });
        }
    </script>

    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
