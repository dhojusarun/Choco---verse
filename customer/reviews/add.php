<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$customer_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

// Verify order belongs to customer and is delivered
$order_stmt = $pdo->prepare("
    SELECT id FROM orders 
    WHERE id = ? AND customer_id = ? AND status = 'delivered'
");
$order_stmt->execute([$order_id, $customer_id]);
$order = $order_stmt->fetch();

if (!$order) {
    die("Error: This order cannot be reviewed or does not exist.");
}

// Fetch items in the order that haven't been reviewed yet
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_url, r.id as existing_review_id
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN reviews r ON p.id = r.product_id AND r.customer_id = ?
    WHERE oi.order_id = ?
");
$items_stmt->execute([$customer_id, $order_id]);
$items = $items_stmt->fetchAll();

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    try {
        // Double check purchase and existing review
        $check_stmt = $pdo->prepare("
            SELECT 1 FROM reviews WHERE product_id = ? AND customer_id = ?
        ");
        $check_stmt->execute([$product_id, $customer_id]);
        if ($check_stmt->fetch()) {
            $success_msg = "You have already reviewed this product.";
        } else {
            $insert_stmt = $pdo->prepare("
                INSERT INTO reviews (product_id, customer_id, rating, comment)
                VALUES (?, ?, ?, ?)
            ");
            $insert_stmt->execute([$product_id, $customer_id, $rating, $comment]);
            header("Location: add.php?order_id=$order_id&success=1");
            exit;
        }
    } catch (PDOException $e) {
        $error_msg = "Error submitting review: " . $e->getMessage();
    }
}

$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Items - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .review-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .product-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 15px;
        }

        .review-textarea {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--cream);
            padding: 1rem;
            min-height: 100px;
            resize: vertical;
        }

        .status-pill {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            background: rgba(76, 175, 80, 0.2);
            color: #A5D6A7;
            margin-bottom: 0.5rem;
        }

        .rating-select {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-radio {
            display: none;
        }

        .star-label {
            font-size: 2rem;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.2);
            transition: color 0.2s;
        }

        .star-radio:checked ~ .star-label,
        .star-label:hover,
        .star-label:hover ~ .star-label {
            color: var(--gold);
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
                        <h1>⭐ Rate & Review</h1>
                        <p>Tell us what you thought about your treats from Order #<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    <a href="../orders/list.php" class="btn btn-secondary">Back to Orders</a>
                </div>
            </div>

            <?php if ($success): ?>
            <div style="background: rgba(76, 175, 80, 0.2); color: #A5D6A7; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
                ✅ Your review has been submitted successfully!
            </div>
            <?php endif; ?>

            <div class="dashboard-content">
                <?php foreach ($items as $item): ?>
                <div class="review-card">
                    <img src="../../<?php echo htmlspecialchars($item['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         class="product-preview"
                         onerror="this.src='../../images/products/default-chocolate.jpg'">
                    
                    <div>
                        <h3 style="color: var(--gold); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($item['name']); ?></h3>
                        
                        <?php if ($item['existing_review_id']): ?>
                            <div class="status-pill">Already Reviewed</div>
                            <p style="opacity: 0.7;">You have already shared your feedback for this item.</p>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                
                                <div class="rating-select">
                                    <input type="radio" name="rating" value="5" id="star5-<?php echo $item['product_id']; ?>" class="star-radio" required>
                                    <label for="star5-<?php echo $item['product_id']; ?>" class="star-label">★</label>
                                    
                                    <input type="radio" name="rating" value="4" id="star4-<?php echo $item['product_id']; ?>" class="star-radio">
                                    <label for="star4-<?php echo $item['product_id']; ?>" class="star-label">★</label>
                                    
                                    <input type="radio" name="rating" value="3" id="star3-<?php echo $item['product_id']; ?>" class="star-radio">
                                    <label for="star3-<?php echo $item['product_id']; ?>" class="star-label">★</label>
                                    
                                    <input type="radio" name="rating" value="2" id="star2-<?php echo $item['product_id']; ?>" class="star-radio">
                                    <label for="star2-<?php echo $item['product_id']; ?>" class="star-label">★</label>
                                    
                                    <input type="radio" name="rating" value="1" id="star1-<?php echo $item['product_id']; ?>" class="star-radio">
                                    <label for="star1-<?php echo $item['product_id']; ?>" class="star-label">★</label>
                                </div>
                                
                                <textarea name="comment" class="review-textarea" placeholder="Share your experience with this chocolate..." required></textarea>
                                
                                <button type="submit" name="submit_review" class="btn btn-primary" style="margin-top: 1rem; padding: 0.8rem 2rem;">
                                    Post Review ✍️
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>
</html>
