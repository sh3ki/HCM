<?php
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT u.id, u.username, e.id as employee_id, e.first_name, e.last_name FROM users u LEFT JOIN employees e ON u.employee_id = e.id WHERE e.first_name = 'John' AND e.last_name = 'Doe'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "User found:\n";
    echo "  Username: {$result['username']}\n";
    echo "  User ID: {$result['id']}\n";
    echo "  Employee ID: {$result['employee_id']}\n";
    echo "  Name: {$result['first_name']} {$result['last_name']}\n";
} else {
    echo "❌ No user found for John Doe\n";
}
?>