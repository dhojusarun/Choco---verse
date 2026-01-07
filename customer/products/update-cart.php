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
$quantity = (int)($_POST['quantity'] ?? 1);

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

try {
    // Verify cart item belongs to customer
    $check_stmt = $pdo->prepare("
        SELECT c.*, p.stock 
        FROM cart c 
        JOIN products p ON c.product_id = p.id
        WHERE c.id = ? AND c.customer_id = ?
    ");
    $check_stmt->execute([$cart_id, $customer_id]);
    $cart_item = $check_stmt->fetch();
    
    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit;
    }
    
    // Check stock availability
    if ($quantity > $cart_item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        exit;
    }
    
    // Update quantity
    $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update_stmt->execute([$quantity, $cart_id]);
    
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
