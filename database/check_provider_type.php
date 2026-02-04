<?php
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("SHOW COLUMNS FROM insurance_providers WHERE Field = 'provider_type'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Provider Type Column: " . $col['Type'] . "\n";

$stmt = $pdo->query("SELECT DISTINCT provider_type FROM insurance_providers");
echo "\nExisting values:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['provider_type'] . "\n";
}
