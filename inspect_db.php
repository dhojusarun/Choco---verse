<?php
require 'config/database.php';
header('Content-Type: text/plain');
try {
    echo "--- Products Table Structure ---\n";
    $stmt = $pdo->query('DESCRIBE products');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results, JSON_PRETTY_PRINT);
    
    echo "\n\n--- Foreign Keys for Products ---\n";
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'products' AND TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute([DB_NAME]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
