<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];

// Fetch all reviews for vendor's products
$reviews_stmt = $pdo->prepare("
    SELECT r.*, p.name as product_name, p.image_url, u.username as customer_name,
           p.id as product_id
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.customer_id = u.id
    WHERE p.vendor_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$vendor_id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate rating statistics
$total_reviews = count($reviews);
$avg_rating = 0;
$rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

if ($total_reviews > 0) {
    $sum = 0;
    foreach ($reviews as $review) {
        $sum += $review['rating'];
        $rating_counts[$review['rating']]++;
    }
    $avg_rating = $sum / $total_reviews;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews & Ratings - Choco World</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
</head>

    <?php
    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';

     include $root . '/includes/vendor_header.php'; ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>⭐ Reviews & Ratings</h1>
                    <p>Customer feedback on your products</p>
                </div>
                <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
            
            <div class="dashboard-content">
                <div class="reviews-summary">
                    <div class="rating-overview">
                        <div class="avg-rating"><?php echo number_format($avg_rating, 1); ?></div>
                        <div class="stars">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= round($avg_rating) ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <p style="margin-top: 1rem; opacity: 0.8;">Based on <?php echo $total_reviews; ?> reviews</p>
                    </div>
                    
                    <div class="rating-distribution">
                        <h3 style="color: var(--gold); margin-bottom: 1.5rem;">Rating Distribution</h3>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="rating-bar">
                            <span style="width: 80px;"><?php echo $i; ?> Stars</span>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: <?php echo $total_reviews > 0 ? ($rating_counts[$i] / $total_reviews * 100) : 0; ?>%;"></div>
                            </div>
                            <span style="width: 50px; text-align: right;"><?php echo $rating_counts[$i]; ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <h3 style="color: var(--gold); margin-bottom: 1.5rem;">Customer Reviews</h3>
                
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="product-info">
                                <img src="../../<?php echo htmlspecialchars($review['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($review['product_name']); ?>"
                                     class="product-thumb"
                                     onerror="this.src='../../images/products/default-chocolate.jpg'">
                                <div>
                                    <strong style="color: var(--gold);"><?php echo htmlspecialchars($review['product_name']); ?></strong>
                                    <br>
                                    <small style="opacity: 0.7;">by <?php echo htmlspecialchars($review['customer_name']); ?></small>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="review-stars">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $review['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                </div>
                                <small style="opacity: 0.7;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php if ($review['comment']): ?>
                        <p style="color: var(--cream); line-height: 1.6; margin-top: 1rem;">
                            "<?php echo htmlspecialchars($review['comment']); ?>"
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; opacity: 0.7;">
                    <h3>No Reviews Yet</h3>
                    <p>Customer reviews will appear here once they rate your products.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
