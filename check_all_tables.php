<?php
require 'config/database.php';

$tables = [
    'users', 
    'products', 
    'categories', 
    'orders', 
    'order_items', 
    'reviews', 
    'cart', 
    'vendor_settings', 
    'favorites'
];

echo "Checking tables...\n";

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        echo "[OK] $table\n";
    } catch (PDOException $e) {
        echo "[ERROR] $table: " . $e->getMessage() . "\n";
    }
}
?>
