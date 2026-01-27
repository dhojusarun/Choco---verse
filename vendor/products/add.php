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
    $category_id = $_POST['category_id'] ?? null;
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
                INSERT INTO products (vendor_id, category_id, name, description, price, stock, image_url, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vendor_id, $category_id, $name, $description, $price, $stock, $image_url, $is_active]);
            
            $success = 'Product added successfully!';
            header('Location: list.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to add product: ' . $e->getMessage();
        }
    }
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Choco World</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        #image { display: none; }
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
                    <p>Create a fresh indulgence for your customers</p>
                </div>
                <a href="list.php" class="btn btn-secondary">← Back to Products</a>
            </div>
            
            <div class="dashboard-content">
                <div class="product-form">
                    <?php if ($error): ?>
                        <div class="alert alert-error show"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group form-full">
                                <label for="name">✨ Product Name</label>
                                <input type="text" id="name" name="name" placeholder="e.g., Artisan Dark Chocolate Truffles" required>
                                <small style="opacity: 0.6; margin-top: 0.5rem; display: block;">Pick a name that sounds as sweet as it tastes!</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group form-full">
                                <label for="category_id">📂 Product Category</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="" disabled selected>-- Select the best fit --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group form-full">
                                <label for="description">✍️ Description</label>
                                <textarea id="description" name="description" placeholder="Describe the texture, flavor profile, and story behind this chocolate..."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">💰 Price ($)</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="stock">📦 Initial Stock</label>
                                <input type="number" id="stock" name="stock" min="0" placeholder="0" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group form-full">
                                <label>🖼️ Product Image</label>
                                <div class="file-input-wrapper">
                                    <label for="image" class="file-input-label">
                                        <span id="upload-icon">📸</span>
                                        <div id="upload-text">
                                            <strong>Click to upload product image</strong>
                                            <br><small>JPG, PNG, or WEBP (Max 5MB)</small>
                                        </div>
                                    </label>
                                    <input type="file" id="image" name="image" accept="image/*">
                                </div>
                            </div>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <label for="is_active" style="margin: 0; color: var(--gold-light);">Visible to customers immediately</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2.5rem; padding: 1.2rem;">
                            🚀 Add This Indulgence
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Show selected filename and preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const uploadText = document.getElementById('upload-text');
            const uploadIcon = document.getElementById('upload-icon');
            
            if (file) {
                uploadIcon.innerHTML = '✅';
                uploadText.innerHTML = `<strong>${file.name}</strong><br><small>File ready for upload</small>`;
                document.querySelector('.file-input-label').style.borderColor = 'var(--gold)';
                document.querySelector('.file-input-label').style.background = 'rgba(212, 175, 55, 0.15)';
            }
        });
    </script>

    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
