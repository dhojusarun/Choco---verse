<?php
require_once 'config/database.php';

try {
    // 1. Create categories table
    echo "Creating 'categories' table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `image_url` VARCHAR(255) DEFAULT 'images/categories/default.jpg',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Insert initial categories
    echo "Inserting initial categories...\n";
    $initial_categories = [
        ['Artisan Truffles', 'Handcrafted truffles with exotic fillings.', 'images/categories/truffles.jpg'],
        ['Dark Chocolate', 'Pure, intense cocoa experience.', 'images/categories/dark.jpg'],
        ['Milk Chocolate', 'Smooth, creamy classics loved by all.', 'images/categories/milk.jpg'],
        ['Assorted Gifts', 'Perfectly curated sets for any occasion.', 'images/categories/gifts.jpg'],
        ['Baking Cocoa', 'Professional grade ingredients for your kitchen.', 'images/categories/baking.jpg'],
        ['Limited Editions', 'Seasonal specials and rare chocolate finds.', 'images/categories/limited.jpg']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `categories` (`name`, `description`, `image_url`) VALUES (?, ?, ?)");
    foreach ($initial_categories as $cat) {
        $stmt->execute($cat);
    }

    // 3. Add foreign key to products table if it doesn't exist
    echo "Adding foreign key to 'products' table...\n";
    
    // Check if FK exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'products' 
        AND TABLE_SCHEMA = ? 
        AND CONSTRAINT_NAME = 'products_category_fk'
    ");
    $stmt->execute([DB_NAME]);
    $fk_exists = $stmt->fetchColumn();

    if (!$fk_exists) {
        // First ensure category_id column exists (it should, but safety first)
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'category_id'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT(11) DEFAULT NULL AFTER vendor_id");
        }
        
        $pdo->exec("
            ALTER TABLE products 
            ADD CONSTRAINT `products_category_fk` 
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) 
            ON DELETE SET NULL
        ");
        echo "Foreign key created successfully.\n";
    } else {
        echo "Foreign key already exists.\n";
    }

    echo "Database fix completed successfully!\n";

} catch (PDOException $e) {
    die("Error applying database fix: " . $e->getMessage() . "\n");
}
?>
