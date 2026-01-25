<?php
require 'config/database.php';
try {
    $stmt = $pdo->query('SHOW TABLES');
    while($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
