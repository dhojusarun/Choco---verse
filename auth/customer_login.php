<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validation
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

try {
    // Fetch user by email
    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    // Check if user is a customer
    if ($user['role'] !== 'customer') {
        echo json_encode(['success' => false, 'message' => 'Access denied. Please use vendor login.']);
        exit;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    
    // Sync session cart to database
    if (isset($_SESSION['temp_cart']) && !empty($_SESSION['temp_cart'])) {
        foreach ($_SESSION['temp_cart'] as $product_id => $quantity) {
            // Check if product already in database cart
            $check_stmt = $pdo->prepare("SELECT id FROM cart WHERE customer_id = ? AND product_id = ?");
            $check_stmt->execute([$user['id'], $product_id]);
            
            if (!$check_stmt->fetch()) {
                // If not in cart, insert it
                $insert_stmt = $pdo->prepare("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->execute([$user['id'], $product_id, $quantity]);
            }
        }
        // Clear temporary cart
        unset($_SESSION['temp_cart']);
    }
    
    echo json_encode(['success' => true, 'message' => 'Login successful!']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
}
?>
