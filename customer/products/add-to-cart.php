<?php
session_start();
header('Content-Type: application/json');

// Remove strict login check - allow guests to add to cart
$is_logged_in = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer';
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;

require_once '../../config/database.php';

$product_id = $_POST['product_id'] ?? 0;

try {
    // Check if product exists and is available
    $product_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1 AND stock > 0");
    $product_stmt->execute([$product_id]);
    $product = $product_stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not available']);
        exit;
    }
    
    if ($is_logged_in) {
        // Logged in logic: Use Database
        $check_stmt = $pdo->prepare("SELECT * FROM cart WHERE customer_id = ? AND product_id = ?");
        $check_stmt->execute([$customer_id, $product_id]);
        
        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already in cart']);
            exit;
        }
        
        // Add to cart in DB
        $insert_stmt = $pdo->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, 1)");
        $insert_stmt->execute([$customer_id, $product_id]);
    } else {
        // Guest logic: Use Session
        if (!isset($_SESSION['temp_cart'])) {
            $_SESSION['temp_cart'] = [];
        }
        
        if (isset($_SESSION['temp_cart'][$product_id])) {
            echo json_encode(['success' => false, 'message' => 'Already in cart']);
            exit;
        }
        
        // Add to session cart
        $_SESSION['temp_cart'][$product_id] = 1; // product_id => quantity
    }
    
    echo json_encode(['success' => true, 'message' => 'Added to cart!']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
