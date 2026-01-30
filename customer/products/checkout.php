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
        SELECT c.*, p.name, p.price, p.stock, p.vendor_id
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
    
    // Calculate total and prepare product summary
    $total_amount = 0;
    $product_names_arr = [];
    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
        $product_names_arr[] = $item['name'];
    }
    $total_amount = $total_amount * 1.1; // Add 10% tax
    $product_summary = implode(', ', $product_names_arr);
    
    
    // Check if customer has sufficient wallet balance (only for online payment)
    if ($payment_method === 'online') {
        // Verify that payment was verified with code
        if (!isset($_SESSION['payment_verified']) || $_SESSION['payment_verified'] !== true) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Payment verification required. Please verify your payment with the code sent to you.',
                'verification_required' => true
            ]);
            exit;
        }
        
        // Check if verification is still valid (within 2 minutes of verification)
        if (isset($_SESSION['payment_verified_at'])) {
            $verification_age = time() - $_SESSION['payment_verified_at'];
            if ($verification_age > 120) { // 2 minutes
                unset($_SESSION['payment_verified']);
                unset($_SESSION['payment_verified_at']);
                $pdo->rollBack();
                echo json_encode([
                    'success' => false, 
                    'message' => 'Payment verification expired. Please verify again.',
                    'verification_expired' => true
                ]);
                exit;
            }
        }
        
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
        
        // Clear verification flags after successful payment
        unset($_SESSION['payment_verified']);
        unset($_SESSION['payment_verified_at']);
        
        $order_status = 'processing'; // Paid orders start as processing
    } else {
        $order_status = 'pending'; // COD orders start as pending
    }

    
    // Create order
    $order_stmt = $pdo->prepare("
        INSERT INTO orders (customer_id, total_amount, status, product_names, shipping_address, payment_method, payment_sub_method)
        VALUES (?, ?, ?, ?, 'Default shipping address', ?, ?)
    ");
    $order_stmt->execute([$customer_id, $total_amount, $order_status, $product_summary, $payment_method, $payment_sub_method]);
    $order_id = $pdo->lastInsertId();
    
    // Create order items and update stock
    $order_item_stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, vendor_id, quantity, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?, ?)
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
            $item['name'],
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
