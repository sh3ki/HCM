<?php
// Temporary settings API without authentication for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetSettings();
            break;
        case 'POST':
        case 'PUT':
            if (isset($_FILES['company_logo'])) {
                handleLogoUpload();
            } else {
                handleUpdateSettings();
            }
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

    $sql = "SELECT setting_key, setting_value, setting_category, data_type, is_public, updated_at FROM settings ORDER BY setting_category, setting_key";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt;

    $settings = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $value = $row['setting_value'];

        // Convert boolean and number types
        if ($row['data_type'] === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($row['data_type'] === 'number') {
            $value = is_numeric($value) ? (float)$value : $value;
        } elseif ($row['data_type'] === 'json') {
            $value = json_decode($value, true) ?? $value;
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
        'count' => count($settings),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function handleUpdateSettings() {
    global $conn;

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['settings'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request body'
        ]);
        return;
    }

    $conn->beginTransaction();

    try {
        $updated = [];

        foreach ($input['settings'] as $key => $data) {
            $value = $data['value'] ?? $data;

            // Convert value to string for storage
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            } else {
                $value = (string)$value;
            }

            // Update existing setting
            $updateStmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
            $updateStmt->execute([$value, $key]);

            $updated[$key] = [
                'value' => $value,
                'action' => 'updated'
            ];
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully',
            'updated' => $updated,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function handleLogoUpload() {
    global $conn;

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