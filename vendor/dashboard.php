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

    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';
    require_once $root . '/config/database.php';
    $vendor_id = $_SESSION['user_id'];


    include $root . '/includes/vendor_header.php'; 
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-content">
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
                </div>
                
                <div class="insight-card">
                    <h3>📈 Business Insights</h3>
                    <p>Your store is performing great! You have <strong class="insight-highlight">0 pending orders</strong> to process.</p>
                    <p style="margin-top: 0.5rem;">Pro Tip: Add high-quality product images to increase sales by up to 40%!</p>
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
