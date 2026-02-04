<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

$database = Database::getInstance();
$conn = $database->getConnection();
$stmt = $conn->query('DESCRIBE employees');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
    echo $row['Field'] . "\n"; 
}
?>