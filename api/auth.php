<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/ApiResponse.php';

// Initialize database and middleware
try {
    $db = Database::getInstance();
    $middleware = new ApiMiddleware($db);
    $auth = new AuthManager($db);

    // Apply rate limiting
    $middleware->rateLimit();

    // Get request method and path
    $method = RequestHelper::getMethod();
    $path = RequestHelper::getPath();
    $pathParts = explode('/', trim($path, '/'));

    // Route the request
    switch ($method) {
        case 'POST':
            if (end($pathParts) === 'login') {
                handleLogin($middleware, $auth);
            } elseif (end($pathParts) === 'logout') {
                handleLogout($middleware, $auth);
            } elseif (end($pathParts) === 'refresh') {
                handleRefreshToken($middleware, $auth);
            } elseif (end($pathParts) === 'change-password') {
                handleChangePassword($middleware, $auth);
            } else {
                ApiResponse::notFound('Authentication endpoint not found');
            }
            break;

        case 'GET':
            if (end($pathParts) === 'me') {
                handleGetProfile($middleware, $auth);
            } elseif (end($pathParts) === 'validate') {
                handleValidateToken($middleware, $auth);
            } else {
                ApiResponse::notFound('Authentication endpoint not found');
            }
            break;

        case 'OPTIONS':
            // Handled by ApiResponse::setHeaders()
            break;

        default:
            ApiResponse::error('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    ApiResponse::serverError();
}

function handleLogin($middleware, $auth) {
    $data = RequestHelper::getJsonInput();

    // Validate input
    $rules = [
        'username' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 3,
            'max_length' => 100
        ],
        'password' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 1
        ]
    ];

    $middleware->validateInput($data, $rules);

    try {
        $result = $auth->login(
            $data['username'],
            $data['password'],
            $data['remember_me'] ?? false
        );

        ApiResponse::success($result, AUTH_MESSAGES['LOGIN_SUCCESS']);

    } catch (Exception $e) {
        ApiResponse::error($e->getMessage(), 401);
    }
}

function handleLogout($middleware, $auth) {
    $data = RequestHelper::getJsonInput();
    $user = $middleware->authenticate(false); // Optional authentication

    try {
        $result = $auth->logout(
            $data['session_id'] ?? null,
            $middleware->extractToken()
        );

        ApiResponse::success($result);

    } catch (Exception $e) {
        ApiResponse::error($e->getMessage());
    }
}

function handleRefreshToken($middleware, $auth) {
    $data = RequestHelper::getJsonInput();

    // Validate input
    $rules = [
        'refresh_token' => [
            'required' => true,
            'type' => 'string'
        ]
    ];

    $middleware->validateInput($data, $rules);

    try {
        $result = $auth->refreshToken($data['refresh_token']);
        ApiResponse::success($result, 'Token refreshed successfully');

    } catch (Exception $e) {
        ApiResponse::error($e->getMessage(), 401);
    }
}

function handleChangePassword($middleware, $auth) {
    $user = $middleware->authenticate();
    $data = RequestHelper::getJsonInput();

    // Validate input
    $rules = [
        'current_password' => [
            'required' => true,
            'type' => 'string'
        ],
        'new_password' => [
            'required' => true,
            'type' => 'string',
            'min_length' => PASSWORD_MIN_LENGTH
        ],
        'confirm_password' => [
            'required' => true,
            'type' => 'string'
        ]
    ];

    $middleware->validateInput($data, $rules);

    // Check if passwords match
    if ($data['new_password'] !== $data['confirm_password']) {
        ApiResponse::validationError(['confirm_password' => 'Passwords do not match']);
    }

    try {
        $result = $auth->changePassword(
            $user['id'],
            $data['current_password'],
            $data['new_password']
        );

        ApiResponse::success($result);

    } catch (Exception $e) {
        ApiResponse::error($e->getMessage());
    }
}

function handleGetProfile($middleware, $auth) {
    $user = $middleware->authenticate();

    // Get full user profile from database
    $db = Database::getInstance();
    $profile = $db->selectOne(
        "SELECT u.id, u.username, u.email, u.role, u.last_login, u.created_at,
                e.id as employee_id, e.employee_number, e.first_name, e.middle_name,
                e.last_name, e.phone, e.hire_date, e.employment_status,
                d.name as department_name, p.title as position_title
         FROM users u
         LEFT JOIN employees e ON u.id = e.user_id
         LEFT JOIN departments d ON e.department_id = d.id
         LEFT JOIN positions p ON e.position_id = p.id
         WHERE u.id = :id",
        ['id' => $user['id']]
    );

    if (!$profile) {
        ApiResponse::notFound('User profile not found');
    }

    ApiResponse::success($profile, 'Profile retrieved successfully');
}

function handleValidateToken($middleware, $auth) {
    $user = $middleware->authenticate();

    ApiResponse::success([
        'valid' => true,
        'user' => $user
    ], 'Token is valid');
}
?>