<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$customer_id = $_SESSION['user_id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Fetch cart items with product details
    $cart_stmt = $pdo->prepare("
        SELECT c.*, p.price, p.stock, p.vendor_id
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.customer_id = ? AND p.is_active = 1
    ");
    $cart_stmt->execute([$customer_id]);
    $cart_items = $cart_stmt->fetchAll();
    
    if (count($cart_items) === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }
    
    // Get payment method from POST
    $payment_method = $_POST['payment_method'] ?? 'online';
    $payment_sub_method = $_POST['payment_sub_method'] ?? 'wallet';
    
    // Verify stock availability for all items
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient stock for some items']);
            exit;
        }
    }
    
    // Calculate total
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    $total_amount = $total_amount * 1.1; // Add 10% tax
    
    // Check if customer has sufficient wallet balance (only for online payment)
    if ($payment_method === 'online') {
        $wallet_check_stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $wallet_check_stmt->execute([$customer_id]);
        $wallet_balance = $wallet_check_stmt->fetchColumn();
        
        if ($wallet_balance < $total_amount) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Insufficient wallet balance. You have $' . number_format($wallet_balance, 2) . ' but need $' . number_format($total_amount, 2)
            ]);
            exit;
        }
        
        // Deduct amount from customer wallet
        $deduct_wallet_stmt = $pdo->prepare("
            UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?
        ");
        $deduct_wallet_stmt->execute([$total_amount, $customer_id]);
        
        $order_status = 'processing'; // Paid orders start as processing
    } else {
        $order_status = 'pending'; // COD orders start as pending
    }
    
    // Create order
    $order_stmt = $pdo->prepare("
        INSERT INTO orders (customer_id, total_amount, status, shipping_address, payment_method, payment_sub_method)
        VALUES (?, ?, ?, 'Default shipping address', ?, ?)
    ");
    $order_stmt->execute([$customer_id, $total_amount, $order_status, $payment_method, $payment_sub_method]);
    $order_id = $pdo->lastInsertId();
    
    // Create order items and update stock
    $order_item_stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $update_stock_stmt = $pdo->prepare("
        UPDATE products SET stock = stock - ? WHERE id = ?
    ");
    
    foreach ($cart_items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        
        // Insert order item
        $order_item_stmt->execute([
            $order_id,
            $item['product_id'],
            $item['vendor_id'],
            $item['quantity'],
            $item['price'],
            $subtotal
        ]);
        
        // Update product stock
        $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // Clear cart
    $clear_cart_stmt = $pdo->prepare("DELETE FROM cart WHERE customer_id = ?");
    $clear_cart_stmt->execute([$customer_id]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_id,
        'payment_method' => $payment_method
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Checkout failed: ' . $e->getMessage()]);
}
?>
