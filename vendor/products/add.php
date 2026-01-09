<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($name) || empty($price) || empty($stock)) {
        $error = 'Name, price, and stock are required fields.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Price must be a positive number.';
    } elseif (!is_numeric($stock) || $stock < 0) {
        $error = 'Stock must be a non-negative number.';
    } else {
        // Handle image upload
        $image_url = 'images/products/default-chocolate.jpg';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../../uploads/';
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_url = 'uploads/' . $new_filename;
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (vendor_id, name, description, price, stock, image_url, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vendor_id, $name, $description, $price, $stock, $image_url, $is_active]);
            
            $success = 'Product added successfully!';
            header('Location: list.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to add product: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Choco World</title>
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

        .file-input-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .file-input-label {
            display: block;
            padding: 2rem;
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
                    <h1>➕ Add New Product</h1>
                    <p>Create a new chocolate product</p>
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
                            <input type="text" id="name" name="name" placeholder="e.g., Dark Chocolate Truffles" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-full">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Describe your chocolate product..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">Stock Quantity *</label>
                            <input type="number" id="stock" name="stock" min="0" placeholder="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-full">
                            <label>Product Image</label>
                            <div class="file-input-wrapper">
                                <label for="image" class="file-input-label">
                                    📸 Click to upload image (JPG, PNG, WEBP)
                                    <br><small>Maximum file size: 5MB</small>
                                </label>
                                <input type="file" id="image" name="image" accept="image/*">
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" checked>
                        <label for="is_active" style="margin: 0; color: var(--cream);">Product is active and visible to customers</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem;">Add Product</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Show selected filename
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
