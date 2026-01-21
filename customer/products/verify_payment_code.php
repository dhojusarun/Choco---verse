<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the code from POST
$entered_code = $_POST['code'] ?? '';

// Check if verification data exists
if (!isset($_SESSION['payment_verification'])) {
    echo json_encode(['success' => false, 'message' => 'No verification code found. Please request a new code.']);
    exit;
}

$verification = $_SESSION['payment_verification'];

// Check if code has expired (5 minutes)
$elapsed_time = time() - $verification['timestamp'];
if ($elapsed_time > $verification['expires_in']) {
    unset($_SESSION['payment_verification']);
    echo json_encode([
        'success' => false, 
        'message' => 'Verification code has expired. Please request a new code.',
        'expired' => true
    ]);
    exit;
}

// Check if max attempts exceeded
if ($verification['attempts'] >= $verification['max_attempts']) {
    unset($_SESSION['payment_verification']);
    echo json_encode([
        'success' => false, 
        'message' => 'Maximum verification attempts exceeded. Please request a new code.',
        'max_attempts_exceeded' => true
    ]);
    exit;
}

// Increment attempts
$_SESSION['payment_verification']['attempts']++;

// Verify the code
if ($entered_code === $verification['code']) {
    // Mark as verified
    $_SESSION['payment_verified'] = true;
    $_SESSION['payment_verified_at'] = time();
    
    // Clear verification data
    unset($_SESSION['payment_verification']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully!'
    ]);
} else {
    $remaining_attempts = $verification['max_attempts'] - $_SESSION['payment_verification']['attempts'];
    echo json_encode([
        'success' => false,
        'message' => 'Invalid verification code. ' . $remaining_attempts . ' attempt(s) remaining.',
        'remaining_attempts' => $remaining_attempts
    ]);
}
?>
