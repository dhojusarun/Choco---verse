<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$customer_id = $_SESSION['user_id'];

// Fetch user's reviews
$reviews_stmt = $pdo->prepare("
    SELECT r.*, p.name as product_name, p.image_url, p.id as product_id
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$customer_id]);
$reviews = $reviews_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Choco World</title>
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

        .review-date {
            opacity: 0.5;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .review-rating {
            color: var(--gold);
            margin-bottom: 0.8rem;
        }

        .review-comment {
            line-height: 1.6;
            opacity: 0.9;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .product-image {
                height: 180px;
            }
            .product-info {
                padding: 1rem;
            }
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
                        <h1>⭐ My Reviews</h1>
                        <p>A history of your chocolate experiences</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-content">
                <?php if (count($reviews) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($reviews as $review): ?>
                    <div class="product-card">
                        <a href="../products/details.php?id=<?php echo $review['product_id']; ?>">
                            <img src="../../<?php echo htmlspecialchars($review['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                 class="product-image"
                                 onerror="this.src='../../images/products/default-chocolate.jpg'">
                        </a>
                        
                        <div class="product-info">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <a href="../products/details.php?id=<?php echo $review['product_id']; ?>" style="text-decoration: none;">
                                    <h3 class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></h3>
                                </a>
                            </div>
                            
                            <div class="review-date">Reviewed on <?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                            
                            <div class="review-rating">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['rating'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                            
                            <p class="review-comment">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 4rem 2rem; opacity: 0.7;">
                    <h2 style="font-size: 3rem;">✍️</h2>
                    <h3>No reviews yet</h3>
                    <p>Share your thoughts on chocolates you've purchased!</p>
                    <a href="../orders/list.php" class="btn btn-primary" style="margin-top: 2rem;">View My Orders</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
