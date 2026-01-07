<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once '../config/database.php'; // Assuming db_connect.php sets up $pdo

$customer_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email']; // Keep email for now, though it's removed from display

// Fetch wallet balance
$wallet_stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$wallet_stmt->execute([$customer_id]);
$wallet_balance = $wallet_stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Choco World</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php 

    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';
    include $root . '/includes/customer_header.php'; 
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-content">
                <div class="dashboard-header" style="border-bottom: none; margin-bottom: 2rem;">
                    <div class="dashboard-title">
                        <h1>🍫 Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
                        <p>Explore our premium collections and manage your chocolate journey</p>
                    </div>
                </div>
            
            <div class="dashboard-content">
                <h2 style="font-family: var(--font-heading); color: var(--gold); margin-bottom: 2rem;">Your Chocolate Paradise</h2>
                
                <div class="dashboard-grid">
                    <a href="products/browse.php" class="dashboard-card" style="text-decoration: none; color: inherit;">
                        <h3>🛍️ Browse Products</h3>
                        <p>Explore our premium collection of handcrafted chocolates from artisan vendors around the world.</p>
                    </a>
                    
                    <a href="orders/list.php" class="dashboard-card" style="text-decoration: none; color: inherit;">
                        <h3>📦 My Orders</h3>
                        <p>Track your chocolate deliveries and view your order history.</p>
                    </a>
                    
                    <a href="favorites/list.php" class="dashboard-card" style="text-decoration: none; color: inherit;">
                        <h3>❤️ Favorites</h3>
                        <p>Save your favorite chocolate treats for quick access.</p>
                    </a>
                    
                    <a href="products/browse.php#gift-cards" class="dashboard-card" style="text-decoration: none; color: inherit;">
                        <h3>🎁 Gift Cards</h3>
                        <p>Send the gift of chocolate to your loved ones.</p>
                    </a>
                    
                    <a href="reviews/my-reviews.php" class="dashboard-card" style="text-decoration: none; color: inherit;">
                        <h3>⭐ Reviews</h3>
                        <p>Share your chocolate experiences with the community.</p>
                    </a>
                    
                    <a href="profile/settings.php" class="dashboard-card" style="text-decoration: none; color: inherit;">
                        <h3>👤 Profile</h3>
                        <p>Manage your account settings and preferences.</p>
                    </a>
                </div>
                
                <div style="margin-top: 3rem; padding: 2rem; background: rgba(212, 175, 55, 0.1); border-radius: 15px; border: 2px solid var(--gold);">
                    <h3 style="color: var(--gold); margin-bottom: 1rem;">🎉 Special Offer!</h3>
                    <p>Get 20% off on your first order! Use code: <strong style="color: var(--gold);">CHOCO20</strong></p>
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
