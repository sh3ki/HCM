<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Check insurance_providers structure
    echo "=== INSURANCE_PROVIDERS COLUMNS ===\n";
    $stmt = $conn->query("DESCRIBE insurance_providers");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
    }
    
    echo "\n=== INSURANCE_PLANS COLUMNS ===\n";
    $stmt = $conn->query("DESCRIBE insurance_plans");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
    }
    
    echo "\n=== EMPLOYEE_INSURANCE COLUMNS ===\n";
    $stmt = $conn->query("DESCRIBE employee_insurance");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>