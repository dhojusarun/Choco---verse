<?php
require 'config/database.php';

try {
    echo "Starting database repair...\n";

    // Disable foreign key checks to allow dropping tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    // Drop tables if they exist
    echo "Dropping 'products' table...\n";
    $pdo->exec("DROP TABLE IF EXISTS products");
    
    echo "Dropping 'categories' table...\n";
    $pdo->exec("DROP TABLE IF EXISTS categories");

    // Keep foreign key checks disabled during creation to avoid dictionary conflicts
    // $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "Recreating 'categories' table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `categories` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(100) NOT NULL,
      `description` TEXT,
      `image_url` VARCHAR(255) DEFAULT 'images/categories/default.jpg',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);

    echo "Recreating 'products' table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `products` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `vendor_id` INT(11) NOT NULL,
      `category_id` INT(11) DEFAULT NULL,
      `name` VARCHAR(200) NOT NULL,
      `description` TEXT,
      `price` DECIMAL(10,2) NOT NULL,
      `image_url` VARCHAR(255) DEFAULT 'images/products/default-chocolate.jpg',
      `stock` INT(11) DEFAULT 0,
      `is_active` TINYINT(1) DEFAULT 1,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`vendor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
      KEY `idx_vendor` (`vendor_id`),
      KEY `idx_category` (`category_id`),
      KEY `idx_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);

    echo "Inserting default categories...\n";
    $sql = "INSERT IGNORE INTO `categories` (`name`, `description`, `image_url`) VALUES
    ('Artisan Truffles', 'Handcrafted truffles with exotic fillings.', 'images/categories/truffles.jpg'),
    ('Dark Chocolate', 'Pure, intense cocoa experience.', 'images/categories/dark.jpg'),
    ('Milk Chocolate', 'Smooth, creamy classics loved by all.', 'images/categories/milk.jpg'),
    ('Assorted Gifts', 'Perfectly curated sets for any occasion.', 'images/categories/gifts.jpg'),
    ('Baking Cocoa', 'Professional grade ingredients for your kitchen.', 'images/categories/baking.jpg'),
    ('Limited Editions', 'Seasonal specials and rare chocolate finds.', 'images/categories/limited.jpg')";
    $pdo->exec($sql);

    echo "Database repair completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
