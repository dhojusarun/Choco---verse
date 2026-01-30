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
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
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
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-card" style="display: flex; flex-direction: column; height: 100%;">
                        <div style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(212, 175, 55, 0.1); padding-bottom: 1.5rem;">
                            <a href="../products/details.php?id=<?php echo $review['product_id']; ?>">
                                <img src="../../<?php echo htmlspecialchars($review['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(212, 175, 55, 0.2);"
                                     onerror="this.src='../../images/products/default-chocolate.jpg'">
                            </a>
                            <div style="flex: 1;">
                                <a href="../products/details.php?id=<?php echo $review['product_id']; ?>" style="text-decoration: none;">
                                    <h3 style="color: var(--gold); font-size: 1.2rem; margin-bottom: 0.3rem;"><?php echo htmlspecialchars($review['product_name']); ?></h3>
                                </a>
                                <div style="font-size: 0.85rem; opacity: 0.6; margin-bottom: 0.5rem;">
                                    📅 <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </div>
                                <div class="review-stars">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="flex-grow: 1;">
                            <p style="font-style: italic; line-height: 1.6; color: var(--cream); opacity: 0.9;">
                                "<?php echo htmlspecialchars($review['comment']); ?>"
                            </p>
                        </div>
                        
                        <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                            <a href="../products/details.php?id=<?php echo $review['product_id']; ?>" class="action-btn" style="color: var(--gold); border: 1px solid var(--gold); font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                                View Product Details
                            </a>
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
