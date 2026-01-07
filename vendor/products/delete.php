<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];
$product_id = $_GET['id'] ?? 0;

// Verify product belongs to vendor
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
$stmt->execute([$product_id, $vendor_id]);
$product = $stmt->fetch();

if ($product) {
    try {
        // Delete product image if not default
        if ($product['image_url'] !== 'images/products/default-chocolate.jpg' && file_exists('../../' . $product['image_url'])) {
            unlink('../../' . $product['image_url']);
        }
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->execute([$product_id, $vendor_id]);
        
        $_SESSION['success_message'] = 'Product deleted successfully!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Failed to delete product: ' . $e->getMessage();
    }
}

header('Location: list.php');
exit;
?>
