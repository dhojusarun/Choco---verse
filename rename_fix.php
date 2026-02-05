<?php
require 'config/database.php';

$tables = [
    'order_items', 'orders', 'reviews', 'cart', 'favorites', 
    'vendor_settings', 'products', 'categories', 'users'
];

echo "Attempting to bypass corrupted tables by renaming...\n";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    foreach ($tables as $table) {
        $broken_name = $table . "_broken_" . time();
        echo "Processing '$table' -> '$broken_name'...\n";
        try {
            $pdo->exec("RENAME TABLE `$table` TO `$broken_name`");
            echo "[OK] Renamed '$table'\n";
        } catch (PDOException $e) {
            echo "[WARN] Could not rename '$table': " . $e->getMessage() . "\n";
            echo "Attempting to drop directly instead...\n";
            try {
                $pdo->exec("DROP TABLE IF EXISTS `$table` CASCADE");
                echo "[OK] Dropped '$table'\n";
            } catch (PDOException $e2) {
                echo "[ERROR] Failed both rename and drop for '$table'\n";
            }
        }
    }

    echo "Running rebuild script to recreate tables...\n";
    // We modify rebuild_db.php content to NOT drop tables since we want to create them fresh
    $rebuild_content = file_get_contents('rebuild_db.php');
    // Remove the DROP TABLE loop
    $rebuild_content = preg_replace('/foreach \(\$tables as \$table\) \{.*?\}/s', '', $rebuild_content);
    
    // Save to a temporary file and run
    file_put_contents('rebuild_fresh.php', $rebuild_content);
    require 'rebuild_fresh.php';

} catch (PDOException $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
