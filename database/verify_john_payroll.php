<?php
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("
    SELECT pr.*, pp.period_name, e.first_name, e.last_name
    FROM payroll_records pr 
    JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
    JOIN employees e ON pr.employee_id = e.id
    WHERE e.first_name = 'John' AND e.last_name = 'Doe'
");

echo "=== JOHN DOE PAYROLL RECORDS ===\n\n";
$count = 0;
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $count++;
    echo "Employee: {$row['first_name']} {$row['last_name']}\n";
    echo "Period: {$row['period_name']}\n";
    echo "Basic Salary: ₱" . number_format($row['basic_salary'], 2) . "\n";
    echo "Overtime/Allowances: ₱" . number_format($row['overtime_pay'], 2) . "\n";
    echo "Gross Pay: ₱" . number_format($row['gross_pay'], 2) . "\n";
    echo "Total Deductions: ₱" . number_format($row['total_deductions'], 2) . "\n";
    echo "Net Pay: ₱" . number_format($row['net_pay'], 2) . "\n";
    echo "Status: {$row['status']}\n\n";
}

if ($count === 0) {
    echo "❌ No payroll records found for John Doe\n";
} else {
    echo "✓ Found $count payroll record(s) for John Doe\n";
}
?>