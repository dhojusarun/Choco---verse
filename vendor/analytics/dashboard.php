<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];

// Get overall statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT oi.order_id) as total_orders,
        SUM(oi.subtotal) as total_revenue,
        COUNT(DISTINCT p.id) as total_products,
        COALESCE(AVG(r.rating), 0) as avg_rating
    FROM order_items oi
    LEFT JOIN orders o ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id AND p.vendor_id = ?
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE oi.vendor_id = ? AND o.status != 'cancelled'
");
$stats_stmt->execute([$vendor_id, $vendor_id]);
$stats = $stats_stmt->fetch();

// Get top selling products
$top_products_stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.vendor_id = ? AND o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$top_products_stmt->execute([$vendor_id]);
$top_products = $top_products_stmt->fetchAll();

// Get order status distribution
$status_stmt = $pdo->prepare("
    SELECT o.status, COUNT(DISTINCT o.id) as count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.vendor_id = ?
    GROUP BY o.status
");
$status_stmt->execute([$vendor_id]);
$status_distribution = $status_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics - Choco World</title>
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
                    <h1>📊 Sales Analytics</h1>
                    <p>Track your business performance</p>
                </div>
                <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
            
            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-value">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🍫</div>
                        <div class="stat-value"><?php echo $stats['total_products'] ?? 0; ?></div>
                        <div class="stat-label">Products Listed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-value"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>

                
                <div class="top-products">
                    <h3 style="color: var(--gold); margin-bottom: 1.5rem;">🏆 Top Selling Products</h3>
                    <?php if (count($top_products) > 0): ?>
                        <?php foreach ($top_products as $index => $product): ?>
                        <div class="product-item">
                            <div>
                                <strong style="color: var(--gold);">#<?php echo $index + 1; ?></strong>
                                <strong style="margin-left: 1rem;"><?php echo htmlspecialchars($product['name']); ?></strong>
                                <br>
                                <small style="opacity: 0.7;"><?php echo $product['total_sold']; ?> units sold</small>
                            </div>
                            <div style="text-align: right;">
                                <strong style="color: var(--gold); font-size: 1.2rem;">$<?php echo number_format($product['revenue'], 2); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; opacity: 0.7;">No sales data available yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
