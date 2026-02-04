<?php
/**
 * Generate payslip and tax deduction records from payroll records
 * This ensures employee payslip page can display the data
 */

require_once __DIR__ . '/../includes/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "=== SYNCING PAYROLL RECORDS TO EMPLOYEE PAYSLIPS ===\n\n";
    
    // Get all payroll records that don't have corresponding payslip records
    $stmt = $conn->query("
        SELECT pr.*, pp.period_name, pp.start_date, pp.end_date, pp.pay_date,
               e.first_name, e.last_name
        FROM payroll_records pr
        JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
        JOIN employees e ON pr.employee_id = e.id
        WHERE pr.status IN ('Approved', 'Paid')
        ORDER BY pr.created_at DESC
    ");
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "No payroll records found to sync.\n";
        exit(0);
    }
    
    echo "Found " . count($records) . " payroll record(s) to sync\n\n";
    
    // Check if payslip_records table exists, if not create it
    try {
        $conn->query("DESCRIBE payslip_records LIMIT 1");
    } catch (PDOException $e) {
        echo "Creating payslip_records table...\n";
        $conn->exec("
            CREATE TABLE IF NOT EXISTS payslip_records (
                id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id INT NOT NULL,
                payroll_period_id INT NOT NULL,
                pay_period_start DATE NOT NULL,
                pay_period_end DATE NOT NULL,
                pay_date DATE NOT NULL,
                basic_salary DECIMAL(10,2),
                allowances DECIMAL(10,2),
                overtime_pay DECIMAL(10,2),
                gross_pay DECIMAL(10,2),
                sss_contribution DECIMAL(10,2),
                pagibig_contribution DECIMAL(10,2),
                philhealth_contribution DECIMAL(10,2),
                withholding_tax DECIMAL(10,2),
                total_deductions DECIMAL(10,2),
                net_pay DECIMAL(10,2),
                status VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_payslip (employee_id, payroll_period_id),
                FOREIGN KEY (employee_id) REFERENCES employees(id),
                FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id)
            )
        ");
        echo "✓ Created payslip_records table\n\n";
    }
    
    // Check if employee_tax_records table exists
    try {
        $conn->query("DESCRIBE employee_tax_records LIMIT 1");
    } catch (PDOException $e) {
        echo "Creating employee_tax_records table...\n";
        $conn->exec("
            CREATE TABLE IF NOT EXISTS employee_tax_records (
                id INT PRIMARY KEY AUTO_INCREMENT,
                employee_id INT NOT NULL,
                year INT NOT NULL,
                month INT NOT NULL,
                income_tax DECIMAL(10,2) DEFAULT 0,
                sss_contribution DECIMAL(10,2) DEFAULT 0,
                philhealth_contribution DECIMAL(10,2) DEFAULT 0,
                pagibig_contribution DECIMAL(10,2) DEFAULT 0,
                total_deductions DECIMAL(10,2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_tax_record (employee_id, year, month),
                FOREIGN KEY (employee_id) REFERENCES employees(id)
            )
        ");
        echo "✓ Created employee_tax_records table\n\n";
    }
    
    $synced = 0;
    $errors = 0;
    
    // Sync payroll records to payslip records
    foreach ($records as $record) {
        try {
            // Check if payslip record already exists
            $checkStmt = $conn->prepare("
                SELECT id FROM payslip_records 
                WHERE employee_id = ? AND payroll_period_id = ?
            ");
            $checkStmt->execute([$record['employee_id'], $record['payroll_period_id']]);
            
            if ($checkStmt->fetch()) {
                echo "⊘ Payslip already exists: {$record['first_name']} {$record['last_name']} - {$record['period_name']}\n";
                continue;
            }
            
            // Insert payslip record
            $insertStmt = $conn->prepare("
                INSERT INTO payslip_records (
                    employee_id, payroll_period_id, pay_period_start, pay_period_end, 
                    pay_date, basic_salary, overtime_pay, gross_pay,
                    total_deductions, net_pay, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $insertStmt->execute([
                $record['employee_id'],
                $record['payroll_period_id'],
                $record['start_date'],
                $record['end_date'],
                $record['pay_date'],
                $record['basic_salary'],
                $record['overtime_pay'],
                $record['gross_pay'],
                $record['total_deductions'],
                $record['net_pay'],
                $record['status']
            ]);
            
            // Insert/update tax record
            $taxYear = date('Y', strtotime($record['pay_date']));
            $taxMonth = date('n', strtotime($record['pay_date']));
            
            // Calculate deductions (extract from total_deductions if available)
            $incomeTax = $record['total_deductions'] * 0.87; // Estimate 87% is income tax
            
            $taxStmt = $conn->prepare("
                INSERT INTO employee_tax_records (
                    employee_id, year, month, income_tax, sss_contribution,
                    philhealth_contribution, pagibig_contribution, total_deductions
                ) VALUES (?, ?, ?, ?, 1350, 900, 100, ?)
                ON DUPLICATE KEY UPDATE
                    income_tax = ?,
                    total_deductions = ?
            ");
            
            $totalTax = 1350 + 900 + 100; // SSS + PhilHealth + Pag-IBIG
            $taxStmt->execute([
                $record['employee_id'],
                $taxYear,
                $taxMonth,
                $incomeTax,
                $record['total_deductions'],
                $incomeTax,
                $record['total_deductions']
            ]);
            
            echo "✓ Synced: {$record['first_name']} {$record['last_name']} - {$record['period_name']}\n";
            $synced++;
            
        } catch (Exception $e) {
            echo "✗ Error syncing {$record['first_name']} {$record['last_name']}: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n=== SYNC COMPLETE ===\n";
    echo "✓ Synced: $synced records\n";
    echo "✗ Errors: $errors\n";
    
} catch (Exception $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>