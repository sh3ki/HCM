<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config/database.php';

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
            handleGetProfile($pdo, $user_id);
            break;

        case 'PUT':
            handleUpdateProfile($pdo, $user_id);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['action']) && $input['action'] === 'change_password') {
                handleChangePassword($pdo, $user_id);
            } else {
                handleUpdateProfile($pdo, $user_id);
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
    error_log("Profile API Error: " . $e->getMessage());

    // Clear any output buffer to prevent HTML errors from breaking JSON
    if (ob_get_level()) {
        ob_clean();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'code' => 'SERVER_ERROR'
    ]);
}

function handleGetProfile($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.is_active, u.last_login,
                   r.role_name, r.permissions,
                   e.employee_id, e.first_name, e.middle_name, e.last_name,
                   e.email as employee_email, e.phone, e.date_of_birth,
                   e.address, e.city, e.state, e.zip_code, e.country,
                   e.hire_date, e.employment_status, e.employee_type,
                   e.emergency_contact_name, e.emergency_contact_phone, e.emergency_contact_relationship,
                   d.dept_name as department_name,
                   p.position_title,
                   ec.basic_salary
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN employees e ON u.id = e.user_id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employee_compensation ec ON e.id = ec.employee_id AND ec.is_active = 1
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'User profile not found',
                'code' => 'PROFILE_NOT_FOUND'
            ]);
            return;
        }

        // Remove sensitive data
        unset($user['permissions']);
        if (isset($user['basic_salary'])) {
            $user['basic_salary'] = '***HIDDEN***';
        }

        echo json_encode([
            'success' => true,
            'data' => $user,
            'message' => 'Profile retrieved successfully'
        ]);

    } catch (PDOException $e) {
        error_log("Get Profile Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleUpdateProfile($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON input',
            'code' => 'INVALID_INPUT'
        ]);
        return;
    }

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'employee_email'];
    $missing_fields = [];

    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing_fields),
            'code' => 'MISSING_FIELDS'
        ]);
        return;
    }

    // Validate email format
    if (!filter_var($input['employee_email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email format',
            'code' => 'INVALID_EMAIL'
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Update user email if provided
        $updateUserStmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $updateUserStmt->execute([$input['employee_email'], $user_id]);

        // Update employee information
        $updateEmpStmt = $pdo->prepare("
            UPDATE employees
            SET first_name = ?, middle_name = ?, last_name = ?, email = ?, phone = ?,
                date_of_birth = ?, address = ?, city = ?, state = ?, zip_code = ?,
                emergency_contact_name = ?, emergency_contact_phone = ?, emergency_contact_relationship = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");

        $updateEmpStmt->execute([
            trim($input['first_name']),
            trim($input['middle_name'] ?? ''),
            trim($input['last_name']),
            trim($input['employee_email']),
            trim($input['phone'] ?? ''),
            $input['date_of_birth'] ?? null,
            trim($input['address'] ?? ''),
            trim($input['city'] ?? ''),
            trim($input['state'] ?? ''),
            trim($input['zip_code'] ?? ''),
            trim($input['emergency_contact_name'] ?? ''),
            trim($input['emergency_contact_phone'] ?? ''),
            trim($input['emergency_contact_relationship'] ?? ''),
            $user_id
        ]);

        $pdo->commit();

        // Update session variables to reflect the changes in the header
        $_SESSION['first_name'] = trim($input['first_name']);
        $_SESSION['last_name'] = trim($input['last_name']);
        $_SESSION['employee_email'] = trim($input['employee_email']);

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user_id' => $user_id,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Update Profile Error: " . $e->getMessage());

        // Check for duplicate email error
        if ($e->getCode() == 23000) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'Email address already exists',
                'code' => 'EMAIL_EXISTS'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error occurred',
                'code' => 'DATABASE_ERROR'
            ]);
        }
    }
}

function handleChangePassword($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON input',
            'code' => 'INVALID_INPUT'
        ]);
        return;
    }

    // Validate required fields
    $required_fields = ['current_password', 'new_password', 'confirm_password'];
    $missing_fields = [];

    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing_fields),
            'code' => 'MISSING_FIELDS'
        ]);
        return;
    }

    // Check if new passwords match
    if ($input['new_password'] !== $input['confirm_password']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'New passwords do not match',
            'code' => 'PASSWORD_MISMATCH'
        ]);
        return;
    }

    // Validate password strength
    if (strlen($input['new_password']) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Password must be at least 6 characters long',
            'code' => 'PASSWORD_TOO_SHORT'
        ]);
        return;
    }

    try {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_hash = $stmt->fetchColumn();

        if (!password_verify($input['current_password'], $current_hash)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Current password is incorrect',
                'code' => 'INVALID_CURRENT_PASSWORD'
            ]);
            return;
        }

        // Update password
        $new_hash = password_hash($input['new_password'], PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->execute([$new_hash, $user_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully',
            'data' => [
                'user_id' => $user_id,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Change Password Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}
?>