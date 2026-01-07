<?php
require 'config/database.php';
$stmt = $pdo->query('DESCRIBE products');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
