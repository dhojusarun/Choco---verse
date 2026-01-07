<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$customer_id = $_SESSION['user_id'];
$cart_id = $_POST['cart_id'] ?? 0;

try {
    // Verify cart item belongs to customer and delete
    $delete_stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
    $delete_stmt->execute([$cart_id, $customer_id]);
    
    if ($delete_stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
