<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized',
        'code' => 'AUTH_REQUIRED'
    ]);
    exit();
}

$action = $_GET['action'] ?? 'change';

try {
    switch ($action) {
        case 'change':
            handleChangePassword($pdo);
            break;
        case 'skip':
            handleSkipPasswordChange($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action',
                'code' => 'INVALID_ACTION'
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("Password Change API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'SERVER_ERROR'
    ]);
}

function handleChangePassword($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'];
    
    // Validate input
    if (empty($data['new_password']) || empty($data['confirm_password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Both password fields are required',
            'code' => 'MISSING_FIELDS'
        ]);
        return;
    }
    
    // Check if passwords match
    if ($data['new_password'] !== $data['confirm_password']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Passwords do not match',
            'code' => 'PASSWORD_MISMATCH'
        ]);
        return;
    }
    
    // Validate password strength (minimum 6 characters)
    if (strlen($data['new_password']) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Password must be at least 6 characters long',
            'code' => 'WEAK_PASSWORD'
        ]);
        return;
    }
    
    try {
        // Hash the new password
        $passwordHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
        
        // Update the password and clear the requires_password_change flag
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, 
                requires_password_change = 0,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$passwordHash, $userId]);
        
        // Update session
        unset($_SESSION['requires_password_change']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Password Update Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

function handleSkipPasswordChange($pdo) {
    $userId = $_SESSION['user_id'];
    
    try {
        // Just clear the requires_password_change flag from session
        // Keep it in database so they can change later
        unset($_SESSION['requires_password_change']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Password change skipped'
        ]);
        
    } catch (Exception $e) {
        error_log("Skip Password Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error occurred',
            'code' => 'SERVER_ERROR'
        ]);
    }
}
?>
