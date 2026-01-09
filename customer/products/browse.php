<?php
session_start();
// Optional login check - allow guests to browse
$customer_id = $_SESSION['user_id'] ?? null;
$is_logged_in = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer';

require_once '../../config/database.php';



// Fetch all active products with vendor info and ratings
$products_stmt = $pdo->prepare("
    SELECT p.*, u.username as vendor_name,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(DISTINCT r.id) as review_count,
           CASE WHEN ? IS NOT NULL THEN EXISTS(SELECT 1 FROM favorites f WHERE f.product_id = p.id AND f.customer_id = ?) ELSE 0 END as is_favorite,
           CASE WHEN ? IS NOT NULL THEN EXISTS(SELECT 1 FROM cart c WHERE c.product_id = p.id AND c.customer_id = ?) ELSE 0 END as in_cart
    FROM products p
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.is_active = 1 AND p.stock > 0
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$products_stmt->execute([$customer_id, $customer_id, $customer_id, $customer_id]);
$products = $products_stmt->fetchAll();

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

        .product-description {
            color: var(--cream);
            opacity: 0.8;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
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
            background: rgba(0, 0, 0, 0.5);
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
        }

        .favorite-btn.active {
            background: rgba(244, 67, 54, 0.8);
        }

        .cart-indicator {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--gradient-gold);
            color: var(--chocolate-dark);
            padding: 1rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            z-index: 100;
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
            text-decoration: none;
        }

        .stock-badge {
            background: rgba(76, 175, 80, 0.2);
            color: #A5D6A7;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            display: inline-block;
        }
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 1.5rem;
                padding: 0 1rem;
            }

            .product-image {
                height: 180px;
            }

            .product-info {
                padding: 1.2rem;
            }

            .product-price {
                font-size: 1.5rem;
            }

            .cart-indicator {
                bottom: 2rem;
                top: auto;
                right: 1.5rem;
                padding: 0.8rem 1.2rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                            
                            <p class="product-description">
                                <?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...
                            </p>
                            
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            
                            <div style="margin-bottom: 1rem;">
                                <span class="stock-badge">In Stock: <?php echo $product['stock']; ?> units</span>
                            </div>
                            
                            <div class="product-actions">
                                <?php if ($product['in_cart']): ?>
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
