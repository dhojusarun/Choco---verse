<?php
session_start();

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - Choco World</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <?php 
    require_once dirname(__DIR__) . '/config/database.php';
    $vendor_id = $_SESSION['user_id'];

    // Stock stats
    $stock_stmt = $pdo->prepare("
        SELECT 
            SUM(stock) as total_stock,
            COUNT(CASE WHEN stock <= 5 AND stock > 0 THEN 1 END) as low_stock_count,
            COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock_count
        FROM products 
        WHERE vendor_id = ?
    ");
    $stock_stmt->execute([$vendor_id]);
    $stock_stats = $stock_stmt->fetch();

    // Pending orders
    $orders_stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.vendor_id = ? AND o.status = 'pending'
    ");
    $orders_stmt->execute([$vendor_id]);
    $pending_orders = $orders_stmt->fetchColumn();

    require_once dirname(__DIR__) . '/includes/vendor_header.php'; 
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header-alt">
                    <div class="vendor-welcome">
                        <h1>Welcome back, <?php echo htmlspecialchars($username); ?>! 💼</h1>
                        <p>Manage your artisan chocolate business and sweetness</p>
                    </div>
                </div>
            
            <div class="dashboard-content">
                <h2 class="section-title-gold">Your Business Dashboard</h2>
                
                <div class="dashboard-grid">
                    <a href="analytics/dashboard.php" class="dashboard-card-link dashboard-card">
                        <h3>📊 Sales Analytics</h3>
                        <p>Track your sales performance and revenue metrics in real-time.</p>
                    </a>
                    
                    <a href="products/list.php" class="dashboard-card-link dashboard-card">
                        <h3>🍫 Product Management</h3>
                        <p>Add, edit, and manage your chocolate product listings.</p>
                    </a>
                    
                    <a href="orders/list.php" class="dashboard-card-link dashboard-card">
                        <h3>📦 Orders</h3>
                        <p>View and process customer orders efficiently.</p>
                    </a>
                    
                    <a href="analytics/revenue.php" class="dashboard-card-link dashboard-card">
                        <h3>💰 Revenue</h3>
                        <p>Monitor your earnings and payment history.</p>
                    </a>
                    
                    <a href="reviews/list.php" class="dashboard-card-link dashboard-card">
                        <h3>⭐ Reviews & Ratings</h3>
                        <p>See what customers are saying about your products.</p>
                    </a>
                    
                    <a href="settings/profile.php" class="dashboard-card-link dashboard-card">
                        <h3>🏪 Store Settings</h3>
                        <p>Customize your vendor profile and business details.</p>
                    </a>

                    <a href="customers/list.php" class="dashboard-card-link dashboard-card">
                        <h3>👥 Customer List</h3>
                        <p>View the list of customers who have purchased from you.</p>
                    </a>
                </div>
                
                <div class="insight-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                    <div class="insight-card">
                        <h3>📈 Business Insights</h3>
                        <p>Your store is performing great! You have <strong class="insight-highlight"><?php echo $pending_orders; ?> pending orders</strong> to process.</p>
                        <p style="margin-top: 0.5rem;">Pro Tip: Add high-quality product images to increase sales by up to 40%!</p>
                    </div>

                    <div class="insight-card">
                        <h3>📦 Inventory Status</h3>
                        <p>You have a total of <strong class="insight-highlight"><?php echo (int)$stock_stats['total_stock']; ?> units</strong> across all products.</p>
                        <div style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.3rem;">
                            <?php if ($stock_stats['low_stock_count'] > 0): ?>
                                <p style="color: #FFB300;">⚠️ <strong><?php echo $stock_stats['low_stock_count']; ?> items</strong> are low on stock (under 5 units).</p>
                            <?php endif; ?>
                            <?php if ($stock_stats['out_of_stock_count'] > 0): ?>
                                <p style="color: #F44336;">🚨 <strong><?php echo $stock_stats['out_of_stock_count']; ?> items</strong> are out of stock!</p>
                            <?php else: ?>
                                <p style="color: #4CAF50;">✅ All products are currently in stock.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    $logo_path = '../images/logo.png';
    include '../includes/footer.php'; 
    ?>
</body>
</html>
