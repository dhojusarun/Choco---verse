<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$customer_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;

try {
    // Check if already favorited
    $check_stmt = $pdo->prepare("SELECT id FROM favorites WHERE customer_id = ? AND product_id = ?");
    $check_stmt->execute([$customer_id, $product_id]);
    $exists = $check_stmt->fetch();
    
    if ($exists) {
        // Remove from favorites
        $delete_stmt = $pdo->prepare("DELETE FROM favorites WHERE customer_id = ? AND product_id = ?");
        $delete_stmt->execute([$customer_id, $product_id]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from favorites']);
    } else {
        // Add to favorites
        $insert_stmt = $pdo->prepare("INSERT INTO favorites (customer_id, product_id) VALUES (?, ?)");
        $insert_stmt->execute([$customer_id, $product_id]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to favorites!']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
