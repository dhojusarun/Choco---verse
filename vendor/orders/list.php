<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];

// Get filter from URL, default to 'all'
$status_filter = $_GET['status'] ?? 'all';

// Get counts for each status
$counts_stmt = $pdo->prepare("
    SELECT 
        o.status,
        COUNT(DISTINCT o.id) as count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.vendor_id = ?
    GROUP BY o.status
");
$counts_stmt->execute([$vendor_id]);
$status_counts = $counts_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Add total count
$total_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id)
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.vendor_id = ?
");
$total_stmt->execute([$vendor_id]);
$total_count = $total_stmt->fetchColumn();

// Fetch orders based on filter
$order_query = "
    SELECT DISTINCT o.*, u.username as customer_name, u.email as customer_email,
           GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as product_names,
           SUM(oi.subtotal) as vendor_total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.customer_id = u.id
    WHERE oi.vendor_id = ?
";

if ($status_filter !== 'all') {
    $order_query .= " AND o.status = ?";
}

$order_query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($order_query);
if ($status_filter !== 'all') {
    $stmt->execute([$vendor_id, $status_filter]);
} else {
    $stmt->execute([$vendor_id]);
}
$orders = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        SUM(oi.subtotal) as total_revenue,
        SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.vendor_id = ? AND o.status != 'cancelled'
");
$stats_stmt->execute([$vendor_id]);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Choco World</title>
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
                    <h1>📦 Orders Management</h1>
                    <p>View and manage customer orders</p>
                </div>
                <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
            
            <div class="dashboard-content">
                <!-- Status Filter Tabs -->
                <div class="status-tabs">
                    <a href="?status=all" class="tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        📋 All Orders
                        <span class="tab-count"><?php echo $total_count; ?></span>
                    </a>
                    <a href="?status=pending" class="tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                        ⏳ Pending
                        <span class="tab-count"><?php echo $status_counts['pending'] ?? 0; ?></span>
                    </a>
                    <a href="?status=processing" class="tab <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">
                        🔄 Processing
                        <span class="tab-count"><?php echo $status_counts['processing'] ?? 0; ?></span>
                    </a>
                    <a href="?status=shipped" class="tab <?php echo $status_filter === 'shipped' ? 'active' : ''; ?>">
                        🚚 Shipped
                        <span class="tab-count"><?php echo $status_counts['shipped'] ?? 0; ?></span>
                    </a>
                    <a href="?status=delivered" class="tab <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">
                        ✅ Delivered
                        <span class="tab-count"><?php echo $status_counts['delivered'] ?? 0; ?></span>
                    </a>
                    <a href="?status=cancelled" class="tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                        ❌ Cancelled
                        <span class="tab-count"><?php echo $status_counts['cancelled'] ?? 0; ?></span>
                    </a>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Pending Orders</div>
                        <div class="stat-value"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Delivered</div>
                        <div class="stat-value"><?php echo $stats['delivered_orders'] ?? 0; ?></div>
                    </div>
                </div>
                
                <?php if (count($orders) > 0): ?>
                <div class="order-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Products</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                    <br><small style="opacity: 0.7;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars(substr($order['product_names'], 0, 40)); ?>...</small>
                                </td>
                                <td><strong style="color: var(--gold);">$<?php echo number_format($order['vendor_total'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="details.php?id=<?php echo $order['id']; ?>" class="action-btn">View Details</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: var(--cream); opacity: 0.7;">
                    <h3>No Orders Yet</h3>
                    <p>Orders will appear here once customers purchase your products.</p>
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
