<?php
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== USERS TABLE STRUCTURE ===\n";
$stmt = $conn->query("DESCRIBE users");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}

echo "\n=== EMPLOYEES TABLE STRUCTURE (partial) ===\n";
$stmt = $conn->query("SELECT id, first_name, last_name FROM employees WHERE first_name = 'John' AND last_name = 'Doe'");
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if ($emp) {
    echo "John Doe Employee ID: " . $emp['id'] . "\n";
}

echo "\n=== USERS TABLE (first 5) ===\n";
$stmt = $conn->query("SELECT id, username, email, role FROM users LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Username: {$row['username']}, Email: {$row['email']}, Role: {$row['role']}\n";
}
?>
