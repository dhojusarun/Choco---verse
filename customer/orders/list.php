<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$customer_id = $_SESSION['user_id'];

// Get filter from URL, default to 'all'
$status_filter = $_GET['status'] ?? 'all';

// Get counts for each status
$counts_stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count
    FROM orders
    WHERE customer_id = ?
    GROUP BY status
");
$counts_stmt->execute([$customer_id]);
$status_counts = $counts_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get total count
$total_stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE customer_id = ?
");
$total_stmt->execute([$customer_id]);
$total_count = $total_stmt->fetchColumn();

// Fetch customer's orders based on filter
$order_query = "
    SELECT o.*, 
           COUNT(DISTINCT oi.id) as item_count,
           GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.customer_id = ?
";

if ($status_filter !== 'all') {
    $order_query .= " AND o.status = ?";
}

$order_query .= "
    GROUP BY o.id
    ORDER BY o.created_at DESC
";

$orders_stmt = $pdo->prepare($order_query);
if ($status_filter !== 'all') {
    $orders_stmt->execute([$customer_id, $status_filter]);
} else {
    $orders_stmt->execute([$customer_id]);
}
$orders = $orders_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .orders-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .order-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition-smooth);
        }
        .order-card:hover {
            border-color: var(--gold);
            transform: translateY(-3px);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .order-id {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gold);
        }
        .order-date {
            opacity: 0.7;
        }
        .status-badge {
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        .status-pending { background: rgba(255, 193, 7, 0.2); color: #FFE082; }
        .status-processing { background: rgba(33, 150, 243, 0.2); color: #90CAF9; }
        .status-shipped { background: rgba(156, 39, 176, 0.2); color: #CE93D8; }
        .status-delivered { background: rgba(76, 175, 80, 0.2); color: #A5D6A7; }
        .status-cancelled { background: rgba(244, 67, 54, 0.2); color: #FFCDD2; }
        .order-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        .order-amount {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gold);
            text-align: right;
        }
        .status-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .tab {
            padding: 0.8rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--cream);
            text-decoration: none;
            transition: var(--transition-smooth);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        .tab:hover {
            border-color: var(--gold);
            background: rgba(255, 255, 255, 0.1);
        }
        .tab.active {
            background: var(--gradient-gold);
            color: var(--chocolate-dark);
            border-color: var(--gold);
        }
        .tab-count {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .tab.active .tab-count {
            background: rgba(0, 0, 0, 0.2);
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
                        <h1>📦 My Orders</h1>
                        <p>Track your chocolate deliveries and order history</p>
                    </div>
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
                
                <?php if (count($orders) > 0): ?>
                <div class="orders-grid">
                    <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                <div class="order-date">Placed on <?php echo date('F d, Y', strtotime($order['created_at'])); ?></div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                    if ($order['status'] === 'delivered') {
                                        echo 'Order is delivered ✅';
                                    } else {
                                        echo ucfirst($order['status']); 
                                    }
                                    ?>
                                </span>
                                <div style="font-size: 0.8rem; opacity: 0.7;">
                                    Payment: <?php echo strtoupper(str_replace('_', ' ', $order['payment_method'] ?? 'Online')); ?> 
                                    (<?php echo ucfirst(str_replace('_', ' ', $order['payment_sub_method'] ?? 'Wallet')); ?>)
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div>
                                <h4 style="color: var(--gold); margin-bottom: 0.5rem;">Items (<?php echo $order['item_count']; ?>):</h4>
                                <p style="color: var(--cream); opacity: 0.9;">
                                    <?php echo htmlspecialchars(substr($order['product_names'], 0, 100)); ?>
                                    <?php echo strlen($order['product_names']) > 100 ? '...' : ''; ?>
                                </p>
                                
                                <?php if ($order['shipping_address']): ?>
                                <div style="margin-top: 1rem;">
                                    <h4 style="color: var(--gold); margin-bottom: 0.5rem;">Shipping to:</h4>
                                    <p style="opacity: 0.8;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <div style="text-align: right; opacity: 0.7; margin-bottom: 0.5rem;">Total</div>
                                <div class="order-amount">$<?php echo number_format($order['total_amount'], 2); ?></div>
                                
                                <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                                <button onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                                        class="btn btn-secondary" 
                                        style="margin-top: 1rem; padding: 0.5rem 1rem; font-size: 0.9rem; background: rgba(244, 67, 54, 0.2); border: 1px solid rgba(244, 67, 54, 0.3); color: #FFCDD2;">
                                    Cancel Order
                                </button>
                                <?php endif; ?>

                                <?php if ($order['status'] === 'delivered'): ?>
                                <a href="../reviews/add.php?order_id=<?php echo $order['id']; ?>" 
                                   class="btn btn-primary" 
                                   style="margin-top: 1rem; padding: 0.5rem 1rem; font-size: 0.9rem; display: inline-block; width: fit-content; margin-left: auto;">
                                    ⭐ Review Items
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 4rem 2rem; opacity: 0.7;">
                    <h2 style="font-size: 3rem;">📦</h2>
                    <h3>No Orders Yet</h3>
                    <p>Start shopping and your orders will appear here!</p>
                    <a href="../products/browse.php" class="btn btn-primary" style="margin-top: 2rem;">Browse Products</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function cancelOrder(orderId) {
            if (!confirm('Are you sure you want to cancel this order? Stock will be restored and payment will be refunded.')) {
                return;
            }
            
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = 'Cancelling...';
            
            fetch('cancel.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = 'Cancel Order';
                }
            })
            .catch(error => {
                alert('Error: ' + error);
                btn.disabled = false;
                btn.innerHTML = 'Cancel Order';
            });
        }
    </script>
</body>
</html>
