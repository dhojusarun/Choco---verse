<?php
require 'config/database.php';

try {
    echo "Starting full database rebuild...\n";

    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    $tables = [
        'order_items',
        'orders',
        'reviews',
        'cart',
        'favorites',
        'vendor_settings',
        'products',
        'categories',
        'users'
    ];

    

    // Keep foreign key checks disabled for creation
    // $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "Creating 'users' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `username` VARCHAR(50) NOT NULL,
      `email` VARCHAR(100) NOT NULL,
      `password` VARCHAR(255) NOT NULL,
      `role` ENUM('customer','vendor') NOT NULL,
      `wallet_balance` DECIMAL(10,2) DEFAULT 0.00,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`),
      UNIQUE KEY `email` (`email`),
      KEY `idx_email` (`email`),
      KEY `idx_role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Creating 'categories' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(100) NOT NULL,
      `description` TEXT,
      `image_url` VARCHAR(255) DEFAULT 'images/categories/default.jpg',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Creating 'products' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Creating 'orders' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `customer_id` INT(11) NOT NULL,
      `total_amount` DECIMAL(10,2) NOT NULL,
      `status` ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
      `shipping_address` TEXT,
      `payment_method` VARCHAR(50) DEFAULT 'online',
      `payment_sub_method` VARCHAR(50) DEFAULT 'wallet',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      KEY `idx_customer` (`customer_id`),
      KEY `idx_status` (`status`),
      KEY `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Creating 'order_items' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `order_id` INT(11) NOT NULL,
      `product_id` INT(11) NOT NULL,
      `vendor_id` INT(11) NOT NULL,
      `quantity` INT(11) NOT NULL,
      `price` DECIMAL(10,2) NOT NULL,
      `subtotal` DECIMAL(10,2) NOT NULL,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`vendor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      KEY `idx_order` (`order_id`),
      KEY `idx_product` (`product_id`),
      KEY `idx_vendor` (`vendor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Creating 'reviews' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reviews` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `product_id` INT(11) NOT NULL,
      `customer_id` INT(11) NOT NULL,
      `rating` TINYINT(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
      `comment` TEXT,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      KEY `idx_product` (`product_id`),
      KEY `idx_customer` (`customer_id`),
      KEY `idx_rating` (`rating`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Creating 'vendor_settings' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `vendor_settings` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `vendor_id` INT(11) NOT NULL,
      `business_description` TEXT,
      `phone` VARCHAR(20),
      `address` TEXT,
      `business_hours` VARCHAR(255),
      `logo_url` VARCHAR(255),
      `banner_url` VARCHAR(255),
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `vendor_id` (`vendor_id`),
      FOREIGN KEY (`vendor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Creating 'favorites' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `favorites` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `customer_id` INT(11) NOT NULL,
      `product_id` INT(11) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `customer_product` (`customer_id`, `product_id`),
      FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
      KEY `idx_customer` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Creating 'cart' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `cart` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `customer_id` INT(11) NOT NULL,
      `product_id` INT(11) NOT NULL,
      `quantity` INT(11) NOT NULL DEFAULT 1,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `customer_product` (`customer_id`, `product_id`),
      FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
      KEY `idx_customer` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Inserting default categories...\n";
    $pdo->exec("INSERT IGNORE INTO `categories` (`name`, `description`, `image_url`) VALUES
    ('Artisan Truffles', 'Handcrafted truffles with exotic fillings.', 'images/categories/truffles.jpg'),
    ('Dark Chocolate', 'Pure, intense cocoa experience.', 'images/categories/dark.jpg'),
    ('Milk Chocolate', 'Smooth, creamy classics loved by all.', 'images/categories/milk.jpg'),
    ('Assorted Gifts', 'Perfectly curated sets for any occasion.', 'images/categories/gifts.jpg'),
    ('Baking Cocoa', 'Professional grade ingredients for your kitchen.', 'images/categories/baking.jpg'),
    ('Limited Editions', 'Seasonal specials and rare chocolate finds.', 'images/categories/limited.jpg')");

    // Insert test users if needed
    echo "Inserting test users...\n";
    // Password is 'password123'
    $pdo->exec("INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`) VALUES
    ('customer1', 'customer1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
    ('vendor1', 'vendor1@business.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendor')");

    echo "Full database rebuild completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
