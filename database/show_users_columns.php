<?php
require __DIR__ . '/../config/database.php';
$stmt = $pdo->query('SHOW COLUMNS FROM users');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . "\n";
}
?>
