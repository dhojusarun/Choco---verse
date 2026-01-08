<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$customer_id = $_SESSION['user_id'];

// Fetch favorited products
$products_stmt = $pdo->prepare("
    SELECT p.*, u.username as vendor_name,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(DISTINCT r.id) as review_count,
           1 as is_favorite,
           EXISTS(SELECT 1 FROM cart c WHERE c.product_id = p.id AND c.customer_id = ?) as in_cart
    FROM favorites f
    JOIN products p ON f.product_id = p.id
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE f.customer_id = ? AND p.is_active = 1
    GROUP BY p.id
    ORDER BY f.created_at DESC
");
$products_stmt->execute([$customer_id, $customer_id]);
$products = $products_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .product-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition-smooth);
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.3);
            border-color: var(--gold);
        }
        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        .product-info {
            padding: 1.5rem;
        }
        .product-name {
            color: var(--gold);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .vendor-name {
            color: var(--cream);
            opacity: 0.7;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .product-rating {
            color: var(--gold);
            margin-bottom: 0.8rem;
        }
        .product-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--gold);
            margin: 1rem 0;
        }
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-small {
            padding: 0.7rem 1.2rem;
            font-size: 0.9rem;
            flex: 1;
        }
        .favorite-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(244, 67, 54, 0.8);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            backdrop-filter: blur(10px);
        }
        .favorite-btn:hover {
            transform: scale(1.1);
            background: rgba(244, 67, 54, 1);
        }
    </style>
</head>
<body>
    <?php 
    include __DIR__ . '/../../includes/customer_header.php'; 
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-content">
                <div class="dashboard-header" style="border-bottom: none; margin-bottom: 1rem;">
                    <div class="dashboard-title">
                        <h1>❤️ My Favorites</h1>
                        <p>Your handpicked selection of delicious chocolates</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-content">
                <?php if (count($products) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card" id="product-<?php echo $product['id']; ?>">
                        <button class="favorite-btn" 
                                onclick="toggleFavorite(<?php echo $product['id']; ?>, this)"
                                title="Remove from favorites">
                            ❤️
                        </button>
                        
                        <a href="../products/details.php?id=<?php echo $product['id']; ?>">
                            <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-image"
                                 onerror="this.src='../../images/products/default-chocolate.jpg'">
                        </a>
                        
                        <div class="product-info">
                            <a href="../products/details.php?id=<?php echo $product['id']; ?>" style="text-decoration: none;">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            </a>
                            <div class="vendor-name">by <?php echo htmlspecialchars($product['vendor_name']); ?></div>
                            
                            <div class="product-rating">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= round($product['avg_rating']) ? '★' : '☆';
                                }
                                ?>
                                <?php echo number_format($product['avg_rating'], 1); ?>
                                <small>(<?php echo $product['review_count']; ?>)</small>
                            </div>
                            
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            
                            <div class="product-actions">
                                <?php if ($product['in_cart']): ?>
                                    <button class="btn btn-secondary btn-small" disabled>✓ In Cart</button>
                                <?php else: ?>
                                    <button class="btn btn-primary btn-small" onclick="addToCart(<?php echo $product['id']; ?>, this)">
                                        🛒 Add to Cart
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 4rem 2rem; opacity: 0.7;">
                    <h2 style="font-size: 3rem;">💔</h2>
                    <h3>No favorites yet</h3>
                    <p>Start hearting products to see them here!</p>
                    <a href="../products/browse.php" class="btn btn-primary" style="margin-top: 2rem;">Browse Products</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function addToCart(productId, btn) {
            fetch('../products/add-to-cart.php', {
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
                    if (typeof updateCartCount === 'function') updateCartCount();
                } else {
                    alert(data.message);
                }
            });
        }
        
        function toggleFavorite(productId, btn) {
            fetch('../favorites/toggle.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // In the favorites list, we just remove the card if unfavorited
                    const card = document.getElementById('product-' + productId);
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        card.remove();
                        if (document.querySelectorAll('.product-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    alert(data.message);
                }
            });
        }
    </script>

    <?php 
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
