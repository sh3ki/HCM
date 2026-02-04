<?php
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== PAYROLL_RECORDS TABLE STRUCTURE ===\n";
$stmt = $conn->query("DESCRIBE payroll_records");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
?>