<?php
require_once __DIR__ . '/../config/auth.php';

class ApiResponse {

    public static function success($data = null, $message = 'Success', $code = 200) {
        // Clean any output buffer content
        if (ob_get_level()) {
            ob_clean();
        }

        self::setHeaders();
        http_response_code($code);

        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c'),
            'data' => $data
        ];

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    public static function error($message = 'Error', $code = 400, $errors = null) {
        // Clean any output buffer content
        if (ob_get_level()) {
            ob_clean();
        }

        self::setHeaders();
        http_response_code($code);

        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
            'errors' => $errors
        ];

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    public static function unauthorized($message = null) {
        $message = $message ?: AUTH_MESSAGES['ACCESS_DENIED'];
        self::error($message, 401);
    }

    public static function forbidden($message = null) {
        $message = $message ?: AUTH_MESSAGES['ACCESS_DENIED'];
        self::error($message, 403);
    }

    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    public static function validationError($errors, $message = null) {
        $message = $message ?: AUTH_MESSAGES['VALIDATION_ERROR'];
        self::error($message, 422, $errors);
    }

    public static function serverError($message = null) {
        $message = $message ?: AUTH_MESSAGES['SERVER_ERROR'];
        self::error($message, 500);
    }

    private static function setHeaders() {
        // Set content type
        header('Content-Type: application/json');

        // Set security headers
        foreach (SECURITY_HEADERS as $header => $value) {
            header("$header: $value");
        }

        // Handle CORS
        self::handleCors();
    }

    private static function handleCors() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, CORS_ALLOWED_ORIGINS)) {
            header("Access-Control-Allow-Origin: $origin");
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', CORS_ALLOWED_METHODS));
        header('Access-Control-Allow-Headers: ' . implode(', ', CORS_ALLOWED_HEADERS));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

class ApiMiddleware {
    private $db;
    private $auth;

    public function __construct($database) {
        $this->db = $database;
        $this->auth = new AuthManager($database);
    }

    public function authenticate($requireAuth = true) {
        if (!$requireAuth) {
            return null;
        }

        $token = $this->extractToken();

        if (!$token) {
            ApiResponse::unauthorized(AUTH_MESSAGES['TOKEN_MISSING']);
        }

        $validation = $this->auth->validateToken($token);

        if (!$validation['valid']) {
            ApiResponse::unauthorized(AUTH_MESSAGES['TOKEN_INVALID']);
        }

        return $validation['user'];
    }

    public function authorize($resource, $action, $user = null) {
        if (!$user) {
            $user = $this->authenticate();
        }

        if (!$this->auth->hasPermission($user['id'], $resource, $action)) {
            ApiResponse::forbidden();
        }

        return $user;
    }

    public function validateInput($data, $rules) {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "$field is required";
                continue;
            }

            if (!empty($value)) {
                if (isset($rule['type'])) {
                    switch ($rule['type']) {
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[$field] = "$field must be a valid email";
                            }
                            break;
                        case 'string':
                            if (!is_string($value)) {
                                $errors[$field] = "$field must be a string";
                            }
                            break;
                        case 'integer':
                            if (!is_numeric($value)) {
                                $errors[$field] = "$field must be a number";
                            }
                            break;
                    }
                }

                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[$field] = "$field must be at least {$rule['min_length']} characters";
                }

                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field] = "$field must not exceed {$rule['max_length']} characters";
                }

                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $errors[$field] = $rule['pattern_message'] ?? "$field format is invalid";
                }
            }
        }

        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }

        return true;
    }

    public function rateLimit($identifier = null) {
        $identifier = $identifier ?: $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit_$identifier";

        // Simple file-based rate limiting (in production, use Redis or Memcached)
        $rateFile = sys_get_temp_dir() . "/$key";
        $currentTime = time();

        if (file_exists($rateFile)) {
            $data = json_decode(file_get_contents($rateFile), true);
            $requests = array_filter($data['requests'], function($time) use ($currentTime) {
                return ($currentTime - $time) < API_RATE_WINDOW;
            });

            if (count($requests) >= API_RATE_LIMIT) {
                ApiResponse::error('Rate limit exceeded. Please try again later.', 429);
            }

            $requests[] = $currentTime;
        } else {
            $requests = [$currentTime];
        }

        // Suppress rate limiting file write errors for now
        @file_put_contents($rateFile, json_encode(['requests' => $requests]));
    }

    public function extractToken() {
        $headers = getallheaders();

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }

        // Check for token in query parameter (not recommended for production)
        return $_GET['token'] ?? null;
    }
}

class RequestHelper {

    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    public static function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function getPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($path, '/');
    }

    public static function getQueryParams() {
        return $_GET;
    }

    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    public static function getIpAddress() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }

        return 'unknown';
    }

    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }

        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}
?>