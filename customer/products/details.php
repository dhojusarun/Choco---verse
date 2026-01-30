<?php
session_start();
// Optional login check - allow guests to browse
$customer_id = $_SESSION['user_id'] ?? 0;
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
           CASE WHEN ? > 0 THEN EXISTS(SELECT 1 FROM favorites f WHERE f.product_id = p.id AND f.customer_id = ?) ELSE 0 END as is_favorite,
           CASE WHEN ? > 0 THEN EXISTS(SELECT 1 FROM cart c WHERE c.product_id = p.id AND c.customer_id = ?) ELSE 0 END as in_cart,
           CASE WHEN ? > 0 THEN EXISTS(
               SELECT 1 FROM orders o 
               JOIN order_items oi ON o.id = oi.order_id 
               WHERE o.customer_id = ? AND oi.product_id = p.id
           ) ELSE 0 END as has_purchased,
           CASE WHEN ? > 0 THEN EXISTS(
               SELECT 1 FROM reviews r 
               WHERE r.product_id = p.id AND r.customer_id = ?
           ) ELSE 0 END as has_reviewed
    FROM products p
    JOIN users u ON p.vendor_id = u.id
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.id = ? AND p.is_active = 1
    GROUP BY p.id
");
$product_stmt->execute([
    $customer_id, $customer_id, 
    $customer_id, $customer_id, 
    $customer_id, $customer_id,
    $customer_id, $customer_id,
    $product_id
]);
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

// Handle single product review submission
$success_msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inline_review'])) {
    $rating = $_POST['rating'] ?? 0;
    $comment = $_POST['comment'] ?? '';
    
    if ($product['has_purchased'] && !$product['has_reviewed'] && $rating >= 1 && $rating <= 5) {
        try {
            $insert_stmt = $pdo->prepare("
                INSERT INTO reviews (product_id, customer_id, rating, comment)
                VALUES (?, ?, ?, ?)
            ");
            $insert_stmt->execute([$product_id, $customer_id, $rating, $comment]);
            $success_msg = "Thank you! Your review has been posted successfully. 🍫";
            
            // Refresh product data to show updated review count/avg
            header("Location: details.php?id=$product_id&review_success=1#reviews");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Error submitting review: " . $e->getMessage();
        }
    }
}
if (isset($_GET['review_success'])) {
    $success_msg = "Thank you! Your review has been posted successfully. 🍫";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Choco World</title>
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
                        <span>
                            <?php echo number_format($product['avg_rating'], 1); ?> 
                            (<a href="#reviews" style="color: var(--gold); text-decoration: underline;"><?php echo $product['review_count']; ?> reviews</a>) 
                            
                            <?php if ($is_logged_in): ?>
                                <?php if (!$product['has_reviewed']): ?>
                                    • <a href="javascript:void(0)" 
                                         onclick="<?php echo $product['has_purchased'] ? 'toggleReviewForm()' : "alert('Only customers who have purchased this artisan chocolate can leave a verified review. Order now to share your experience!')"; ?>" 
                                         style="color: var(--gold); font-weight: 600;">Post your review</a>
                                <?php endif; ?>
                            <?php else: ?>
                                • <a href="../login.php" style="color: var(--gold); font-weight: 600;">Login to review</a>
                            <?php endif; ?>
                        </span>
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

            <div class="reviews-section" id="reviews">
                <div class="reviews-header">
                    <h2>Customer Reviews</h2>
                    <div class="avg-large" style="font-size: 1.5rem; color: var(--gold);">
                        Average Rating: <?php echo number_format($product['avg_rating'], 1); ?> / 5.0
                    </div>
                </div>
                
                <!-- Review Status Debug -->
                <!-- User ID: <?php echo var_export($customer_id, true); ?> -->
                <!-- Is Logged In: <?php echo var_export($is_logged_in, true); ?> -->
                <!-- has_purchased: <?php echo var_export($product['has_purchased'] ?? 'N/A', true); ?> -->
                <!-- has_reviewed: <?php echo var_export($product['has_reviewed'] ?? 'N/A', true); ?> -->


                <?php if ($success_msg): ?>
                    <div style="background: rgba(76, 175, 80, 0.2); color: #A5D6A7; padding: 1.2rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid rgba(76, 175, 80, 0.3); text-align: center;">
                        ✅ <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_msg): ?>
                    <div style="background: rgba(244, 67, 54, 0.2); color: #FFCDD2; padding: 1.2rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid rgba(244, 67, 54, 0.3); text-align: center;">
                        ❌ <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if ($product['has_purchased'] && !$product['has_reviewed']): ?>
                    <div id="review-prompt" style="background: rgba(212, 175, 55, 0.1); border: 1px solid var(--gold); padding: 1.5rem; border-radius: 15px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3 style="color: var(--gold); margin-bottom: 0.3rem;">Enjoyed this chocolate? 🍫</h3>
                            <p style="opacity: 0.8;">Share your experience with others and help them choose their next treat!</p>
                        </div>
                        <button onclick="toggleReviewForm()" class="btn btn-primary">
                            ⭐ Write a Review
                        </button>
                    </div>

                    <div id="inline-review-form" style="display: none; background: rgba(45, 26, 12, 0.6); backdrop-filter: blur(10px); border: 1px solid var(--gold); padding: 2.5rem; border-radius: 20px; margin-bottom: 3rem; animation: slideDown 0.4s ease-out;">
                        <h3 style="color: var(--gold); margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.8rem;">Your Experience Matters ✍️</h3>
                        <form method="POST">
                            <div style="margin-bottom: 2rem;">
                                <label style="display: block; color: var(--gold); margin-bottom: 0.8rem; font-weight: 600;">Your Rating:</label>
                                <div class="rating-select" style="font-size: 2.5rem; display: flex; gap: 0.5rem; flex-direction: row-reverse; justify-content: flex-end;">
                                    <input type="radio" name="rating" value="5" id="star5" class="star-radio" required style="display: none;">
                                    <label for="star5" class="star-label" style="cursor: pointer; color: rgba(255,255,255,0.2); transition: 0.2s;">★</label>
                                    
                                    <input type="radio" name="rating" value="4" id="star4" class="star-radio" style="display: none;">
                                    <label for="star4" class="star-label" style="cursor: pointer; color: rgba(255,255,255,0.2); transition: 0.2s;">★</label>
                                    
                                    <input type="radio" name="rating" value="3" id="star3" class="star-radio" style="display: none;">
                                    <label for="star3" class="star-label" style="cursor: pointer; color: rgba(255,255,255,0.2); transition: 0.2s;">★</label>
                                    
                                    <input type="radio" name="rating" value="2" id="star2" class="star-radio" style="display: none;">
                                    <label for="star2" class="star-label" style="cursor: pointer; color: rgba(255,255,255,0.2); transition: 0.2s;">★</label>
                                    
                                    <input type="radio" name="rating" value="1" id="star1" class="star-radio" style="display: none;">
                                    <label for="star1" class="star-label" style="cursor: pointer; color: rgba(255,255,255,0.2); transition: 0.2s;">★</label>
                                </div>
                                <style>
                                    .star-radio:checked ~ .star-label,
                                    .star-label:hover,
                                    .star-label:hover ~ .star-label {
                                        color: var(--gold) !important;
                                    }
                                    @keyframes slideDown {
                                        from { opacity: 0; transform: translateY(-20px); }
                                        to { opacity: 1; transform: translateY(0); }
                                    }
                                </style>
                            </div>

                            <div style="margin-bottom: 2rem;">
                                <label style="display: block; color: var(--gold); margin-bottom: 0.8rem; font-weight: 600;">What did you think?</label>
                                <textarea name="comment" class="form-control" placeholder="Describe the flavors, texture, and how it made you feel..." style="min-height: 150px; background: rgba(0,0,0,0.3); font-size: 1.1rem;" required></textarea>
                            </div>

                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" name="submit_inline_review" class="btn btn-primary" style="padding: 1rem 3rem; border-radius: 50px; font-weight: 600;">
                                    Publish Review Now 🍫
                                </button>
                                <button type="button" onclick="toggleReviewForm()" class="btn btn-secondary" style="padding: 1rem 2rem; border-radius: 50px;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

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

        function toggleReviewForm() {
            const form = document.getElementById('inline-review-form');
            const prompt = document.getElementById('review-prompt');
            if (form.style.display === 'none') {
                form.style.display = 'block';
                prompt.style.display = 'none';
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                form.style.display = 'none';
                prompt.style.display = 'flex';
            }
        }
    </script>

    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
