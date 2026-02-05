<?php
// We don't use config/database.php yet because it tries to connect to the DB that might be broken
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'choco_world');

try {
    // Connect to MySQL without specifying a database
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Dropping database " . DB_NAME . "...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `" . DB_NAME . "`");
    echo "Creating database " . DB_NAME . "...\n";
    $pdo->exec("CREATE DATABASE `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database reset successfully.\n";

    // Now run the rebuild script
    echo "Running rebuild_db.php...\n";
    require 'rebuild_db.php';

} catch (PDOException $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
