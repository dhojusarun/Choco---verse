<?php
require 'config/database.php';
try {
    echo "--- Products Table Structure ---\n";
    $stmt = $pdo->query('DESCRIBE products');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
    echo "\n--- Foreign Keys for Products ---\n";
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'products' AND TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute([DB_NAME]);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
