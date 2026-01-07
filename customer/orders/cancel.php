<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$customer_id = $_SESSION['user_id'];
$order_id = $_POST['order_id'] ?? 0;

try {
    $pdo->beginTransaction();
    
    // Verify order belongs to customer and can be cancelled
    $order_stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND customer_id = ?
    ");
    $order_stmt->execute([$order_id, $customer_id]);
    $order = $order_stmt->fetch();
    
    if (!$order) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Can only cancel pending or processing orders
    if (!in_array($order['status'], ['pending', 'processing'])) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
        exit;
    }
    
    // Already cancelled
    if ($order['status'] === 'cancelled') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Order is already cancelled']);
        exit;
    }
    
    // Get all order items to restore stock
    $items_stmt = $pdo->prepare("
        SELECT product_id, quantity 
        FROM order_items 
        WHERE order_id = ?
    ");
    $items_stmt->execute([$order_id]);
    $items = $items_stmt->fetchAll();
    
    // Restore stock for each product
    $restore_stock_stmt = $pdo->prepare("
        UPDATE products SET stock = stock + ? WHERE id = ?
    ");
    
    foreach ($items as $item) {
        $restore_stock_stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // Refund amount to customer wallet
    $refund_stmt = $pdo->prepare("
        UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?
    ");
    $refund_stmt->execute([$order['total_amount'], $customer_id]);
    
    // Update order status to cancelled
    $cancel_stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $cancel_stmt->execute([$order_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order cancelled successfully! $' . number_format($order['total_amount'], 2) . ' has been refunded to your wallet.'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Cancellation failed: ' . $e->getMessage()]);
}
?>
