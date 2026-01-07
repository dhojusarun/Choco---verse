<?php
/**
 * Migration Script: Add wallet_balance column to users table
 * Run this file once to update your existing database
 */

require_once 'config/database.php';

try {
    echo "<h2>Database Migration: Adding Wallet Balance</h2>";
    
    // Check if column already exists
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'wallet_balance'");
    
    if ($check->rowCount() > 0) {
        echo "<p style='color: orange;'>✓ wallet_balance column already exists. No migration needed.</p>";
    } else {
        // Add wallet_balance column
        $pdo->exec("ALTER TABLE users ADD COLUMN wallet_balance DECIMAL(10,2) DEFAULT 0.00 AFTER role");
        echo "<p style='color: green;'>✓ Successfully added wallet_balance column to users table!</p>";
    }
    
    // Check if favorites table exists
    $check_favorites = $pdo->query("SHOW TABLES LIKE 'favorites'");
    if ($check_favorites->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY customer_product (customer_id, product_id),
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p style='color: green;'>✓ Successfully created favorites table!</p>";
    } else {
        echo "<p style='color: orange;'>✓ favorites table already exists.</p>";
    }
    
    // Check if cart table exists
    $check_cart = $pdo->query("SHOW TABLES LIKE 'cart'");
    if ($check_cart->rowCount() == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY customer_product (customer_id, product_id),
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_customer (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p style='color: green;'>✓ Successfully created cart table!</p>";
    } else {
        echo "<p style='color: orange;'>✓ cart table already exists.</p>";
    }
    
    echo "<br><h3 style='color: #4CAF50;'>✅ Migration Complete!</h3>";
    echo "<p>Your database has been updated. You can now use the wallet and shopping features.</p>";
    echo "<br><a href='index.php' style='background: #D4AF37; color: #3E2723; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Home Page</a>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
}
?>
