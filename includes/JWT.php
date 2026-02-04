<?php
require_once __DIR__ . '/../config/auth.php';

class JWT {

    public static function encode($payload, $secret = null) {
        $secret = $secret ?: JWT_SECRET;

        $header = [
            'typ' => 'JWT',
            'alg' => JWT_ALGORITHM
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRATION;

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    public static function decode($token, $secret = null) {
        $secret = $secret ?: JWT_SECRET;

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        $header = json_decode(self::base64UrlDecode($headerEncoded), true);
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (!$header || !$payload) {
            throw new Exception('Invalid token data');
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $actualSignature = self::base64UrlDecode($signatureEncoded);

        if (!hash_equals($expectedSignature, $actualSignature)) {
            throw new Exception('Invalid token signature');
        }

        // Check expiration
        if (isset($payload['exp']) && time() >= $payload['exp']) {
            throw new Exception('Token has expired');
        }

        return $payload;
    }

    public static function generateRefreshToken($userId) {
        $payload = [
            'user_id' => $userId,
            'type' => 'refresh',
            'iat' => time(),
            'exp' => time() + JWT_REFRESH_EXPIRATION
        ];

        return self::encode($payload);
    }

    public static function validateRefreshToken($token) {
        try {
            $payload = self::decode($token);

            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                throw new Exception('Invalid refresh token');
            }

            return $payload;
        } catch (Exception $e) {
            throw new Exception('Invalid refresh token: ' . $e->getMessage());
        }
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

class AuthManager {
    private $db;
    private $loginAttempts = [];

    public function __construct($database) {
        $this->db = $database;
    }

    public function login($username, $password, $rememberMe = false) {
        // Check if account is locked
        if ($this->isAccountLocked($username)) {
            throw new Exception(AUTH_MESSAGES['ACCOUNT_LOCKED']);
        }

        // Get user from database
        $user = $this->getUserByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($username);
            throw new Exception(AUTH_MESSAGES['LOGIN_FAILED']);
        }

        if (!$user['is_active']) {
            throw new Exception('Account is deactivated');
        }

        // Clear failed attempts on successful login
        $this->clearFailedAttempts($username);

        // Update last login
        $this->updateLastLogin($user['id']);

        // Generate tokens
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $rememberMe ? JWT::generateRefreshToken($user['id']) : null;

        // Create session
        $sessionId = $this->createSession($user['id']);

        return [
            'user' => $this->sanitizeUserData($user),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'session_id' => $sessionId,
            'expires_in' => JWT_EXPIRATION
        ];
    }

    public function logout($sessionId = null, $token = null) {
        if ($sessionId) {
            $this->destroySession($sessionId);
        }

        if ($token) {
            // In a production environment, you would add the token to a blacklist
            // For now, we'll just return success
        }

        return ['message' => AUTH_MESSAGES['LOGOUT_SUCCESS']];
    }

    public function refreshToken($refreshToken) {
        try {
            $payload = JWT::validateRefreshToken($refreshToken);
            $user = $this->getUserById($payload['user_id']);

            if (!$user || !$user['is_active']) {
                throw new Exception('User not found or inactive');
            }

            $newAccessToken = $this->generateAccessToken($user);

            return [
                'access_token' => $newAccessToken,
                'expires_in' => JWT_EXPIRATION
            ];

        } catch (Exception $e) {
            throw new Exception('Invalid refresh token');
        }
    }

    public function validateToken($token) {
        try {
            $payload = JWT::decode($token);
            $user = $this->getUserById($payload['user_id']);

            if (!$user || !$user['is_active']) {
                throw new Exception('User not found or inactive');
            }

            return [
                'valid' => true,
                'user' => $this->sanitizeUserData($user),
                'payload' => $payload
            ];

        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->getUserById($userId);

        if (!$user) {
            throw new Exception(AUTH_MESSAGES['USER_NOT_FOUND']);
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new Exception('Current password is incorrect');
        }

        if (!$this->validatePassword($newPassword)) {
            throw new Exception('Password does not meet requirements');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->db->update('users',
            ['password_hash' => $hashedPassword],
            'id = :id',
            ['id' => $userId]
        );

        // Log password change
        $this->logUserActivity($userId, 'password_changed');

        return ['message' => AUTH_MESSAGES['PASSWORD_CHANGED']];
    }

    public function hasPermission($userId, $resource, $action) {
        $user = $this->getUserById($userId);

        if (!$user) {
            return false;
        }

        $userRole = $user['role'];
        $permissions = USER_ROLES[$userRole] ?? [];

        return isset($permissions[$resource]) && in_array($action, $permissions[$resource]);
    }

    private function getUserByUsername($username) {
        return $this->db->selectOne(
            "SELECT u.*, r.role_name as role, u.role_id, e.id as employee_id, e.first_name, e.last_name, e.email as employee_email
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN employees e ON u.id = e.user_id
             WHERE u.username = :username OR u.email = :email",
            ['username' => $username, 'email' => $username]
        );
    }

    private function getUserById($id) {
        return $this->db->selectOne(
            "SELECT u.*, r.role_name as role, u.role_id, e.id as employee_id, e.first_name, e.last_name, e.email as employee_email
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN employees e ON u.id = e.user_id
             WHERE u.id = :id",
            ['id' => $id]
        );
    }

    private function generateAccessToken($user) {
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'role_id' => $user['role_id'],
            'is_new' => $user['is_new'] ?? 0,
            'employee_id' => $user['employee_id']
        ];

        return JWT::encode($payload);
    }

    private function sanitizeUserData($user) {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'employee_email' => $user['employee_email'],
            'role' => $user['role'],
            'role_id' => $user['role_id'],
            'is_new' => $user['is_new'] ?? 0,
            'requires_password_change' => $user['requires_password_change'] ?? 0,
            'employee_id' => $user['employee_id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'last_login' => $user['last_login']
        ];
    }

    private function updateLastLogin($userId) {
        $this->db->update('users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $userId]
        );
    }

    private function createSession($userId) {
        $sessionId = bin2hex(random_bytes(32));

        // Try to create session record, but don't fail if table doesn't exist
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

            $this->db->insert('user_sessions', [
                'id' => $sessionId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent
            ]);
        } catch (Exception $e) {
            // Ignore session creation errors for now
            error_log("Session creation failed: " . $e->getMessage());
        }

        return $sessionId;
    }

    private function destroySession($sessionId) {
        try {
            $this->db->delete('user_sessions', 'id = :id', ['id' => $sessionId]);
        } catch (Exception $e) {
            // Ignore session deletion errors for now
            error_log("Session deletion failed: " . $e->getMessage());
        }
    }

    private function isAccountLocked($username) {
        $attempts = $this->loginAttempts[$username] ?? [];
        $recentAttempts = array_filter($attempts, function($time) {
            return (time() - $time) < LOGIN_LOCKOUT_DURATION;
        });

        return count($recentAttempts) >= MAX_LOGIN_ATTEMPTS;
    }

    private function recordFailedAttempt($username) {
        if (!isset($this->loginAttempts[$username])) {
            $this->loginAttempts[$username] = [];
        }
        $this->loginAttempts[$username][] = time();
    }

    private function clearFailedAttempts($username) {
        unset($this->loginAttempts[$username]);
    }

    private function validatePassword($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }

        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    private function logUserActivity($userId, $action, $details = null) {
        try {
            $this->db->insert('system_logs', [
                'user_id' => $userId,
                'action' => $action,
                'new_values' => $details ? json_encode($details) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Ignore logging errors for now
            error_log("Activity logging failed: " . $e->getMessage());
        }
    }
}
?>