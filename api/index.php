<?php
require_once __DIR__ . '/../includes/ApiResponse.php';

// Simple router for API endpoints
$method = RequestHelper::getMethod();
$path = RequestHelper::getPath();
$pathParts = explode('/', trim($path, '/'));

// Remove 'HCM' and 'api' from path if present (for XAMPP setup)
if (!empty($pathParts) && $pathParts[0] === 'HCM') {
    array_shift($pathParts);
}
if (!empty($pathParts) && $pathParts[0] === 'api') {
    array_shift($pathParts);
}

if (empty($pathParts)) {
    ApiResponse::success([
        'name' => 'HCM System API',
        'version' => '1.0.0',
        'description' => 'Human Capital Management System REST API',
        'endpoints' => [
            'auth' => [
                'POST /api/auth/login' => 'User login',
                'POST /api/auth/logout' => 'User logout',
                'POST /api/auth/refresh' => 'Refresh access token',
                'POST /api/auth/change-password' => 'Change user password',
                'GET /api/auth/me' => 'Get user profile',
                'GET /api/auth/validate' => 'Validate access token'
            ],
            'employees' => [
                'GET /api/employees' => 'List employees',
                'GET /api/employees/:id' => 'Get employee details',
                'POST /api/employees' => 'Create employee',
                'PUT /api/employees/:id' => 'Update employee',
                'DELETE /api/employees/:id' => 'Delete employee'
            ]
        ]
    ], 'HCM System API');
}

// Route to appropriate handler
$endpoint = $pathParts[0] ?? '';

switch ($endpoint) {
    case 'auth':
        require_once __DIR__ . '/auth.php';
        break;

    case 'employees':
        require_once __DIR__ . '/employees.php';
        break;

    case 'departments':
        require_once __DIR__ . '/departments.php';
        break;

    case 'payroll':
        require_once __DIR__ . '/payroll.php';
        break;

    case 'attendance':
        require_once __DIR__ . '/attendance.php';
        break;

    case 'leaves':
        require_once __DIR__ . '/leaves.php';
        break;

    case 'reports':
        require_once __DIR__ . '/reports.php';
        break;

    default:
        ApiResponse::notFound('API endpoint not found');
}
?>