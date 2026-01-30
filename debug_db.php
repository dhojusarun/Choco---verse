<?php
require 'config/database.php';
$stmt = $pdo->query('SELECT status, COUNT(*) as count FROM orders GROUP BY status');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query('SELECT o.id, o.status, oi.product_id, o.customer_id FROM orders o JOIN order_items oi ON o.id = oi.order_id');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
