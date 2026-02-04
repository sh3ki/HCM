<?php
// Disable error display, only log errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/otp_mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'status':
        otpStatus();
        break;
    case 'send':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        sendOtp();
        break;
    case 'verify':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }
        verifyOtp();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function otpStatus() {
    global $pdo;
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        return;
    }

    $stmt = $pdo->prepare("SELECT is_new FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $isNew = (int) ($stmt->fetchColumn() ?? 0);

    echo json_encode(['success' => true, 'is_new' => $isNew]);
}

function sendOtp() {
    global $pdo;
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        return;
    }

    $stmt = $pdo->prepare("SELECT u.email, e.first_name, e.last_name, u.is_new
        FROM users u
        LEFT JOIN employees e ON e.user_id = u.id
        WHERE u.id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        return;
    }

    if ((int) $user['is_new'] !== 1) {
        echo json_encode(['success' => true, 'message' => 'OTP not required']);
        return;
    }

    $email = $user['email'];
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No email found for user']);
        return;
    }

    $stmt = $pdo->prepare("SELECT otp_last_sent_at FROM user_otps WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $userId]);
    $lastSent = $stmt->fetchColumn();

    if ($lastSent && (time() - strtotime($lastSent)) < 60) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Please wait before requesting another OTP']);
        return;
    }

    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + 600);
    $sentAt = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO user_otps (user_id, otp_code, otp_expires_at, otp_last_sent_at, otp_attempts, otp_verified_at, updated_at)
        VALUES (:user_id, :otp_code, :expires_at, :sent_at, 0, NULL, :updated_at)
        ON DUPLICATE KEY UPDATE otp_code = VALUES(otp_code), otp_expires_at = VALUES(otp_expires_at), otp_last_sent_at = VALUES(otp_last_sent_at), otp_attempts = 0, otp_verified_at = NULL, updated_at = VALUES(updated_at)");
    $stmt->execute([
        'user_id' => $userId,
        'otp_code' => $otpHash,
        'expires_at' => $expiresAt,
        'sent_at' => $sentAt,
        'updated_at' => $sentAt
    ]);

    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

    try {
        $result = sendOtpEmail($email, $fullName, $otp);
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
    } catch (Exception $e) {
        error_log('OTP email failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to send OTP email']);
    }
}

function verifyOtp() {
    global $pdo;
    $userId = $_SESSION['user_id'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = trim($input['otp'] ?? '');

    if (!$userId || !$otp) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'OTP required']);
        return;
    }

    $stmt = $pdo->prepare("SELECT otp_code, otp_expires_at, otp_attempts FROM user_otps WHERE user_id = :user_id LIMIT 1");
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'OTP not found']);
        return;
    }

    if ($row['otp_expires_at'] && time() > strtotime($row['otp_expires_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'OTP expired']);
        return;
    }

    if (($row['otp_attempts'] ?? 0) >= 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Too many attempts']);
        return;
    }

    if (!password_verify($otp, $row['otp_code'])) {
        $stmt = $pdo->prepare("UPDATE user_otps SET otp_attempts = otp_attempts + 1, updated_at = NOW() WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid OTP']);
        return;
    }

    $stmt = $pdo->prepare("UPDATE user_otps SET otp_verified_at = NOW(), updated_at = NOW() WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);

    $stmt = $pdo->prepare("UPDATE users SET is_new = 0 WHERE id = :user_id");
    $stmt->execute(['user_id' => $userId]);

    $_SESSION['is_new'] = 0;

    echo json_encode(['success' => true, 'message' => 'OTP verified']);
}
