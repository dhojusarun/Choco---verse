<?php
session_start();
// Optional login check - allow guests to browse
$customer_id = $_SESSION['user_id'] ?? null;
$is_logged_in = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer';

require_once '../../config/database.php';

$product_id = $_GET['id'] ?? 0;

if (!$product_id) {
    header('Location: browse.php');
    exit;
}

// Fetch product details with vendor info
$product_stmt = $pdo->prepare("
    SELECT p.*, u.username as vendor_name,
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(DISTINCT r.id) as review_count,
           CASE WHEN ? IS NOT NULL THEN EXISTS(SELECT 1 FROM favorites f WHERE f.product_id = p.id AND f.customer_id = ?) ELSE 0 END as is_favorite,
           CASE WHEN ? IS NOT NULL THEN EXISTS(SELECT 1 FROM cart c WHERE c.product_id = p.id AND c.customer_id = ?) ELSE 0 END as in_cart
    FROM products p
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.id = ? AND p.is_active = 1
    GROUP BY p.id
");
$product_stmt->execute([$customer_id, $customer_id, $customer_id, $customer_id, $product_id]);
$product = $product_stmt->fetch();

if (!$product) {
    die("Product not found or inactive.");
}

// Fetch reviews
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.username as customer_name
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll();

// Get cart count for header badge
$cart_count = 0;
if ($is_logged_in) {
    $cart_count_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE customer_id = ?");
    $cart_count_stmt->execute([$customer_id]);
    $cart_count = (int)$cart_count_stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .product-details-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-top: 2rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 3rem;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .details-image-section {
            position: relative;
        }

        .main-product-image {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            border: 2px solid rgba(212, 175, 55, 0.2);
        }

        .details-favorite-btn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 2rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .details-favorite-btn:hover {
            transform: scale(1.1);
        }

        .details-favorite-btn.active {
            background: rgba(244, 67, 54, 0.8);
        }

        .details-info-section h1 {
            font-size: 3rem;
            color: var(--gold);
            margin-bottom: 0.5rem;
        }

        .details-vendor {
            font-size: 1.2rem;
            opacity: 0.7;
            margin-bottom: 1.5rem;
        }

        .details-rating {
            font-size: 1.2rem;
            color: var(--gold);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .details-price {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 2rem;
        }

        .details-description {
            font-size: 1.1rem;
            line-height: 1.8;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            color: var(--cream);
        }

        .details-stock {
            margin-bottom: 2rem;
        }

        .reviews-section {
            margin-top: 5rem;
        }

        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            border-bottom: 2px solid var(--gold);
            padding-bottom: 1rem;
        }

        .review-card {
            background: rgba(255, 255, 255, 0.03);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .review-user-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .review-user {
            font-weight: 600;
            color: var(--gold);
        }

        .review-date {
            opacity: 0.5;
            font-size: 0.9rem;
        }

        .review-comment {
            line-height: 1.6;
            opacity: 0.9;
        }

        @media (max-width: 968px) {
            .product-details-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem;
            }

            .details-info-section h1 {
                font-size: 2.5rem;
            }

            .details-price {
                font-size: 2.8rem;
            }
        }

        @media (max-width: 768px) {
            .product-details-container {
                padding: 1.5rem;
                border-radius: 20px;
            }

            .details-info-section h1 {
                font-size: 2rem;
            }

            .details-description {
                font-size: 1rem;
            }

            .details-actions {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .btn-large {
                width: 100%;
            }

            .reviews-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
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
        $customer_id = 0;
        $wallet_balance = 0;
        $username = "Guest";
        include $root . '/includes/customer_header.php';
    }
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header" style="border-bottom: none; margin-bottom: 1rem;">
                <a href="browse.php" class="back-link">← Back to Products</a>
            </div>

            <div class="product-details-container">
                <div class="details-image-section">
                    <button class="details-favorite-btn <?php echo $product['is_favorite'] ? 'active' : ''; ?>" 
                            onclick="toggleFavorite(<?php echo $product['id']; ?>, this)"
                            title="<?php echo $product['is_favorite'] ? 'Remove from favorites' : 'Add to favorites'; ?>">
                        <?php echo $product['is_favorite'] ? '❤️' : '🤍'; ?>
                    </button>
                    <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="main-product-image"
                         onerror="this.src='../../images/products/default-chocolate.jpg'">
                </div>

                <div class="details-info-section">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="details-vendor">by Artisan Vendor: <strong><?php echo htmlspecialchars($product['vendor_name']); ?></strong></div>
                    
                    <div class="details-rating">
                        <div class="stars">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= round($product['avg_rating']) ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <span><?php echo number_format($product['avg_rating'], 1); ?> (<?php echo $product['review_count']; ?> reviews)</span>
                    </div>

                    <div class="details-price">$<?php echo number_format($product['price'], 2); ?></div>

                    <div class="details-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>

                    <div class="details-stock">
                        <?php if ($product['stock'] > 0): ?>
                            <span class="stock-badge">✅ In Stock: <?php echo $product['stock']; ?> units available</span>
                        <?php else: ?>
                            <span class="stock-badge out-of-stock">❌ Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="details-actions">
                        <?php if ($product['in_cart']): ?>
                            <button class="btn btn-secondary btn-large" disabled>✓ Successfully in Cart</button>
                        <?php elseif ($product['stock'] > 0): ?>
                            <button class="btn btn-primary btn-large" onclick="addToCart(<?php echo $product['id']; ?>, this)">
                                🛒 Add to Shopping Cart
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-large" disabled>Temporarily Unavailable</button>
                        <?php endif; ?>
                        <a href="cart.php" class="btn btn-secondary btn-large">View Cart</a>
                    </div>
                </div>
            </div>

            <div class="reviews-section">
                <div class="reviews-header">
                    <h2>Customer Reviews</h2>
                    <div class="avg-large" style="font-size: 1.5rem; color: var(--gold);">
                        Average Rating: <?php echo number_format($product['avg_rating'], 1); ?> / 5.0
                    </div>
                </div>

                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-user-info">
                                <span class="review-user">👤 <?php echo htmlspecialchars($review['customer_name']); ?></span>
                                <span class="review-date"><?php echo date('F d, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div class="review-rating">
                                <?php for($i=1; $i<=5; $i++) echo $i <= $review['rating'] ? '★' : '☆'; ?>
                            </div>
                            <div class="review-comment">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 3.5rem; opacity: 0.6; background: rgba(255,255,255,0.02); border-radius: 20px;">
                        <p style="font-size: 1.2rem;">No reviews yet for this chocolate. Be the first to taste and share!</p>
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
                    btn.innerHTML = '✓ Successfully in Cart';
                    btn.disabled = true;
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-secondary');
                    // Update header cart badge if exists
                    if (typeof updateCartCount === 'function') updateCartCount();
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
