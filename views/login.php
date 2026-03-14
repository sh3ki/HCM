<?php
// Start output buffering to prevent any accidental output before headers
ob_start();
session_start();

if (!function_exists('findProjectRoot')) {
    function findProjectRoot() {
        $documentRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $scriptRoot = dirname(__DIR__);
        $candidates = [
            $scriptRoot,
            dirname($scriptRoot),
            $documentRoot,
            $documentRoot . '/HCM'
        ];

        $checked = [];

        foreach ($candidates as $root) {
            $root = rtrim(str_replace('\\', '/', (string) $root), '/');
            if ($root === '' || isset($checked[$root])) {
                continue;
            }

            $checked[$root] = true;

            $hasIncludes = @is_file($root . '/includes/auth_helper.php');
            $hasConfig = @is_file($root . '/config/app.php');
            $hasViews = @is_dir($root . '/views');

            if ($hasIncludes && $hasConfig && $hasViews) {
                return $root;
            }
        }

        return null;
    }
}

$projectRoot = findProjectRoot();

if ($projectRoot === null) {
    http_response_code(500);
    die('Deployment error: could not locate project root. Ensure includes/, config/, and views/ are in the same folder.');
}

require_once $projectRoot . '/includes/auth_helper.php';
require_once $projectRoot . '/config/app.php';
require_once $projectRoot . '/includes/Database.php';
require_once $projectRoot . '/includes/JWT.php';
require_once $projectRoot . '/includes/otp_mailer.php';

if (!function_exists('normalizeRoleIdFromUser')) {
    function normalizeRoleIdFromUser(array $user): int {
        $roleId = (int) ($user['role_id'] ?? 0);
        if ($roleId > 0) {
            return $roleId;
        }

        $role = strtolower((string) ($user['role'] ?? ''));
        return ($role === 'super admin' || $role === 'admin') ? 1 : 2;
    }
}

if (!function_exists('finalizeAuthenticatedSession')) {
    function finalizeAuthenticatedSession(array $payload): void {
        $user = $payload['user'] ?? [];
        $sessionRoleId = normalizeRoleIdFromUser($user);

        $_SESSION['user_id'] = $user['id'] ?? null;
        $_SESSION['role_id'] = $sessionRoleId > 0 ? $sessionRoleId : 1;
        $_SESSION['employee_id'] = $user['employee_id'] ?? null;
        $_SESSION['is_new'] = $user['is_new'] ?? 0;
        $_SESSION['auto_password_changed'] = $user['auto_password_changed'] ?? 0;
        $_SESSION['requires_password_change'] = $user['requires_password_change'] ?? 0;
        $_SESSION['username'] = $user['username'] ?? null;
        $_SESSION['email'] = $user['email'] ?? null;
        $_SESSION['employee_email'] = $user['employee_email'] ?? null;
        $_SESSION['role'] = $user['role'] ?? null;
        $_SESSION['first_name'] = $user['first_name'] ?? null;
        $_SESSION['last_name'] = $user['last_name'] ?? null;
        $_SESSION['access_token'] = $payload['access_token'] ?? null;
        $_SESSION['refresh_token'] = $payload['refresh_token'] ?? null;
        $_SESSION['authenticated'] = true;
    }
}

if (!function_exists('sendAdminOtp')) {
    function sendAdminOtp(array $user, PDO $pdo): void {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            throw new Exception('Unable to send OTP: missing admin user ID.');
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '') {
            throw new Exception('Admin OTP failed: no email address is linked to this admin account.');
        }

        $fullName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
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

        sendOtpEmail($email, $fullName, $otp);
    }
}

if (!function_exists('redirectByRoleId')) {
    function redirectByRoleId($roleId) {
        if ((int) $roleId === 1) {
            header('Location: index.php');
            exit();
        }

        header('Location: employee_payslip.php');
        exit();
    }
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$showAdminOtpForm = isset($_SESSION['pending_admin_auth']);

if ($_POST && isset($_POST['back_to_login'])) {
    unset($_SESSION['pending_admin_auth']);
    $showAdminOtpForm = false;
}

if ($_POST && isset($_POST['verify_admin_otp'])) {
    $pending = $_SESSION['pending_admin_auth'] ?? null;
    if (!$pending || !isset($pending['user']['id'])) {
        $error = 'Your admin OTP session expired. Please log in again.';
        $showAdminOtpForm = false;
    } else {
        $otp = preg_replace('/\D+/', '', (string) ($_POST['admin_otp'] ?? ''));
        if ($otp === '') {
            $error = 'Please enter the OTP sent to your admin email.';
            $showAdminOtpForm = true;
        } else {
            $stmt = $pdo->prepare('SELECT otp_code, otp_expires_at, otp_attempts FROM user_otps WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => (int) $pending['user']['id']]);
            $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRow) {
                $error = 'No OTP record found. Please request a new OTP.';
                $showAdminOtpForm = true;
            } elseif (($otpRow['otp_attempts'] ?? 0) >= 5) {
                $error = 'Too many OTP attempts. Please request a new OTP.';
                $showAdminOtpForm = true;
            } elseif (!empty($otpRow['otp_expires_at']) && time() > strtotime($otpRow['otp_expires_at'])) {
                $error = 'Your OTP has expired. Please request a new OTP.';
                $showAdminOtpForm = true;
            } elseif (!password_verify($otp, (string) $otpRow['otp_code'])) {
                $stmt = $pdo->prepare('UPDATE user_otps SET otp_attempts = otp_attempts + 1, updated_at = NOW() WHERE user_id = :user_id');
                $stmt->execute(['user_id' => (int) $pending['user']['id']]);
                $error = 'Invalid OTP. Please try again.';
                $showAdminOtpForm = true;
            } else {
                $stmt = $pdo->prepare('UPDATE user_otps SET otp_verified_at = NOW(), updated_at = NOW() WHERE user_id = :user_id');
                $stmt->execute(['user_id' => (int) $pending['user']['id']]);

                finalizeAuthenticatedSession($pending);
                unset($_SESSION['pending_admin_auth']);
                $showAdminOtpForm = false;

                ob_end_clean();
                redirectByRoleId($_SESSION['role_id'] ?? 1);
            }
        }
    }
}

if ($_POST && isset($_POST['resend_admin_otp'])) {
    $pending = $_SESSION['pending_admin_auth'] ?? null;
    if (!$pending || !isset($pending['user'])) {
        $error = 'Your admin OTP session expired. Please log in again.';
        $showAdminOtpForm = false;
    } else {
        try {
            sendAdminOtp($pending['user'], $pdo);
            $otpNotice = 'A new OTP was sent to your admin email.';
            $showAdminOtpForm = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            $showAdminOtpForm = true;
        }
    }
}

// If already authenticated, redirect to appropriate page
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isAuthenticated()) {
    $roleId = intval($_SESSION['role_id'] ?? 1); // Default to admin if not set
    redirectByRoleId($roleId);
}

// Handle login form submission
if ($_POST && !isset($_POST['verify_admin_otp']) && !isset($_POST['resend_admin_otp']) && !isset($_POST['back_to_login'])) {
    // If relogging while an old session exists, clear it and proceed with new credentials
    if (isAuthenticated()) {
        session_unset();
    }

    unset($_SESSION['pending_admin_auth']);
    $showAdminOtpForm = false;

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // Remember me is currently disabled by business request.
    // $remember_me = isset($_POST['remember-me']);
    $remember_me = false;

    // Prepare data for API call
    $data = [
        'username' => $username,
        'password' => $password,
        'remember_me' => $remember_me
    ];

    // Make API call to login endpoint (with fallback URL formats)
    $authEndpoints = function_exists('api_url_candidates')
        ? api_url_candidates('auth.php?action=login', ['auth/login', 'auth.php/login'])
        : [
            api_url('auth.php?action=login'),
            api_url('auth/login'),
            api_url('auth.php/login')
        ];

    $response = false;
    $httpCode = 0;
    $curlError = '';

    foreach ($authEndpoints as $endpointUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpointUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $httpCode !== 404) {
            break;
        }
    }

    // Debug logging
    error_log("Login attempt - HTTP Code: $httpCode");
    error_log("Login response: " . substr($response, 0, 500));

    if ($response === false) {
        error_log("CURL Error: $curlError");
        $error = 'Unable to connect to authentication service';
    } else {
        // Extract JSON from response (in case there are PHP warnings before JSON)
        $jsonStart = strpos($response, '{');
        if ($jsonStart !== false) {
            $jsonResponse = substr($response, $jsonStart);
            $responseData = json_decode($jsonResponse, true);
        } else {
            $responseData = json_decode($response, true);
        }

        if ($httpCode === 404 || !$responseData) {
            try {
                $db = Database::getInstance();
                $authManager = new AuthManager($db);
                $localResult = $authManager->login($username, $password, $remember_me);

                $responseData = [
                    'success' => true,
                    'data' => $localResult
                ];
                $httpCode = 200;
            } catch (Exception $localAuthError) {
                if ($httpCode === 404) {
                    $responseData = [
                        'success' => false,
                        'message' => $localAuthError->getMessage()
                    ];
                    $httpCode = 401;
                }
            }
        }

        if ($httpCode === 200 && $responseData && isset($responseData['success']) && $responseData['success']) {
            $payload = [
                'user' => $responseData['data']['user'] ?? [],
                'access_token' => $responseData['data']['access_token'] ?? null,
                'refresh_token' => $responseData['data']['refresh_token'] ?? null
            ];

            $sessionRoleId = normalizeRoleIdFromUser($payload['user']);

            if ($sessionRoleId === 1) {
                try {
                    sendAdminOtp($payload['user'], $pdo);
                    $_SESSION['pending_admin_auth'] = $payload;
                    $showAdminOtpForm = true;
                    $otpNotice = 'OTP sent to your admin email. Enter it below to continue.';
                } catch (Exception $otpError) {
                    $error = $otpError->getMessage();
                }
            } else {
                finalizeAuthenticatedSession($payload);

                // Clear any output buffer before redirect
                ob_end_clean();

                // Redirect based on role_id (1 = Admin, all others = Employee)
                redirectByRoleId($_SESSION['role_id'] ?? 1);
            }
        } else {
            // Handle various error scenarios
            if ($responseData && isset($responseData['message'])) {
                $error = $responseData['message'];
            } elseif ($httpCode !== 200) {
                $error = "Server error (HTTP $httpCode)";
            } elseif (!$responseData) {
                $error = 'Invalid response from authentication service';
            } else {
                $error = 'Login failed';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HCM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1b68ff',
                        secondary: '#6c757d',
                        success: '#3ad29f',
                        danger: '#dc3545',
                        warning: '#eea303',
                        info: '#17a2b8',
                        light: '#f8f9fa',
                        dark: '#343a40'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-primary rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    HCM System Login
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Human Capital Management System
                </p>
            </div>

            <!-- Login Form -->
            <?php if (!empty($showAdminOtpForm)): ?>
            <form class="mt-8 space-y-6" method="POST" action="">
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($otpNotice)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($otpNotice); ?>
                </div>
                <?php endif; ?>

                <div>
                    <label for="admin_otp" class="block text-sm font-medium text-gray-700 mb-1">
                        Admin OTP Verification
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-shield-alt text-gray-400"></i>
                        </div>
                        <input
                            id="admin_otp"
                            name="admin_otp"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            required
                            class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                            placeholder="Enter 6-digit OTP"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                        type="submit"
                        name="verify_admin_otp"
                        value="1"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-check-circle text-blue-300 group-hover:text-blue-200"></i>
                        </span>
                        Verify OTP
                    </button>

                    <button
                        type="submit"
                        name="resend_admin_otp"
                        value="1"
                        formnovalidate
                        class="group relative w-full flex justify-center py-3 px-4 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-paper-plane text-gray-400"></i>
                        </span>
                        Resend OTP
                    </button>
                </div>

                <button
                    type="submit"
                    name="back_to_login"
                    value="1"
                    formnovalidate
                    class="w-full flex justify-center py-3 px-4 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Login
                </button>
            </form>
            <?php else: ?>
            <form class="mt-8 space-y-6" method="POST" action="">
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input
                                id="username"
                                name="username"
                                type="text"
                                required
                                class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                                placeholder="Enter your username"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                                placeholder="Enter your password"
                            >
                        </div>
                    </div>
                </div>

                <!--
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember-me"
                            name="remember-me"
                            type="checkbox"
                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        >
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot_password.php" class="font-medium text-primary hover:text-blue-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>
                -->

                <div>
                    <button
                        type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-blue-300 group-hover:text-blue-200"></i>
                        </span>
                        Sign in
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Please enter your credentials to access the system.
                    </p>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Background Pattern -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-100"></div>
        <svg class="absolute inset-0 h-full w-full stroke-blue-200/50" fill="none" viewBox="0 0 200 200">
            <defs>
                <pattern id="pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M.5 40V.5H40" fill="none" stroke-width="1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#pattern)" />
        </svg>
    </div>
</body>
</html>