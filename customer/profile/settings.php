<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$customer_id = $_SESSION['user_id'];

// Fetch user data
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$customer_id]);
$user = $user_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Choco World</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
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
                        <h1>👤 My Profile</h1>
                        <p>Manage your account settings and chocolate preferences</p>
                    </div>
                </div>
            
            <div class="profile-container">
                <div class="wallet-card">
                    <div class="wallet-icon">💰</div>
                    <div class="wallet-info">
                        <h2 class="wallet-title">Wallet Balance</h2>
                        <div class="wallet-amount">$<?php echo number_format($user['wallet_balance'], 2); ?></div>
                    </div>
                </div>
                
                <div class="profile-card">
                    <h2 style="color: var(--gold); margin-bottom: 1.5rem; border-bottom: 2px solid rgba(212, 175, 55, 0.3); padding-bottom: 0.8rem;">
                        Account Information
                    </h2>
                    
                    <div class="info-row">
                        <span class="info-label">Username:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Account Type:</span>
                        <span class="info-value" style="text-transform: capitalize;"><?php echo $user['role']; ?></span>
                    </div>
                    
                    <div class="info-row" style="border-bottom: none;">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="profile-card" style="background: rgba(212, 175, 55, 0.1); border: 2px solid var(--gold);">
                    <h3 style="color: var(--gold); margin-bottom: 1rem;">💡 About Your Wallet</h3>
                    <ul style="color: var(--cream); line-height: 2; list-style-position: inside;">
                        <li>Wallet balance is automatically credited when you cancel orders</li>
                        <li>Refunds are instant and available immediately</li>
                        <li>Use your wallet balance for future chocolate purchases</li>
                        <li>Track all your transactions in the orders section</li>
                    </ul>
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
