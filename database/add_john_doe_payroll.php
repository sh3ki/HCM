<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Get John Doe's employee ID
    $stmt = $conn->query("SELECT id, first_name, last_name FROM employees WHERE first_name = 'John' AND last_name = 'Doe' LIMIT 1");
    $johnDoe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$johnDoe) {
        echo "❌ John Doe not found in database\n";
        exit(1);
    }
    
    echo "✓ Found John Doe (ID: {$johnDoe['id']})\n\n";
    
    // Get his compensation to calculate payroll
    $stmt = $conn->prepare("SELECT * FROM employee_compensation WHERE employee_id = ? ORDER BY effective_date DESC LIMIT 1");
    $stmt->execute([$johnDoe['id']]);
    $compensation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$compensation) {
        echo "❌ No compensation record found for John Doe\n";
        exit(1);
    }
    
    $basicSalary = $compensation['basic_salary'];
    echo "✓ Basic Salary: ₱" . number_format($basicSalary, 2) . "\n\n";
    
    // Get current payroll period (January 2025)
    $stmt = $conn->query("SELECT * FROM payroll_periods WHERE period_name = 'January 2025' LIMIT 1");
    $period = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$period) {
        echo "Creating January 2025 payroll period...\n";
        $conn->exec("
            INSERT INTO payroll_periods (period_name, start_date, end_date, pay_date, status)
            VALUES ('January 2025', '2025-01-01', '2025-01-31', '2026-03-06', 'Processing')
        ");
        $periodId = $conn->lastInsertId();
        echo "✓ Created period ID: $periodId\n\n";
    } else {
        $periodId = $period['id'];
        echo "✓ Using existing period: {$period['period_name']} (ID: $periodId)\n\n";
    }
    
    // Check if payroll record already exists
    $stmt = $conn->prepare("SELECT id FROM payroll_records WHERE employee_id = ? AND payroll_period_id = ?");
    $stmt->execute([$johnDoe['id'], $periodId]);
    if ($stmt->fetch()) {
        echo "⚠ Payroll record already exists for John Doe in January 2025\n";
        exit(0);
    }
    
    // Calculate payroll components
    $allowances = 2000.00; // Transportation allowance
    $overtime = 0.00;
    $grossPay = $basicSalary + $allowances + $overtime;
    
    // Deductions
    $sssContribution = 1350.00; // From government benefits
    $pagibigContribution = 100.00;
    $philhealthContribution = 900.00;
    $withholdingTax = $basicSalary * 0.15; // 15% tax
    $totalDeductions = $sssContribution + $pagibigContribution + $philhealthContribution + $withholdingTax;
    
    $netPay = $grossPay - $totalDeductions;
    
    echo "=== PAYROLL CALCULATION ===\n";
    echo "Basic Salary: ₱" . number_format($basicSalary, 2) . "\n";
    echo "Allowances (via overtime_pay): ₱" . number_format($allowances, 2) . "\n";
    echo "Gross Pay: ₱" . number_format($grossPay, 2) . "\n";
    echo "\nDeductions:\n";
    echo "  SSS: ₱" . number_format($sssContribution, 2) . "\n";
    echo "  Pag-IBIG: ₱" . number_format($pagibigContribution, 2) . "\n";
    echo "  PhilHealth: ₱" . number_format($philhealthContribution, 2) . "\n";
    echo "  Withholding Tax (late_deductions): ₱" . number_format($withholdingTax, 2) . "\n";
    echo "Total Deductions: ₱" . number_format($totalDeductions, 2) . "\n";
    echo "Net Pay: ₱" . number_format($netPay, 2) . "\n\n";
    
    // Insert payroll record - map to available columns
    $stmt = $conn->prepare("
        INSERT INTO payroll_records (
            employee_id, payroll_period_id, basic_salary, overtime_pay,
            gross_pay, total_deductions, net_pay, days_worked, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 22, 'Approved')
    ");
    
    $stmt->execute([
        $johnDoe['id'],
        $periodId,
        $basicSalary,
        $allowances, // Store allowances in overtime_pay field
        $grossPay,
        $totalDeductions,
        $netPay
    ]);
    
    echo "✓ Payroll record created successfully!\n";
    echo "✓ Record ID: " . $conn->lastInsertId() . "\n\n";
    
    echo "=== VERIFICATION ===\n";
    $stmt = $conn->prepare("
        SELECT pr.*, pp.period_name, e.first_name, e.last_name
        FROM payroll_records pr
        JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
        JOIN employees e ON pr.employee_id = e.id
        WHERE pr.employee_id = ? AND pr.payroll_period_id = ?
    ");
    $stmt->execute([$johnDoe['id'], $periodId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Employee: {$record['first_name']} {$record['last_name']}\n";
    echo "Period: {$record['period_name']}\n";
    echo "Gross Pay: ₱" . number_format($record['gross_pay'], 2) . "\n";
    echo "Net Pay: ₱" . number_format($record['net_pay'], 2) . "\n";
    echo "Status: {$record['status']}\n";
    
    echo "\n✓ All done! John Doe now has payroll records.\n";
    
} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>