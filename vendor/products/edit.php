<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];
$product_id = $_GET['id'] ?? 0;
$success = $error = '';

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
$stmt->execute([$product_id, $vendor_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name) || empty($price) || empty($stock)) {
        $error = 'Name, price, and stock are required fields.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Price must be a positive number.';
    } elseif (!is_numeric($stock) || $stock < 0) {
        $error = 'Stock must be a non-negative number.';
    } else {
        $image_url = $product['image_url'];
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../../uploads/';
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Delete old image if it's not the default
                    if ($product['image_url'] !== 'images/products/default-chocolate.jpg' && file_exists('../../' . $product['image_url'])) {
                        unlink('../../' . $product['image_url']);
                    }
                    $image_url = 'uploads/' . $new_filename;
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, description = ?, price = ?, stock = ?, image_url = ?, is_active = ?
                WHERE id = ? AND vendor_id = ?
            ");
            $stmt->execute([$name, $description, $price, $stock, $image_url, $is_active, $product_id, $vendor_id]);
            
            $success = 'Product updated successfully!';
            header('Location: list.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to update product: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .product-form {
            background: rgba(255, 255, 255, 0.05);
            padding: 3rem;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 900px;
            margin: 0 auto;
        }

        .current-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: block;
            border: 2px solid var(--gold);
        }

        .file-input-label {
            display: block;
            padding: 1.5rem;
            background: rgba(212, 175, 55, 0.1);
            border: 2px dashed var(--gold);
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .file-input-label:hover {
            background: rgba(212, 175, 55, 0.2);
        }

        #image {
            display: none;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.03);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .product-form {
                padding: 1.5rem;
                border-radius: 20px;
            }

            .form-group {
                min-width: 100%;
            }

            .current-image {
                width: 150px;
                height: 150px;
            }

            .checkbox-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
                    <h1>✏️ Edit Product</h1>
                    <p>Update product details</p>
                </div>
                <a href="list.php" class="btn btn-secondary">← Back to Products</a>
            </div>
            
            <div class="product-form">
                <?php if ($error): ?>
                    <div class="alert alert-error show"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group form-full">
                            <label for="name">Product Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-full">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">Stock Quantity *</label>
                            <input type="number" id="stock" name="stock" min="0" value="<?php echo $product['stock']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-full">
                            <label>Current Image</label>
                            <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="Current product" 
                                 class="current-image"
                                 onerror="this.src='../../images/products/default-chocolate.jpg'">
                            <label for="image" class="file-input-label">
                                📸 Click to upload new image (optional)
                                <br><small>JPG, PNG, WEBP - Max 5MB</small>
                            </label>
                            <input type="file" id="image" name="image" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                        <label for="is_active" style="margin: 0; color: var(--cream);">Product is active and visible to customers</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem;">Update Product</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.querySelector('.file-input-label').innerHTML = 
                    `✅ Selected: ${fileName}<br><small>Click to change</small>`;
            }
        });
    </script>

    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
