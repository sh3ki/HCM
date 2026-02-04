<?php
require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== CHECKING JOHN DOE USER ACCOUNT ===\n\n";

$stmt = $conn->query("SELECT u.id, u.username, e.id as employee_id, e.first_name, e.last_name FROM users u LEFT JOIN employees e ON u.employee_id = e.id WHERE e.first_name = 'John' AND e.last_name = 'Doe'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "✓ User found:\n";
    echo "  Username: {$result['username']}\n";
    echo "  User ID: {$result['id']}\n";
    echo "  Employee ID: {$result['employee_id']}\n";
    echo "  Name: {$result['first_name']} {$result['last_name']}\n\n";
} else {
    echo "❌ No user found for John Doe\n\n";
}

echo "=== CHECKING PAYROLL RECORDS ===\n\n";
$stmt = $conn->query("SELECT pr.id, pr.employee_id, pr.gross_pay, pr.net_pay, pp.period_name FROM payroll_records pr JOIN payroll_periods pp ON pr.payroll_period_id = pp.id WHERE pr.employee_id = (SELECT id FROM employees WHERE first_name = 'John' AND last_name = 'Doe')");
$payrollRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($payrollRecords) . " payroll record(s)\n";
foreach ($payrollRecords as $pr) {
    echo "  - {$pr['period_name']}: ₱" . number_format($pr['gross_pay'], 2) . " gross\n";
}

echo "\n=== CHECKING PAYSLIP RECORDS ===\n\n";
$stmt = $conn->query("SELECT ps.id, ps.employee_id, ps.gross_pay, ps.net_pay FROM payslip_records ps WHERE ps.employee_id = (SELECT id FROM employees WHERE first_name = 'John' AND last_name = 'Doe')");
$payslipRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($payslipRecords) . " payslip record(s)\n";
foreach ($payslipRecords as $ps) {
    echo "  - Gross: ₱" . number_format($ps['gross_pay'], 2) . ", Net: ₱" . number_format($ps['net_pay'], 2) . "\n";
}
?>
