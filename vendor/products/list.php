<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch vendor's products
$stmt = $pdo->prepare("
    SELECT p.*, 
           COALESCE(AVG(r.rating), 0) as avg_rating,
           COUNT(DISTINCT r.id) as review_count
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.vendor_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$vendor_id]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .product-table {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .product-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th {
            text-align: left;
            padding: 1.2rem;
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold);
        }

        .product-table td {
            padding: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .vendor-product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .status-active {
            background: rgba(76, 175, 80, 0.2);
            color: #A5D6A7;
        }

        .status-inactive {
            background: rgba(244, 67, 54, 0.2);
            color: #FFCDD2;
        }

        .btn-edit {
            background: rgba(33, 150, 243, 0.1);
            color: #90CAF9;
            border: 1px solid #90CAF9;
        }

        .btn-edit:hover {
            background: #90CAF9;
            color: white;
        }

        .btn-delete {
            background: rgba(244, 67, 54, 0.1);
            color: #EF5350;
            border: 1px solid #EF5350;
        }

        .btn-delete:hover {
            background: #EF5350;
            color: white;
        }

        .no-products {
            text-align: center;
            padding: 5rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            border: 1px dashed rgba(255, 255, 255, 0.2);
        }
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }

            .product-table {
                display: block;
                overflow-x: auto;
            }

            .product-table th, .product-table td {
                padding: 0.8rem;
                font-size: 0.9rem;
            }

            .vendor-product-img {
                width: 60px;
                height: 60px;
            }
        }
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
                    <h1>🍫 Product Management</h1>
                    <p>Manage your chocolate products</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="add.php" class="btn btn-primary">+ Add New Product</a>
                    <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                </div>
            </div>
            
            <div class="dashboard-content">
                <?php if (count($products) > 0): ?>
                <div class="product-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="vendor-product-img"
                                         onerror="this.src='../../images/products/default-chocolate.jpg'">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <br>
                                    <small style="opacity: 0.7;"><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</small>
                                </td>
                                <td><strong style="color: var(--gold);">$<?php echo number_format($product['price'], 2); ?></strong></td>
                                <td><?php echo $product['stock']; ?> units</td>
                                <td>
                                    ⭐ <?php echo number_format($product['avg_rating'], 1); ?> 
                                    <small>(<?php echo $product['review_count']; ?>)</small>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit">Edit</a>
                                    <a href="delete.php?id=<?php echo $product['id']; ?>" 
                                       class="action-btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-products">
                    <h3>No Products Yet</h3>
                    <p>Start by adding your first chocolate product!</p>
                    <a href="add.php" class="btn btn-primary" style="margin-top: 1rem;">+ Add Your First Product</a>
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
