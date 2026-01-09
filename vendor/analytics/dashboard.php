<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];

// Get overall statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT oi.order_id) as total_orders,
        SUM(oi.subtotal) as total_revenue,
        COUNT(DISTINCT p.id) as total_products,
        COALESCE(AVG(r.rating), 0) as avg_rating
    FROM order_items oi
    LEFT JOIN orders o ON oi.order_id = o.id
    LEFT JOIN products p ON oi.product_id = p.id AND p.vendor_id = ?
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE oi.vendor_id = ? AND o.status != 'cancelled'
");
$stats_stmt->execute([$vendor_id, $vendor_id]);
$stats = $stats_stmt->fetch();

// Get monthly revenue (last 6 months)
$monthly_stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') as month,
        SUM(oi.subtotal) as revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.vendor_id = ? 
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        AND o.status != 'cancelled'
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_stmt->execute([$vendor_id]);
$monthly_revenue = $monthly_stmt->fetchAll();

// Get top selling products
$top_products_stmt = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.vendor_id = ? AND o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$top_products_stmt->execute([$vendor_id]);
$top_products = $top_products_stmt->fetchAll();

// Get order status distribution
$status_stmt = $pdo->prepare("
    SELECT o.status, COUNT(DISTINCT o.id) as count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE oi.vendor_id = ?
    GROUP BY o.status
");
$status_stmt->execute([$vendor_id]);
$status_distribution = $status_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition-smooth);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            opacity: 0.7;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 3rem;
        }

        .chart-container h3 {
            color: var(--gold);
            margin-bottom: 2rem;
        }

        .top-products {
            background: rgba(255, 255, 255, 0.05);
            padding: 2.5rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .product-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .stat-card {
                padding: 1.5rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .chart-container {
                padding: 1rem;
                overflow-x: auto;
            }

            .top-products {
                padding: 1.5rem;
            }

            .product-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .product-item div:last-child {
                text-align: left;
            }
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
                    <h1>📊 Sales Analytics</h1>
                    <p>Track your business performance</p>
                </div>
                <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
            
            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-value">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🍫</div>
                        <div class="stat-value"><?php echo $stats['total_products'] ?? 0; ?></div>
                        <div class="stat-label">Products Listed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-value"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <h3>📈 Revenue Trend (Last 6 Months)</h3>
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
                
                <div class="top-products">
                    <h3 style="color: var(--gold); margin-bottom: 1.5rem;">🏆 Top Selling Products</h3>
                    <?php if (count($top_products) > 0): ?>
                        <?php foreach ($top_products as $index => $product): ?>
                        <div class="product-item">
                            <div>
                                <strong style="color: var(--gold);">#<?php echo $index + 1; ?></strong>
                                <strong style="margin-left: 1rem;"><?php echo htmlspecialchars($product['name']); ?></strong>
                                <br>
                                <small style="opacity: 0.7;"><?php echo $product['total_sold']; ?> units sold</small>
                            </div>
                            <div style="text-align: right;">
                                <strong style="color: var(--gold); font-size: 1.2rem;">$<?php echo number_format($product['revenue'], 2); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; opacity: 0.7;">No sales data available yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php echo json_encode($monthly_revenue); ?>;
        
        const labels = revenueData.map(item => {
            const [year, month] = item.month.split('-');
            return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        });
        const data = revenueData.map(item => parseFloat(item.revenue));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: data,
                    borderColor: '#D4AF37',
                    backgroundColor: 'rgba(212, 175, 55, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#FFF8E7',
                            font: { size: 14 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#FFF8E7',
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#FFF8E7'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
    </script>
    
    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
