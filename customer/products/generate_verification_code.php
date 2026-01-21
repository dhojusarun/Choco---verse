<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Generate a 6-digit verification code
$verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Store in session with timestamp
$_SESSION['payment_verification'] = [
    'code' => $verification_code,
    'timestamp' => time(),
    'attempts' => 0,
    'max_attempts' => 3,
    'expires_in' => 300 // 5 minutes in seconds
];

// In production, this would send SMS/Email
// For now, we return the code to display to the user
echo json_encode([
    'success' => true,
    'code' => $verification_code,
    'expires_in' => 300,
    'message' => 'Verification code generated successfully'
]);
?>
