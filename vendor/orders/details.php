<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username as customer_name, u.email as customer_email
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: list.php');
    exit;
}

// Fetch order items for this vendor
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ? AND oi.vendor_id = ?
");
$items_stmt->execute([$order_id, $vendor_id]);
$items = $items_stmt->fetchAll();

if (count($items) === 0) {
    header('Location: list.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $old_status = $order['status'];
    
    try {
        $pdo->beginTransaction();
        
        // If changing TO cancelled status, restore stock
        if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
            // Get all order items for this vendor
            $restore_items_stmt = $pdo->prepare("
                SELECT oi.product_id, oi.quantity
                FROM order_items oi
                WHERE oi.order_id = ? AND oi.vendor_id = ?
            ");
            $restore_items_stmt->execute([$order_id, $vendor_id]);
            $restore_items = $restore_items_stmt->fetchAll();
            
            // Restore stock for each product
            $restore_stock_stmt = $pdo->prepare("
                UPDATE products SET stock = stock + ? WHERE id = ?
            ");
            
            foreach ($restore_items as $item) {
                $restore_stock_stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Refund to customer wallet
            $refund_stmt = $pdo->prepare("
                UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?
            ");
            $refund_stmt->execute([$order['total_amount'], $order['customer_id']]);
        }
        
        // If changing FROM cancelled to another status, reduce stock again
        if ($old_status === 'cancelled' && $new_status !== 'cancelled') {
            // Get all order items for this vendor
            $reduce_items_stmt = $pdo->prepare("
                SELECT oi.product_id, oi.quantity
                FROM order_items oi
                WHERE oi.order_id = ? AND oi.vendor_id = ?
            ");
            $reduce_items_stmt->execute([$order_id, $vendor_id]);
            $reduce_items = $reduce_items_stmt->fetchAll();
            
            // Reduce stock for each product
            $reduce_stock_stmt = $pdo->prepare("
                UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
            ");
            
            foreach ($reduce_items as $item) {
                $reduce_stock_stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                
                // Check if stock was actually updated (enough stock available)
                if ($reduce_stock_stmt->rowCount() === 0) {
                    $pdo->rollBack();
                    header("Location: details.php?id=$order_id&error=insufficient_stock");
                    exit;
                }
            }
            
            // Deduct refunded amount from customer wallet
            $deduct_wallet_stmt = $pdo->prepare("
                UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ? AND wallet_balance >= ?
            ");
            $deduct_wallet_stmt->execute([$order['total_amount'], $order['customer_id'], $order['total_amount']]);
            
            if ($deduct_wallet_stmt->rowCount() === 0) {
                $pdo->rollBack();
                header("Location: details.php?id=$order_id&error=insufficient_wallet");
                exit;
            }
        }
        
        // Update order status
        $update_stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update_stmt->execute([$new_status, $order_id]);
        
        $pdo->commit();
        
        header("Location: details.php?id=$order_id&success=status_updated");
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: details.php?id=$order_id&error=update_failed");
        exit;
    }
}

$vendor_total = array_sum(array_column($items, 'subtotal'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .order-details {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .detail-section h3 {
            color: var(--gold);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        .detail-row {
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
        }
        .detail-label {
            color: var(--gold-light);
            font-weight: 500;
        }
        .detail-value {
            color: var(--cream);
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .items-table th {
            background: rgba(212, 175, 55, 0.2);
            color: var(--gold);
            padding: 1rem;
            text-align: left;
        }
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--cream);
        }
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .status-form {
            background: rgba(212, 175, 55, 0.1);
            padding: 1.5rem;
            border-radius: 15px;
            border: 2px solid var(--gold);
        }
        .status-form select {
            width: 100%;
            padding: 0.8rem;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(212, 175, 55, 0.3);
            border-radius: 10px;
            color: var(--cream);
            font-size: 1rem;
            margin: 1rem 0;
        }
        .status-form select:focus {
            outline: none;
            border-color: var(--gold);
        }
        .status-badge {
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: capitalize;
            display: inline-block;
        }
        .status-pending { background: rgba(255, 193, 7, 0.2); color: #FFE082; }
        .status-processing { background: rgba(33, 150, 243, 0.2); color: #90CAF9; }
        .status-shipped { background: rgba(156, 39, 176, 0.2); color: #CE93D8; }
        .status-delivered { background: rgba(76, 175, 80, 0.2); color: #A5D6A7; }
        .status-cancelled { background: rgba(244, 67, 54, 0.2); color: #FFCDD2; }
    </style>
</head>

    <?php 
    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';
    include $root . '/includes/vendor_header.php'; 
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>📦 Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h1>
                    <p>Order Details</p>
                </div>
                <a href="list.php" class="btn btn-secondary">← Back to Orders</a>
            </div>
            
            <div class="order-details">
                <?php if (isset($_GET['success'])): ?>
                    <div style="background: rgba(76, 175, 80, 0.2); color: #A5D6A7; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(76, 175, 80, 0.3);">
                        ✅ <?php 
                            if ($_GET['success'] === 'status_updated') {
                                echo 'Order status updated successfully!';
                                if (isset($_GET['refunded'])) {
                                    echo ' Stock has been restored to inventory.';
                                }
                            }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div style="background: rgba(244, 67, 54, 0.2); color: #FFCDD2; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(244, 67, 54, 0.3);">
                        ❌ <?php 
                            if ($_GET['error'] === 'insufficient_stock') {
                                echo 'Cannot reactivate order: Insufficient stock available.';
                            } elseif ($_GET['error'] === 'insufficient_wallet') {
                                echo 'Cannot reactivate order: Customer has insufficient wallet balance for refund reversal.';
                            } elseif ($_GET['error'] === 'update_failed') {
                                echo 'Failed to update order status. Please try again.';
                            }
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="details-grid">
                    <div class="detail-section">
                        <h3>Customer Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Shipping Address:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['shipping_address'] ?? 'Not provided'); ?></span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3>Order Information</h3>
                        <div class="detail-row">
                            <span class="detail-label">Order Date:</span>
                            <span class="detail-value"><?php echo date('F d, Y H:i', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Current Status:</span>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Your Revenue:</span>
                            <span class="detail-value" style="color: var(--gold); font-size: 1.4rem; font-weight: 700;">
                                $<?php echo number_format($vendor_total, 2); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <h3 style="color: var(--gold); margin-top: 2rem; margin-bottom: 1rem;">Order Items</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="../../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="product-img"
                                         onerror="this.src='../../images/products/default-chocolate.jpg'">
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                </div>
                            </td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><strong style="color: var(--gold);">$<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="status-form" style="margin-top: 2rem;">
                    <h3 style="color: var(--gold); margin-bottom: 0.5rem;">Update Order Status</h3>
                    <p style="margin-bottom: 1rem; opacity: 0.8;">Change the status to keep customers informed</p>
                    
                    <div style="background: rgba(33, 150, 243, 0.1); padding: 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid rgba(33, 150, 243, 0.3);">
                        <small style="color: #90CAF9;">
                            ℹ️ <strong>Note:</strong> Setting status to "Cancelled" will automatically restore product stock to your inventory 
                            and refund the amount to customer's wallet. Reactivating a cancelled order will deduct stock and wallet balance if available.
                        </small>
                    </div>
                    
                    <form method="POST">
                        <select name="status">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">Update Status</button>
                    </form>
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
