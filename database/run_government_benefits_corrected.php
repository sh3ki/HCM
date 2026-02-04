<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Enable buffered queries
    $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/add_government_benefits_corrected.sql');
    
    // Split into individual statements (remove verification SELECT queries)
    $statements = explode(';', $sql);
    
    echo "Executing Government Benefits Setup...\n\n";
    
    $successCount = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && stripos($statement, '-- ') !== 0) {
            // Skip SELECT verification queries during batch execution
            if (stripos($statement, 'SELECT COUNT') === false && 
                stripos($statement, 'SELECT ') !== 0 ||
                stripos($statement, 'SET @') === 0) {
                try {
                    $conn->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    // Skip errors for ON DUPLICATE KEY updates
                    if (strpos($e->getMessage(), 'Duplicate') === false) {
                        echo "⚠ Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
    
    echo "✓ Executed $successCount SQL statements successfully\n\n";
    
    // Now run verification queries
    echo "=== VERIFICATION RESULTS ===\n\n";
    
    // Total active employees
    $stmt = $conn->query("SELECT COUNT(*) as total_active_employees FROM employees WHERE employment_status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Active Employees: " . $result['total_active_employees'] . "\n\n";
    
    // SSS enrollments
    $stmt = $conn->query("
        SELECT COUNT(*) as sss_enrollments 
        FROM employee_insurance ei
        JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
        WHERE ip.plan_code = 'GOV-SSS-2026'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "SSS Enrollments: " . $result['sss_enrollments'] . "\n";
    
    // Pag-IBIG enrollments
    $stmt = $conn->query("
        SELECT COUNT(*) as pagibig_enrollments 
        FROM employee_insurance ei
        JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
        WHERE ip.plan_code = 'GOV-PAGIBIG-2026'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Pag-IBIG Enrollments: " . $result['pagibig_enrollments'] . "\n";
    
    // PhilHealth enrollments
    $stmt = $conn->query("
        SELECT COUNT(*) as philhealth_enrollments 
        FROM employee_insurance ei
        JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
        WHERE ip.plan_code = 'GOV-PHILHEALTH-2026'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "PhilHealth Enrollments: " . $result['philhealth_enrollments'] . "\n\n";
    
    // Show premium details
    echo "=== GOVERNMENT BENEFIT PLANS ===\n\n";
    $stmt = $conn->query("
        SELECT 
            ip.plan_name,
            ip.monthly_premium,
            ip.employer_contribution,
            ip.employee_contribution
        FROM insurance_plans ip
        WHERE ip.plan_code IN ('GOV-SSS-2026', 'GOV-PAGIBIG-2026', 'GOV-PHILHEALTH-2026')
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['plan_name'] . ":\n";
        echo "  Total Premium: ₱" . number_format($row['monthly_premium'], 2) . "\n";
        echo "  Employer: ₱" . number_format($row['employer_contribution'], 2) . "\n";
        echo "  Employee: ₱" . number_format($row['employee_contribution'], 2) . "\n\n";
    }
    
    echo "✓ Government benefits setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>