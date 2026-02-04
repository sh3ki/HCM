<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

try {
    require_once __DIR__ . '/../includes/Database.php';
    require_once __DIR__ . '/../includes/otp_mailer.php';
    session_start();
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'System error']));
}

$action = $_GET['action'] ?? '';

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    sendOtp();
} elseif ($action === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyOtp();
} elseif ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    resetPass();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function sendOtp() {
    global $pdo;
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = trim($input['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Valid email required']));
        }

        $stmt = $pdo->prepare("SELECT u.id, u.username, e.first_name, e.last_name FROM users u LEFT JOIN employees e ON e.user_id = u.id WHERE u.email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            die(json_encode(['success' => false, 'error' => 'Email not found']));
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($otp, PASSWORD_DEFAULT);
        $exp = date('Y-m-d H:i:s', time() + 600);

        $stmt = $pdo->prepare("UPDATE users SET password_reset_otp = :h, password_reset_expires = :e, password_reset_sent_at = NOW(), password_reset_attempts = 0 WHERE id = :id");
        $stmt->execute(['h' => $hash, 'e' => $exp, 'id' => $user['id']]);

        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'];
        $html = "<div style='font-family:Arial;'><h2 style='color:#1b68ff;'>Password Reset</h2><p>Hi <b>$name</b>,</p><p>Your code:</p><div style='font-size:32px;font-weight:bold;color:#1b68ff;letter-spacing:8px;margin:20px 0;'>$otp</div><p>Expires in 10 min.</p></div>";
        
        smtp_send_mail($email, $name, 'Password Reset - HCM', $html);
        
        $_SESSION['password_reset_email'] = $email;
        $_SESSION['password_reset_user_id'] = $user['id'];
        
        echo json_encode(['success' => true, 'message' => 'OTP sent']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Send failed']);
    }
}

function verifyOtp() {
    global $pdo;
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $otp = trim($input['otp'] ?? '');
        $email = $_SESSION['password_reset_email'] ?? '';

        if (!$otp || !$email) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid request']));
        }

        $stmt = $pdo->prepare("SELECT id, password_reset_otp, password_reset_expires, password_reset_attempts FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['password_reset_otp']) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'OTP not found']));
        }

        if (time() > strtotime($user['password_reset_expires'])) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'OTP expired']));
        }

        if ($user['password_reset_attempts'] >= 5) {
            http_response_code(429);
            die(json_encode(['success' => false, 'error' => 'Too many attempts']));
        }

        if (!password_verify($otp, $user['password_reset_otp'])) {
            $pdo->prepare("UPDATE users SET password_reset_attempts = password_reset_attempts + 1 WHERE id = :id")->execute(['id' => $user['id']]);
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid OTP']));
        }

        $_SESSION['password_reset_verified'] = true;
        $_SESSION['password_reset_user_id'] = $user['id'];
        
        echo json_encode(['success' => true, 'message' => 'Verified']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Verify failed']);
    }
}

function resetPass() {
    global $pdo;
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $pass = trim($input['password'] ?? '');
        $conf = trim($input['confirm_password'] ?? '');
        
        if (!($_SESSION['password_reset_verified'] ?? false)) {
            http_response_code(401);
            die(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        $uid = $_SESSION['password_reset_user_id'] ?? null;
        if (!$uid || !$pass || !$conf) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Invalid request']));
        }

        if (strlen($pass) < 6) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Password too short']));
        }

        if ($pass !== $conf) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'Passwords do not match']));
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :h, password_reset_otp = NULL, password_reset_expires = NULL, password_reset_sent_at = NULL, password_reset_attempts = 0 WHERE id = :id");
        $stmt->execute(['h' => $hash, 'id' => $uid]);

        unset($_SESSION['password_reset_email'], $_SESSION['password_reset_user_id'], $_SESSION['password_reset_verified']);

        echo json_encode(['success' => true, 'message' => 'Password reset']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Reset failed']);
    }
}
