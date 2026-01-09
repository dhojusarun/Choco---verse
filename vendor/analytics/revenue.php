<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];

// Get all transactions (order items)
$transactions_stmt = $pdo->prepare("
    SELECT oi.*, o.created_at as order_date, o.status, 
           p.name as product_name, u.username as customer_name
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.customer_id = u.id
    WHERE oi.vendor_id = ?
    ORDER BY o.created_at DESC
");
$transactions_stmt->execute([$vendor_id]);
$transactions = $transactions_stmt->fetchAll();

// Calculate totals (excluding cancelled orders for active revenue)
$total_revenue = 0;
$pending_revenue = 0;
$completed_revenue = 0;
$cancelled_revenue = 0;

foreach ($transactions as $transaction) {
    if ($transaction['status'] === 'cancelled') {
        $cancelled_revenue += $transaction['subtotal'];
    } else {
        $total_revenue += $transaction['subtotal'];
        if ($transaction['status'] === 'delivered') {
            $completed_revenue += $transaction['subtotal'];
        } else {
            $pending_revenue += $transaction['subtotal'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .revenue-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .revenue-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .revenue-label {
            opacity: 0.7;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .revenue-amount {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--gold);
        }

        .transactions-table {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            margin-top: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .transactions-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table th {
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold);
            padding: 1.2rem 1rem;
            text-align: left;
            font-weight: 600;
        }

        .transactions-table td {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--cream);
        }

        .transactions-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
    </style>
</head>

    <?php
    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';

     include $root . '/includes/vendor_header.php'; ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>💰 Revenue & Earnings</h1>
                    <p>Track your income and payments</p>
                </div>
                <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
            
            <div class="dashboard-content">
                <div class="revenue-summary">
                    <div class="revenue-card" style="border: 2px solid var(--gold);">
                        <div class="revenue-label">💵 Total Revenue</div>
                        <div class="revenue-amount">$<?php echo number_format($total_revenue, 2); ?></div>
                        <small style="opacity: 0.7;">Active orders only</small>
                    </div>
                    <div class="revenue-card">
                        <div class="revenue-label">✅ Completed</div>
                        <div class="revenue-amount" style="color: #A5D6A7;">$<?php echo number_format($completed_revenue, 2); ?></div>
                        <small style="opacity: 0.7;">Delivered orders</small>
                    </div>
                    <div class="revenue-card">
                        <div class="revenue-label">⏳ Pending</div>
                        <div class="revenue-amount" style="color: #FFE082;">$<?php echo number_format($pending_revenue, 2); ?></div>
                        <small style="opacity: 0.7;">Orders in progress</small>
                    </div>
                    <div class="revenue-card" style="background: rgba(244, 67, 54, 0.1); border: 1px solid rgba(244, 67, 54, 0.3);">
                        <div class="revenue-label">❌ Cancelled</div>
                        <div class="revenue-amount" style="color: #FFCDD2;">$<?php echo number_format($cancelled_revenue, 2); ?></div>
                        <small style="opacity: 0.7;">Refunded to customers</small>
                    </div>
                </div>
                
                <h3 style="color: var(--gold); margin-bottom: 1.5rem;">Transaction History</h3>
                
                <?php if (count($transactions) > 0): ?>
                <div class="transactions-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($transaction['order_date'])); ?></td>
                                <td><strong>#<?php echo str_pad($transaction['order_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                <td><?php echo $transaction['quantity']; ?>x</td>
                                <td><strong style="color: var(--gold);">$<?php echo number_format($transaction['subtotal'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 3rem; opacity: 0.7;">
                    <h3>No Transactions Yet</h3>
                    <p>Your earnings will appear here once customers purchase your products.</p>
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
