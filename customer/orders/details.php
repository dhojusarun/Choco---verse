<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$customer_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: list.php');
    exit;
}

// Fetch order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image_url, p.description as product_description, u.username as vendor_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON p.vendor_id = u.id
    WHERE oi.order_id = ?
");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Choco World</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        .order-details-card {
            background: rgba(45, 26, 12, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid rgba(212, 175, 55, 0.2);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            margin-bottom: 2rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2.5rem;
            margin-bottom: 3rem;
        }

        .detail-section h3 {
            color: var(--gold);
            font-size: 1.2rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            padding-bottom: 0.8rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
        }

        .detail-label {
            opacity: 0.7;
            color: var(--cream);
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .items-table th {
            text-align: left;
            padding: 1.2rem;
            border-bottom: 2px solid rgba(212, 175, 55, 0.2);
            color: var(--gold);
            font-weight: 600;
        }

        .items-table td {
            padding: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }

        .product-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .summary-box {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            max-width: 400px;
            margin-left: auto;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        @media screen and (max-width: 768px) {
            .order-details-card { padding: 1.5rem; }
            .items-table thead { display: none; }
            .items-table tr { display: block; margin-bottom: 1.5rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 1rem; }
            .items-table td { display: flex; justify-content: space-between; padding: 0.5rem 0; border: none; }
            .items-table td::before { content: attr(data-label); font-weight: bold; color: var(--gold); }
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
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>📦 Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h1>
                    <p>Detailed overview of your chocolate order</p>
                </div>
                <a href="list.php" class="btn btn-secondary">← Back to My Orders</a>
            </div>
            
            <div class="order-details-card">
                <div class="details-grid">
                    <div class="detail-section">
                        <h3>📋 Order Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Order Date:</span>
                            <span class="detail-value"><?php echo date('F d, Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Payment Method:</span>
                            <span class="detail-value">
                                <?php echo strtoupper(str_replace('_', ' ', $order['payment_method'] ?? 'Online')); ?> 
                                (<?php echo ucfirst(str_replace('_', ' ', $order['payment_sub_method'] ?? 'Wallet')); ?>)
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3>📍 Shipping Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value" style="text-align: right; max-width: 200px;">
                                <?php echo htmlspecialchars($order['shipping_address'] ?? 'Default shipping address'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <h3 style="color: var(--gold); margin-bottom: 1.5rem; border-bottom: 1px solid rgba(212, 175, 55, 0.1); padding-bottom: 0.8rem;">
                    🛒 Items Ordered (<?php echo $order['item_count']; ?>)
                </h3>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Vendor</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td data-label="Product">
                                <div style="display: flex; align-items: center; gap: 1.5rem;">
                                    <img src="../../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="product-img"
                                         onerror="this.src='../../images/products/default-chocolate.jpg'">
                                    <div>
                                        <div style="font-weight: 600; color: var(--cream); font-size: 1.1rem; margin-bottom: 0.3rem;">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </div>
                                        <?php if ($item['product_description']): ?>
                                            <p style="font-size: 0.85rem; opacity: 0.6; line-height: 1.4; color: var(--cream); max-width: 350px;">
                                                <?php echo htmlspecialchars($item['product_description']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Vendor">
                                <span style="opacity: 0.8;"><?php echo htmlspecialchars($item['vendor_name']); ?></span>
                            </td>
                            <td data-label="Price">$<?php echo number_format($item['price'], 2); ?></td>
                            <td data-label="Qty"><?php echo $item['quantity']; ?></td>
                            <td data-label="Subtotal"><strong style="color: var(--gold); font-size: 1.1rem;">$<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="summary-box">
                    <div class="detail-row">
                        <span class="detail-label">Items Subtotal:</span>
                        <span>$<?php echo number_format($order['total_amount'] / 1.1, 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tax (10%):</span>
                        <span>$<?php echo number_format($order['total_amount'] - ($order['total_amount'] / 1.1), 2); ?></span>
                    </div>
                    <div class="detail-row" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <strong style="font-size: 1.3rem;">Total Paid:</strong>
                        <strong style="color: var(--gold); font-size: 1.8rem;">$<?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                </div>

                <?php if ($order['status'] === 'delivered'): ?>
                    <div style="margin-top: 2rem; text-align: right;">
                        <a href="../reviews/add.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                            ⭐ Rate & Review Items
                        </a>
                    </div>
                <?php elseif ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                    <div style="margin-top: 2rem; text-align: right;">
                        <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="btn btn-secondary" style="background: rgba(244, 67, 54, 0.1); border-color: rgba(244, 67, 54, 0.3); color: #FFCDD2;">
                            Cancel Order
                        </button>
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
            
            fetch('cancel.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = 'list.php';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => alert('Error: ' + error));
        }
    </script>

    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
