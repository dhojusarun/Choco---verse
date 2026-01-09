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
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 2.5rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .wallet-card {
            background: var(--gradient-gold);
            color: var(--chocolate-dark);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .wallet-amount {
            font-size: 3rem;
            font-weight: 700;
            margin: 1rem 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-label {
            color: var(--gold-light);
            font-weight: 500;
        }

        .info-value {
            color: var(--cream);
            font-weight: 600;
        }
    </style>
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
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">💰</div>
                    <h2 style="margin: 0; font-size: 1.2rem;">Wallet Balance</h2>
                    <div class="wallet-amount">$<?php echo number_format($user['wallet_balance'], 2); ?></div>
                    <p style="margin: 0; opacity: 0.8;">
                        Available for purchases and refunds
                    </p>
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
