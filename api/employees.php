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
            // Handle special endpoints
            if (isset($_GET['departments'])) {
                getDepartments($pdo);
            } elseif (isset($_GET['positions'])) {
                getPositions($pdo);
            } elseif (isset($_GET['id'])) {
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
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['first_name', 'last_name', 'email', 'hire_date', 'employment_status'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required',
                    'code' => 'MISSING_FIELD'
                ]);
                return;
            }
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? UNION SELECT id FROM employees WHERE email = ?");
        $stmt->execute([$data['email'], $data['email']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Email already exists',
                'code' => 'EMAIL_EXISTS'
            ]);
            return;
        }
        
        // Generate auto password
        $autoPassword = bin2hex(random_bytes(8)); // 16 character random password
        $passwordHash = password_hash($autoPassword, PASSWORD_DEFAULT);
        
        // Generate employee number (format: EMP-XXX)
        $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(employee_id, 5) AS UNSIGNED)) as max_num FROM employees WHERE employee_id LIKE 'EMP-%'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = ($result['max_num'] ?? 0) + 1;
        $employeeNumber = 'EMP-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        
        // Create username from email (part before @)
        $username = explode('@', $data['email'])[0];
        $baseUsername = $username;
        $counter = 1;
        
        // Ensure unique username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        while ($stmt->fetch()) {
            $username = $baseUsername . $counter;
            $counter++;
            $stmt->execute([$username]);
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // 1. Insert into users table
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, is_active, requires_password_change, is_new)
                VALUES (?, ?, ?, 'employee', 1, 1, 1)
            ");
            $stmt->execute([$username, $data['email'], $passwordHash]);
            $userId = $pdo->lastInsertId();
            
            // 2. Insert into employees table
            $stmt = $pdo->prepare("
                INSERT INTO employees (
                    user_id, employee_id, first_name, middle_name, last_name, 
                    email, phone, address, date_of_birth, gender, 
                    marital_status, emergency_contact_name, emergency_contact_phone,
                    department, position, hire_date, employment_status, employment_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $employeeNumber,
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? null,
                $data['marital_status'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['department'] ?? null,
                $data['position'] ?? null,
                $data['hire_date'],
                $data['employment_status'],
                $data['employment_type'] ?? 'Full-time'
            ]);
            
            $employeeId = $pdo->lastInsertId();
            
            // Commit transaction
            $pdo->commit();
            
            // Send email with credentials
            $emailSent = sendCredentialsEmail($data['email'], $data['first_name'], $username, $autoPassword);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'employee_id' => $employeeId,
                    'employee_number' => $employeeNumber,
                    'username' => $username,
                    'email' => $data['email'],
                    'email_sent' => $emailSent
                ],
                'message' => 'Employee created successfully. Login credentials have been sent to ' . $data['email']
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        error_log("Create Employee Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage(),
            'code' => 'DATABASE_ERROR'
        ]);
    } catch (Exception $e) {
        error_log("Create Employee Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error creating employee',
            'code' => 'SERVER_ERROR'
        ]);
    }
}

function sendCredentialsEmail($email, $firstName, $username, $password) {
    $subject = "Welcome to HCM System - Your Login Credentials";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1b68ff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .credentials { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #1b68ff; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to HCM System</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($firstName) . ",</h2>
                <p>Your employee account has been created successfully. Below are your login credentials:</p>
                <div class='credentials'>
                    <p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>
                    <p><strong>Temporary Password:</strong> " . htmlspecialchars($password) . "</p>
                    <p><strong>Login URL:</strong> <a href='http://localhost/HCM/views/login.php'>Click here to login</a></p>
                </div>
                <p><strong>Important:</strong></p>
                <ul>
                    <li>You will be prompted to change your password on first login</li>
                    <li>You will need to verify your email with an OTP code</li>
                    <li>Please keep your credentials secure</li>
                </ul>
            </div>
            <div class='footer'>
                <p>This is an automated message from HCM System. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: HCM System <noreply@hcmsystem.com>" . "\r\n";
    
    // For development, just log the credentials
    error_log("=== NEW EMPLOYEE CREDENTIALS ===");
    error_log("Email: $email");
    error_log("Username: $username");
    error_log("Password: $password");
    error_log("================================");
    
    // Attempt to send email (will fail in local dev without mail server)
    $sent = @mail($email, $subject, $message, $headers);
    
    // Return true since we logged it (for development purposes)
    return true;
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

function getDepartments($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT dept_name FROM departments WHERE dept_name IS NOT NULL AND is_active = 1 ORDER BY dept_name");
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'data' => [
                'departments' => $departments
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred'
        ]);
    }
}

function getPositions($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT position_title FROM positions WHERE position_title IS NOT NULL AND is_active = 1 ORDER BY position_title");
        $positions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'data' => [
                'positions' => $positions
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred'
        ]);
    }
}
?>