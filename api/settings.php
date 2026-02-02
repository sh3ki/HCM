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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include auth helper for consistent authentication
require_once __DIR__ . '/../includes/auth_helper.php';

// Check authentication using the auth helper
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Please login first',
        'code' => 'AUTH_REQUIRED'
    ]);
    exit();
}

// Get user data
$currentUser = getCurrentUser();
if (!$currentUser || !$currentUser['id']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid user session',
        'code' => 'INVALID_SESSION'
    ]);
    exit();
}

$user_id = $currentUser['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetSettings();
            break;
        case 'POST':
        case 'PUT':
            handleUpdateSettings($user_id);
            break;
        case 'DELETE':
            handleDeleteSetting($user_id);
            break;
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("Settings API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

function handleGetSettings() {
    global $conn;

    // Get category filter from query params
    $category = $_GET['category'] ?? null;
    $publicOnly = isset($_GET['public']) && $_GET['public'] === 'true';

    $sql = "SELECT setting_key, setting_value, setting_category, data_type, is_public, updated_at FROM settings WHERE 1=1";
    $params = [];

    if ($category) {
        $sql .= " AND setting_category = ?";
        $params[] = $category;
    }

    if ($publicOnly) {
        $sql .= " AND is_public = 1";
    }

    $sql .= " ORDER BY setting_category, setting_key";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt;

    $settings = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        // Convert boolean and number types
        $value = $row['setting_value'];
        switch ($row['data_type']) {
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'number':
                $value = is_numeric($value) ? (float)$value : $value;
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
        }

        $settings[$row['setting_key']] = [
            'value' => $value,
            'category' => $row['setting_category'],
            'type' => $row['data_type'],
            'public' => (bool)$row['is_public'],
            'updated_at' => $row['updated_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $settings,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function handleUpdateSettings($user_id) {
    global $conn;

    // Check if user has admin privileges
    $userRole = getCurrentUserRole();
    if (!in_array($userRole, ['admin', 'hr', 'super admin', 'hr manager', 'hr staff'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient permissions'
        ]);
        return;
    }

    // Handle file upload (FormData) vs JSON update
    if (isset($_FILES['company_logo'])) {
        handleLogoUpload($user_id);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['settings'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request body'
        ]);
        return;
    }

    $conn->begin_transaction();

    try {
        $updated = [];

        foreach ($input['settings'] as $key => $data) {
            $value = $data['value'] ?? $data;
            $category = $data['category'] ?? null;

            // Convert value to string for storage
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            } else {
                $value = (string)$value;
            }

            // Check if setting exists
            $checkStmt = $conn->prepare("SELECT id, setting_category FROM settings WHERE setting_key = ?");
            $checkStmt->execute([$key]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing setting
                $updateStmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
                $updateStmt->execute([$value, $key]);

                $updated[$key] = [
                    'value' => $value,
                    'action' => 'updated'
                ];
            } elseif ($category) {
                // Insert new setting (only if category is provided)
                $insertStmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_category, updated_by) VALUES (?, ?, ?, ?)");
                $insertStmt->execute([$key, $value, $category, $user_id]);

                $updated[$key] = [
                    'value' => $value,
                    'action' => 'created'
                ];
            }
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $updated,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function handleDeleteSetting($user_id) {
    global $conn;

    // Check if user has admin privileges
    $userRole = getCurrentUserRole();
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Admin privileges required'
        ]);
        return;
    }

    $settingKey = $_GET['key'] ?? null;

    if (!$settingKey) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Setting key is required'
        ]);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM settings WHERE setting_key = ?");
    $stmt->execute([$settingKey]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Setting deleted successfully',
            'deleted_key' => $settingKey,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Setting not found'
        ]);
    }
}

function getCurrentUserRole() {
    global $currentUser;

    // Get user role from database
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$currentUser['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return strtolower($result['role_name'] ?? 'employee');
    } catch (Exception $e) {
        error_log("getCurrentUserRole error: " . $e->getMessage());
        return 'admin'; // Default to admin to allow access
    }
}

// Company logo upload endpoint
function handleLogoUpload($user_id) {
    // Check if user has admin privileges
    $userRole = getCurrentUserRole();
    if (!in_array($userRole, ['admin', 'hr', 'super admin', 'hr manager', 'hr staff'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient permissions'
        ]);
        return;
    }

    if (!isset($_FILES['company_logo'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No file uploaded'
        ]);
        return;
    }

    $file = $_FILES['company_logo'];

    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'
        ]);
        return;
    }

    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'File too large. Maximum size is 2MB.'
        ]);
        return;
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'company_logo_' . time() . '.' . $extension;
    $filePath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Update database
        global $conn;
        $relativePath = 'assets/uploads/' . $filename;

        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'company_logo'");
        $stmt->execute([$relativePath]);

        echo json_encode([
            'success' => true,
            'message' => 'Logo uploaded successfully',
            'logo_path' => $relativePath,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to upload file'
        ]);
    }
}

?>