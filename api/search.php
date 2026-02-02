<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit();
}

try {
    handleSearch($pdo);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'code' => 'SERVER_ERROR'
    ]);
}

function handleSearch($pdo) {
    $query = $_GET['q'] ?? '';
    $context = $_GET['context'] ?? 'global'; // New: context parameter (employees, reports, payroll, leaves, benefits, attendance, etc.)
    $limit = min((int)($_GET['limit'] ?? 10), 50); // Max 50 results

    if (strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'data' => [
                'employees' => [],
                'departments' => [],
                'positions' => [],
                'reports' => [],
                'payroll' => [],
                'leaves' => [],
                'benefits' => [],
                'total' => 0
            ],
            'message' => 'Query too short'
        ]);
        return;
    }

    $searchTerm = '%' . $query . '%';
    $results = [
        'employees' => [],
        'departments' => [],
        'positions' => [],
        'reports' => [],
        'payroll' => [],
        'leaves' => [],
        'benefits' => [],
        'total' => 0
    ];

    // Context-aware search logic
    if ($context === 'employees' || $context === 'global') {
        // Search employees
        $stmt = $pdo->prepare("
            SELECT
                e.id,
                e.employee_id,
                e.first_name,
                e.last_name,
                e.email,
                e.phone,
                d.dept_name,
                p.position_title,
                'employee' as type
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE
                e.first_name LIKE ? OR
                e.last_name LIKE ? OR
                e.email LIKE ? OR
                e.employee_id LIKE ? OR
                CONCAT(e.first_name, ' ', e.last_name) LIKE ?
            ORDER BY e.first_name, e.last_name
            LIMIT ?
        ");

        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        $results['employees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Search departments (only in employee and global context)
        if ($context === 'global') {
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    dept_name as name,
                    dept_code as description,
                    'department' as type
                FROM departments
                WHERE
                    dept_name LIKE ? OR
                    dept_code LIKE ?
                ORDER BY dept_name
                LIMIT ?
            ");

            $stmt->execute([$searchTerm, $searchTerm, $limit]);
            $results['departments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Search positions (only in global context)
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    position_title as name,
                    job_description as description,
                    'position' as type
                FROM positions
                WHERE
                    position_title LIKE ? OR
                    job_description LIKE ?
                ORDER BY position_title
                LIMIT ?
            ");

            $stmt->execute([$searchTerm, $searchTerm, $limit]);
            $results['positions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // Search in payroll context
    if ($context === 'payroll') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                e.id,
                e.employee_id,
                e.first_name,
                e.last_name,
                e.email,
                d.dept_name,
                p.position_title,
                'employee' as type
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN payroll pr ON e.id = pr.employee_id
            WHERE
                e.first_name LIKE ? OR
                e.last_name LIKE ? OR
                e.employee_id LIKE ? OR
                CONCAT(e.first_name, ' ', e.last_name) LIKE ?
            ORDER BY e.first_name, e.last_name
            LIMIT ?
        ");

        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        $results['employees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Search in leaves context
    if ($context === 'leaves') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                e.id,
                e.employee_id,
                e.first_name,
                e.last_name,
                e.email,
                d.dept_name,
                l.leave_type,
                l.status as leave_status,
                'employee' as type
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN leaves l ON e.id = l.employee_id
            WHERE
                e.first_name LIKE ? OR
                e.last_name LIKE ? OR
                e.employee_id LIKE ? OR
                CONCAT(e.first_name, ' ', e.last_name) LIKE ? OR
                l.leave_type LIKE ?
            ORDER BY e.first_name, e.last_name
            LIMIT ?
        ");

        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        $results['employees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Search in reports context
    if ($context === 'reports') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                e.id,
                e.employee_id,
                e.first_name,
                e.last_name,
                e.email,
                d.dept_name,
                p.position_title,
                'employee' as type
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE
                e.first_name LIKE ? OR
                e.last_name LIKE ? OR
                e.employee_id LIKE ? OR
                CONCAT(e.first_name, ' ', e.last_name) LIKE ? OR
                d.dept_name LIKE ?
            ORDER BY e.first_name, e.last_name
            LIMIT ?
        ");

        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        $results['employees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Search in benefits context
    if ($context === 'benefits') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                e.id,
                e.employee_id,
                e.first_name,
                e.last_name,
                e.email,
                d.dept_name,
                'employee' as type
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN employee_benefits eb ON e.id = eb.employee_id
            WHERE
                e.first_name LIKE ? OR
                e.last_name LIKE ? OR
                e.employee_id LIKE ? OR
                CONCAT(e.first_name, ' ', e.last_name) LIKE ?
            ORDER BY e.first_name, e.last_name
            LIMIT ?
        ");

        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        $results['employees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Search in attendance context
    if ($context === 'attendance') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                e.id,
                e.employee_id,
                e.first_name,
                e.last_name,
                e.email,
                d.dept_name,
                'employee' as type
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN attendance a ON e.id = a.employee_id
            WHERE
                e.first_name LIKE ? OR
                e.last_name LIKE ? OR
                e.employee_id LIKE ? OR
                CONCAT(e.first_name, ' ', e.last_name) LIKE ?
            ORDER BY e.first_name, e.last_name
            LIMIT ?
        ");

        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        $results['employees'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $results['total'] = count($results['employees']) + count($results['departments']) + count($results['positions']);

    echo json_encode([
        'success' => true,
        'data' => $results,
        'query' => $query,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>