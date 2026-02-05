<?php
require 'config/database.php';

$tables = [
    'order_items', 'orders', 'reviews', 'cart', 'favorites', 
    'vendor_settings', 'products', 'categories', 'users'
];

echo "Forcing database repair...\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    foreach ($tables as $table) {
        echo "Processing table '$table'...\n";
        try {
            // Attempt to drop the table
            $pdo->exec("DROP TABLE IF EXISTS `$table` CASCADE");
            echo "[OK] Dropped '$table'\n";
        } catch (PDOException $e) {
            echo "[WARN] Could not drop '$table': " . $e->getMessage() . "\n";
            echo "Attempting to discard tablespace and drop...\n";
            try {
                $pdo->exec("ALTER TABLE `$table` DISCARD TABLESPACE");
                $pdo->exec("DROP TABLE `$table` CASCADE");
                echo "[OK] Dropped '$table' after discarding tablespace\n";
            } catch (PDOException $e2) {
                echo "[ERROR] Failed to drop '$table' definitively: " . $e2->getMessage() . "\n";
            }
        }
    }

    echo "Recreating schema...\n";
    // Now include the creation logic from rebuild_db.php or run it
    require 'rebuild_db.php';

} catch (PDOException $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
