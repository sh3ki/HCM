<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Please login first',
        'code' => 'AUTH_REQUIRED'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                handleGetEmployee($pdo, $_GET['id']);
            } else {
                handleGetEmployees($pdo);
            }
            break;

        case 'POST':
            handleCreateEmployee($pdo);
            break;

        case 'PUT':
            if (isset($_GET['id'])) {
                handleUpdateEmployee($pdo, $_GET['id']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Employee ID required for update',
                    'code' => 'MISSING_ID'
                ]);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                handleDeleteEmployee($pdo, $_GET['id']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Employee ID required for deletion',
                    'code' => 'MISSING_ID'
                ]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed',
                'code' => 'METHOD_NOT_ALLOWED'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("Employee API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'SERVER_ERROR'
    ]);
}

function handleGetEmployees($pdo) {
    try {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $department = $_GET['department'] ?? '';

        // Build WHERE clause
        $whereConditions = [];
        $params = [];

        if (!empty($search)) {
            $whereConditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ? OR e.email LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($status)) {
            $whereConditions[] = "e.employment_status = ?";
            $params[] = $status;
        }

        if (!empty($department)) {
            $whereConditions[] = "d.dept_name = ?";
            $params[] = $department;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count
        $countStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT e.id) as total
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            {$whereClause}
        ");
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();

        // Get employees data
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                e.id,
                e.employee_id,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.email,
                e.phone,
                e.employment_status,
                e.employee_type,
                e.hire_date,
                e.termination_date,
                e.date_of_birth,
                e.gender,
                e.marital_status,
                e.address,
                e.city,
                e.state,
                e.zip_code,
                e.country,
                e.profile_picture,
                e.emergency_contact_name,
                e.emergency_contact_phone,
                e.emergency_contact_relationship,
                d.dept_name as department,
                p.position_title as position,
                ec.basic_salary,
                e.created_at,
                e.updated_at
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            {$whereClause}
            ORDER BY
                CASE e.employment_status
                    WHEN 'Active' THEN 1
                    WHEN 'On Leave' THEN 2
                    WHEN 'Inactive' THEN 3
                    WHEN 'Terminated' THEN 4
                END,
                e.last_name ASC,
                e.first_name ASC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get departments for filter
        $deptStmt = $pdo->prepare("SELECT DISTINCT dept_name FROM departments WHERE dept_name IS NOT NULL ORDER BY dept_name");
        $deptStmt->execute();
        $departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'data' => [
                'employees' => $employees,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$totalCount,
                    'totalPages' => ceil($totalCount / $limit)
                ],
                'filters' => [
                    'departments' => $departments,
                    'statuses' => ['Active', 'On Leave', 'Inactive', 'Terminated']
                ]
            ],
            'message' => 'Employees retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Employees Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleGetEmployee($pdo, $employeeId) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                e.*,
                d.dept_name as department,
                p.position_title as position,
                ec.basic_salary,
                ec.salary_grade_id,
                ec.current_step
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            WHERE e.id = ?
        ");
        $stmt->execute([$employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Employee Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleCreateEmployee($pdo) {
    // Implementation for creating employees
    http_response_code(501);
    echo json_encode([
        'success' => false,
        'error' => 'Create employee not implemented yet',
        'code' => 'NOT_IMPLEMENTED'
    ]);
}

function handleUpdateEmployee($pdo, $employeeId) {
    // Implementation for updating employees
    http_response_code(501);
    echo json_encode([
        'success' => false,
        'error' => 'Update employee not implemented yet',
        'code' => 'NOT_IMPLEMENTED'
    ]);
}

function handleDeleteEmployee($pdo, $employeeId) {
    try {
        // Don't actually delete, just mark as terminated
        $stmt = $pdo->prepare("
            UPDATE employees
            SET employment_status = 'Terminated',
                termination_date = CURDATE(),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$employeeId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Employee not found',
                'code' => 'EMPLOYEE_NOT_FOUND'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'employee_id' => $employeeId,
                'terminated_at' => date('Y-m-d H:i:s')
            ],
            'message' => 'Employee terminated successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Delete Employee Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}
?>