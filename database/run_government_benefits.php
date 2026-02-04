<?php
require_once __DIR__ . '/../config/database.php';

$sql = file_get_contents(__DIR__ . '/add_mandatory_government_benefits.sql');

try {
    $pdo->exec($sql);
    echo "✓ SUCCESS: Government benefits added successfully!\n\n";
    
    // Verify the added benefits
    $stmt = $pdo->query("
        SELECT 
            ip.plan_code,
            ip.plan_name,
            ip.monthly_premium,
            ip.employer_contribution,
            ip.employee_contribution,
            COUNT(ei.id) as enrolled_employees
        FROM insurance_plans ip
        LEFT JOIN employee_insurance ei ON ip.id = ei.insurance_plan_id AND ei.status = 'Active'
        WHERE ip.plan_code IN ('GOV-SSS-2026', 'GOV-PAGIBIG-2026', 'GOV-PHILHEALTH-2026')
        GROUP BY ip.id
        ORDER BY ip.plan_code
    ");
    
    echo "Mandatory Government Benefits Added:\n";
    echo str_repeat("=", 80) . "\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Plan: " . $row['plan_name'] . "\n";
        echo "  Code: " . $row['plan_code'] . "\n";
        echo "  Monthly Premium: ₱" . number_format($row['monthly_premium'], 2) . "\n";
        echo "  Employer: ₱" . number_format($row['employer_contribution'], 2) . "\n";
        echo "  Employee: ₱" . number_format($row['employee_contribution'], 2) . "\n";
        echo "  Enrolled Employees: " . $row['enrolled_employees'] . "\n";
        echo str_repeat("-", 80) . "\n";
    }
    
    // Total cost summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT e.id) as total_employees,
            SUM(ip.monthly_premium) as total_monthly_cost
        FROM employees e
        JOIN employee_insurance ei ON e.id = ei.employee_id AND ei.status = 'Active'
        JOIN insurance_plans ip ON ei.insurance_plan_id = ip.id
        WHERE e.employment_status = 'Active'
        AND ip.plan_code IN ('GOV-SSS-2026', 'GOV-PAGIBIG-2026', 'GOV-PHILHEALTH-2026')
    ");
    
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nSUMMARY:\n";
    echo "Total Active Employees: " . $summary['total_employees'] . "\n";
    echo "Total Monthly Cost (all 3 benefits): ₱" . number_format($summary['total_monthly_cost'], 2) . "\n";
    echo "Per Employee Monthly Cost: ₱" . number_format(6150, 2) . " (SSS ₱4,050 + Pag-IBIG ₱300 + PhilHealth ₱1,800)\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
