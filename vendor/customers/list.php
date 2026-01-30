<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];

// Fetch unique customers who have ordered from this vendor
$query = "
    SELECT 
        u.id, 
        u.username, 
        u.email, 
        COUNT(DISTINCT o.id) as total_orders,
        SUM(oi.subtotal) as total_spent,
        MAX(o.created_at) as last_order_date
    FROM users u
    JOIN orders o ON u.id = o.customer_id
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.vendor_id = ?
    GROUP BY u.id
    ORDER BY last_order_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$vendor_id]);
$customers = $stmt->fetchAll();

// Get total customer count
$total_customers = count($customers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer List - Choco World</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body>
    <?php
    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';
    include $root . '/includes/vendor_header.php'; 
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>👥 My Customers</h1>
                    <p>Manage and view information of customers who purchased from you</p>
                </div>
                <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
            
            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Unique Customers</div>
                        <div class="stat-value"><?php echo $total_customers; ?></div>
                    </div>
                </div>
                
                <?php if ($total_customers > 0): ?>
                <div class="order-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                                <th>Last Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($customer['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo $customer['total_orders']; ?></td>
                                <td><strong style="color: var(--gold);">$<?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($customer['last_order_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--cream); opacity: 0.7;">
                    <h3>No Customers Yet</h3>
                    <p>Customers will appear here once they purchase your products.</p>
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
