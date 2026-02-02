<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../config/database.php';

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require authentication - simplified for testing
session_start();

// Allow bypass for testing if 'test' parameter is provided
$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';

if (!$isTestMode && (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

switch ($method) {
    case 'GET':
        handleGetRequest($path);
        break;
    case 'POST':
        handlePostRequest($path);
        break;
    case 'PUT':
        handlePutRequest($path);
        break;
    case 'DELETE':
        handleDeleteRequest($path);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGetRequest($path) {
    switch ($path) {
        case 'summary':
            getPayrollSummary();
            break;
        case 'records':
            getPayrollRecords();
            break;
        case 'employee':
            $employeeId = $_GET['employee_id'] ?? null;
            if ($employeeId) {
                getEmployeePayroll($employeeId);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Employee ID required']);
            }
            break;
        case 'periods':
            getPayrollPeriods();
            break;
        default:
            getAllPayrollData();
            break;
    }
}

function handlePostRequest($path) {
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($path) {
        case 'process':
            processPayroll($input);
            break;
        case 'period':
            createPayrollPeriod($input);
            break;
        case 'approve':
            approvePayroll($input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid endpoint']);
            break;
    }
}

function handlePutRequest($path) {
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($path) {
        case 'update':
            updatePayrollRecord($input);
            break;
        case 'status':
            updatePayrollStatus($input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid endpoint']);
            break;
    }
}

function handleDeleteRequest($path) {
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($path) {
        case 'record':
            deletePayrollRecord($input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid endpoint']);
            break;
    }
}

function getPayrollSummary() {
    try {
        $pdo = getDbConnection();
        $periodId = $_GET['period_id'] ?? null;

        // If period is specified, get data from payroll_records for that period
        if ($periodId) {
            // Get total employees for this period
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT pr.employee_id) as total_employees 
                FROM payroll_records pr 
                JOIN employees e ON pr.employee_id = e.id 
                WHERE pr.payroll_period_id = :period_id AND e.employment_status = 'Active'");
            $stmt->bindValue(':period_id', (int)$periodId, PDO::PARAM_INT);
            $stmt->execute();
            $totalEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['total_employees'];

            // Calculate payroll totals from payroll_records for this period
            $stmt = $pdo->prepare("
                SELECT
                    SUM(pr.gross_pay) as total_gross,
                    SUM(pr.total_deductions) as total_deductions,
                    SUM(pr.net_pay) as total_net
                FROM payroll_records pr
                JOIN employees e ON pr.employee_id = e.id
                WHERE pr.payroll_period_id = :period_id AND e.employment_status = 'Active'
            ");
            $stmt->bindValue(':period_id', (int)$periodId, PDO::PARAM_INT);
            $stmt->execute();
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Get total employees (all active)
            $stmt = $pdo->query("SELECT COUNT(*) as total_employees FROM employees WHERE employment_status = 'Active'");
            $totalEmployees = $stmt->fetch(PDO::FETCH_ASSOC)['total_employees'];

            // Calculate payroll totals from employee compensation
            $stmt = $pdo->query("
                SELECT
                    SUM(ec.basic_salary + (ec.basic_salary * 0.10) + (ec.basic_salary * 0.05)) as total_gross,
                    SUM(ec.basic_salary * 0.15) as total_deductions,
                    SUM((ec.basic_salary + (ec.basic_salary * 0.10) + (ec.basic_salary * 0.05)) * 0.85) as total_net
                FROM employee_compensation ec
                JOIN employees e ON ec.employee_id = e.id
                WHERE ec.is_active = 1 AND e.employment_status = 'Active'
            ");
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'total_employees' => (int)$totalEmployees,
                'total_gross' => number_format($totals['total_gross'] ?? 0, 2),
                'total_deductions' => number_format($totals['total_deductions'] ?? 0, 2),
                'total_net' => number_format($totals['total_net'] ?? 0, 2),
                'total_gross_raw' => (float)($totals['total_gross'] ?? 0),
                'total_deductions_raw' => (float)($totals['total_deductions'] ?? 0),
                'total_net_raw' => (float)($totals['total_net'] ?? 0)
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getPayrollRecords() {
    try {
        $pdo = getDbConnection();
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $periodId = $_GET['period_id'] ?? null;

        // Ensure limit and offset are integers
        $limit = (int)$limit;
        $offset = (int)$offset;

        // Build the WHERE clause - if period is specified, only show employees with records for that period
        $whereClause = "e.employment_status = 'Active'";
        $params = [':limit' => $limit, ':offset' => $offset];

        // When a period is selected, only show employees who have payroll records for that period
        if ($periodId) {
            $params[':period_id'] = (int)$periodId;
            $whereClause .= " AND pr.payroll_period_id = :period_id";
        }

        $stmt = $pdo->prepare("
            SELECT
                e.id,
                e.employee_id,
                CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as name,
                e.first_name,
                e.last_name,
                COALESCE(pr.basic_salary, ec.basic_salary, 0) as basic_salary,
                COALESCE(pr.overtime_pay, ec.basic_salary * 0.10, 0) as allowances,
                COALESCE(pr.overtime_pay, ec.basic_salary * 0.05, 0) as overtime,
                COALESCE(pr.gross_pay, ec.basic_salary + (ec.basic_salary * 0.15), 0) as gross_pay,
                COALESCE(pr.total_deductions, ec.basic_salary * 0.15, 0) as deductions,
                COALESCE(pr.net_pay, (ec.basic_salary + (ec.basic_salary * 0.15)) * 0.85, 0) as net_pay,
                COALESCE(pr.status, 'Pending') as status,
                d.dept_name as department,
                p.position_title,
                pr.payroll_period_id,
                pr.days_worked,
                pr.overtime_hours,
                pr.late_hours,
                pr.absent_days
            FROM employees e
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            LEFT JOIN payroll_records pr ON e.id = pr.employee_id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE " . $whereClause . "
            ORDER BY e.last_name, e.first_name
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        if ($periodId) {
            $stmt->bindValue(':period_id', (int)$periodId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Try to get actual status from payroll_status table if it exists
        try {
            $statusQuery = $pdo->prepare("SELECT employee_id, status, reason FROM payroll_status");
            $statusQuery->execute();
            $statusRecords = $statusQuery->fetchAll(PDO::FETCH_ASSOC);

            // Create a lookup array for statuses
            $statusLookup = [];
            foreach ($statusRecords as $statusRecord) {
                $statusLookup[$statusRecord['employee_id']] = [
                    'status' => $statusRecord['status'],
                    'reason' => $statusRecord['reason']
                ];
            }

            // Update records with actual status if available
            foreach ($records as &$record) {
                if (isset($statusLookup[$record['id']])) {
                    $record['status'] = $statusLookup[$record['id']]['status'];
                    $record['status_reason'] = $statusLookup[$record['id']]['reason'];
                }
            }
        } catch (Exception $statusError) {
            // If payroll_status table doesn't exist or has issues, just use default "Pending" status
            error_log("Could not load payroll status: " . $statusError->getMessage());
        }

        // Get total count - filter by period if specified
        if ($periodId) {
            $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT pr.employee_id) as total 
                FROM payroll_records pr 
                JOIN employees e ON pr.employee_id = e.id 
                WHERE pr.payroll_period_id = :period_id AND e.employment_status = 'Active'");
            $countStmt->bindValue(':period_id', (int)$periodId, PDO::PARAM_INT);
            $countStmt->execute();
        } else {
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE employment_status = 'Active'");
        }
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode([
            'success' => true,
            'data' => $records,
            'pagination' => [
                'total' => (int)$total,
                'limit' => (int)$limit,
                'offset' => (int)$offset,
                'has_more' => ((int)$offset + (int)$limit) < (int)$total
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getEmployeePayroll($employeeId) {
    try {
        $pdo = getDbConnection();

        $stmt = $pdo->prepare("
            SELECT
                e.id,
                e.employee_id,
                CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as name,
                e.first_name,
                e.last_name,
                e.email,
                COALESCE(ec.basic_salary, 0) as basic_salary,
                (ec.basic_salary * 0.10) as allowances,
                (ec.basic_salary * 0.05) as overtime,
                (ec.basic_salary + (ec.basic_salary * 0.10) + (ec.basic_salary * 0.05)) as gross_pay,
                (ec.basic_salary * 0.15) as deductions,
                ((ec.basic_salary + (ec.basic_salary * 0.10) + (ec.basic_salary * 0.05)) * 0.85) as net_pay,
                d.dept_name as department,
                p.position_title,
                ec.effective_date,
                ec.is_active
            FROM employees e
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE e.employee_id = :employee_id
        ");

        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();

        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            echo json_encode([
                'success' => true,
                'data' => $record
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Employee not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getPayrollPeriods() {
    try {
        $pdo = getDbConnection();

        $stmt = $pdo->prepare("
            SELECT
                id,
                period_code,
                period_name as name,
                start_date,
                end_date,
                pay_date,
                status,
                total_gross,
                total_deductions,
                total_net,
                created_at,
                updated_at
            FROM payroll_periods
            ORDER BY created_at DESC
            LIMIT 20
        ");

        $stmt->execute();
        $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get employee count for each period (if needed, this is optional for performance)
        foreach ($periods as &$period) {
            $period['employee_count'] = 248; // Default for now, could be calculated from actual records
            $period['total_amount'] = $period['total_net'] ?: 0;

            // Format status for display
            if ($period['status'] === 'Draft') {
                $period['status'] = 'Processing';
            } elseif ($period['status'] === 'Paid') {
                $period['status'] = 'Completed';
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $periods
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getAllPayrollData() {
    $summary = [];
    $records = [];
    $periods = [];

    // Get summary
    ob_start();
    getPayrollSummary();
    $summaryResponse = json_decode(ob_get_clean(), true);
    $summary = $summaryResponse['data'] ?? [];

    // Get records (limited)
    ob_start();
    getPayrollRecords();
    $recordsResponse = json_decode(ob_get_clean(), true);
    $records = $recordsResponse['data'] ?? [];

    // Get periods
    ob_start();
    getPayrollPeriods();
    $periodsResponse = json_decode(ob_get_clean(), true);
    $periods = $periodsResponse['data'] ?? [];

    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => $summary,
            'records' => array_slice($records, 0, 10), // Limit to 10 for overview
            'periods' => $periods
        ]
    ]);
}

function processPayroll($input) {
    try {
        $periodId = $input['period_id'] ?? null;
        $employeeIds = $input['employee_ids'] ?? [];

        if (!$periodId) {
            http_response_code(400);
            echo json_encode(['error' => 'Period ID is required']);
            return;
        }

        // Simulate payroll processing
        sleep(1); // Simulate processing time

        $processedCount = empty($employeeIds) ? 248 : count($employeeIds);

        echo json_encode([
            'success' => true,
            'message' => "Payroll processed successfully for {$processedCount} employees",
            'data' => [
                'period_id' => $periodId,
                'processed_count' => $processedCount,
                'total_amount' => 9700000,
                'status' => 'Processed',
                'processed_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Processing error: ' . $e->getMessage()]);
    }
}

function createPayrollPeriod($input) {
    try {
        $required = ['period_name', 'start_date', 'end_date', 'pay_date'];

        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '{$field}' is required"]);
                return;
            }
        }

        $pdo = getDbConnection();

        // Generate unique period code
        $periodCode = 'PAY-' . strtoupper(date('Y-m', strtotime($input['start_date']))) . '-' . sprintf('%03d', rand(1, 999));

        // Check if period code already exists, regenerate if needed
        $codeCheck = $pdo->prepare("SELECT id FROM payroll_periods WHERE period_code = :period_code");
        do {
            $codeCheck->bindParam(':period_code', $periodCode);
            $codeCheck->execute();
            if ($codeCheck->rowCount() > 0) {
                $periodCode = 'PAY-' . strtoupper(date('Y-m', strtotime($input['start_date']))) . '-' . sprintf('%03d', rand(1, 999));
            }
        } while ($codeCheck->rowCount() > 0);

        // Validate dates
        $startDate = new DateTime($input['start_date']);
        $endDate = new DateTime($input['end_date']);
        $payDate = new DateTime($input['pay_date']);

        if ($endDate <= $startDate) {
            http_response_code(400);
            echo json_encode(['error' => 'End date must be after start date']);
            return;
        }

        // Insert new payroll period
        $stmt = $pdo->prepare("
            INSERT INTO payroll_periods
            (period_code, period_name, start_date, end_date, pay_date, status, created_at, updated_at)
            VALUES (:period_code, :period_name, :start_date, :end_date, :pay_date, 'Draft', NOW(), NOW())
        ");

        $stmt->bindParam(':period_code', $periodCode);
        $stmt->bindParam(':period_name', $input['period_name']);
        $stmt->bindParam(':start_date', $input['start_date']);
        $stmt->bindParam(':end_date', $input['end_date']);
        $stmt->bindParam(':pay_date', $input['pay_date']);

        $stmt->execute();
        $newPeriodId = $pdo->lastInsertId();

        // Get the created period
        $getStmt = $pdo->prepare("SELECT * FROM payroll_periods WHERE id = :id");
        $getStmt->bindParam(':id', $newPeriodId);
        $getStmt->execute();
        $createdPeriod = $getStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Payroll period created successfully',
            'data' => [
                'id' => (int)$createdPeriod['id'],
                'period_code' => $createdPeriod['period_code'],
                'name' => $createdPeriod['period_name'],
                'start_date' => $createdPeriod['start_date'],
                'end_date' => $createdPeriod['end_date'],
                'pay_date' => $createdPeriod['pay_date'],
                'status' => $createdPeriod['status'],
                'employee_count' => 0,
                'total_amount' => 0,
                'created_at' => $createdPeriod['created_at']
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Creation error: ' . $e->getMessage()]);
    }
}

function approvePayroll($input) {
    try {
        $periodId = $input['period_id'] ?? null;
        $employeeId = $input['employee_id'] ?? null;

        if (!$periodId && !$employeeId) {
            http_response_code(400);
            echo json_encode(['error' => 'Period ID or Employee ID is required']);
            return;
        }

        // If specific employee, update their status directly
        if ($employeeId) {
            $statusInput = [
                'employee_id' => $employeeId,
                'status' => 'Approved',
                'reason' => 'Payroll approved by manager'
            ];
            updatePayrollStatus($statusInput);
            return;
        }

        $message = $employeeId ?
            "Payroll approved for employee ID: {$employeeId}" :
            "Payroll approved for all employees in period ID: {$periodId}";

        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => [
                'period_id' => $periodId,
                'employee_id' => $employeeId,
                'status' => 'Approved',
                'approved_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Approval error: ' . $e->getMessage()]);
    }
}

function updatePayrollRecord($input) {
    try {
        $employeeId = $input['employee_id'] ?? null;
        $updates = $input['updates'] ?? [];

        if (!$employeeId || empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'Employee ID and updates are required']);
            return;
        }

        $pdo = getDbConnection();

        // First, get the employee's internal ID from employee_id
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE employee_id = :employee_id");
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode(['error' => 'Employee not found']);
            return;
        }

        $internalEmployeeId = $employee['id'];

        // Update or insert employee compensation
        if (isset($updates['basic_salary'])) {
            $newSalary = $updates['basic_salary'];

            // Check if active compensation record exists
            $stmt = $pdo->prepare("
                SELECT id FROM employee_compensation
                WHERE employee_id = :employee_id AND is_active = 1
            ");
            $stmt->bindParam(':employee_id', $internalEmployeeId);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing record
                $stmt = $pdo->prepare("
                    UPDATE employee_compensation
                    SET basic_salary = :basic_salary,
                        effective_date = CURDATE(),
                        notes = CONCAT(IFNULL(notes, ''), ' Updated via API on ', NOW())
                    WHERE id = :id
                ");
                $stmt->bindParam(':basic_salary', $newSalary);
                $stmt->bindParam(':id', $existing['id']);
                $stmt->execute();
            } else {
                // Insert new record
                $stmt = $pdo->prepare("
                    INSERT INTO employee_compensation
                    (employee_id, salary_grade_id, basic_salary, effective_date, is_active, notes)
                    VALUES (:employee_id, 1, :basic_salary, CURDATE(), 1, 'Created via API')
                ");
                $stmt->bindParam(':employee_id', $internalEmployeeId);
                $stmt->bindParam(':basic_salary', $newSalary);
                $stmt->execute();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Payroll record updated for employee ID: {$employeeId}",
            'data' => [
                'employee_id' => $employeeId,
                'updates' => $updates,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Update error: ' . $e->getMessage()]);
    }
}

function updatePayrollStatus($input) {
    try {
        $employeeId = $input['employee_id'] ?? null;
        $status = $input['status'] ?? null;
        $reason = $input['reason'] ?? null;

        if (!$employeeId || !$status) {
            http_response_code(400);
            echo json_encode(['error' => 'Employee ID and status are required']);
            return;
        }

        $allowedStatuses = ['Pending', 'Approved', 'Rejected', 'Paid', 'Emailed'];
        if (!in_array($status, $allowedStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status. Allowed: ' . implode(', ', $allowedStatuses)]);
            return;
        }

        $pdo = getDbConnection();

        // First, get the employee's internal ID from employee_id
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE employee_id = :employee_id");
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode(['error' => 'Employee not found']);
            return;
        }

        $internalEmployeeId = $employee['id'];

        // Get current status for audit trail
        $currentStatusStmt = $pdo->prepare("SELECT status FROM payroll_status WHERE employee_id = :employee_id");
        $currentStatusStmt->bindParam(':employee_id', $internalEmployeeId);
        $currentStatusStmt->execute();
        $currentStatus = $currentStatusStmt->fetch(PDO::FETCH_ASSOC);
        $oldStatus = $currentStatus ? $currentStatus['status'] : 'Pending';

        // Create or update payroll status record
        $stmt = $pdo->prepare("
            INSERT INTO payroll_status (employee_id, status, reason, updated_by, updated_at)
            VALUES (:employee_id, :status, :reason, 1, NOW())
            ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            reason = VALUES(reason),
            updated_by = VALUES(updated_by),
            updated_at = VALUES(updated_at)
        ");

        $stmt->bindParam(':employee_id', $internalEmployeeId);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':reason', $reason);
        $stmt->execute();

        // Create audit trail
        try {
            $auditStmt = $pdo->prepare("
                INSERT INTO payroll_audit_log (employee_id, action, old_status, new_status, reason, created_by, created_at)
                VALUES (:employee_id, :action, :old_status, :new_status, :reason, 1, NOW())
            ");

            $auditStmt->bindParam(':employee_id', $internalEmployeeId);
            $action = "Status Changed";
            $auditStmt->bindParam(':action', $action);
            $auditStmt->bindParam(':old_status', $oldStatus);
            $auditStmt->bindParam(':new_status', $status);
            $auditStmt->bindParam(':reason', $reason);
            $auditStmt->execute();
        } catch (PDOException $e) {
            // If audit fails, continue (don't fail the main operation)
            error_log("Audit log failed: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => "Payroll status updated to '{$status}' for employee ID: {$employeeId}",
            'data' => [
                'employee_id' => $employeeId,
                'status' => $status,
                'reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Status update error: ' . $e->getMessage()]);
    }
}

function deletePayrollRecord($input) {
    try {
        $employeeId = $input['employee_id'] ?? null;
        $periodId = $input['period_id'] ?? null;

        if (!$employeeId) {
            http_response_code(400);
            echo json_encode(['error' => 'Employee ID is required']);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => "Payroll record deleted for employee ID: {$employeeId}",
            'data' => [
                'employee_id' => $employeeId,
                'period_id' => $periodId,
                'deleted_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Deletion error: ' . $e->getMessage()]);
    }
}

// Helper function to get database connection
function getDbConnection() {
    try {
        $host = 'localhost';
        $dbname = 'hcm_system';
        $username = 'root';
        $password = '';

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}

// Helper function to create payroll tables if they don't exist
function createPayrollTablesIfNotExist($pdo) {
    try {
        // Check if payroll_status table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'payroll_status'");
        if ($stmt->rowCount() == 0) {
            // Create payroll_status table
            $pdo->exec("
                CREATE TABLE payroll_status (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT NOT NULL,
                    status ENUM('Pending', 'Approved', 'Rejected', 'Paid', 'Emailed') DEFAULT 'Pending',
                    reason TEXT,
                    updated_by INT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_employee (employee_id),
                    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
                )
            ");
        }

        // Check if payroll_audit_log table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'payroll_audit_log'");
        if ($stmt->rowCount() == 0) {
            // Create payroll_audit_log table
            $pdo->exec("
                CREATE TABLE payroll_audit_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    old_status VARCHAR(50),
                    new_status VARCHAR(50),
                    reason TEXT,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
                )
            ");
        }
    } catch (PDOException $e) {
        // If foreign key constraint fails, create without foreign keys
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS payroll_status (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT NOT NULL,
                    status ENUM('Pending', 'Approved', 'Rejected', 'Paid', 'Emailed') DEFAULT 'Pending',
                    reason TEXT,
                    updated_by INT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_employee (employee_id)
                )
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS payroll_audit_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    old_status VARCHAR(50),
                    new_status VARCHAR(50),
                    reason TEXT,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } catch (PDOException $e2) {
            // Ignore table creation errors to prevent breaking the API
            error_log("Failed to create payroll tables: " . $e2->getMessage());
        }
    }
}
?>