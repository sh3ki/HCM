<?php
// Authentication Configuration for HCM System

// JWT Configuration
define('JWT_SECRET', 'your-super-secret-jwt-key-change-in-production');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 3600 * 24); // 24 hours
define('JWT_REFRESH_EXPIRATION', 3600 * 24 * 7); // 7 days

// Session Configuration
define('SESSION_TIMEOUT', 3600 * 2); // 2 hours
define('SESSION_NAME', 'HCM_SESSION');

// Password Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Login Attempt Limits
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 900); // 15 minutes

// API Rate Limiting
define('API_RATE_LIMIT', 100); // requests per hour
define('API_RATE_WINDOW', 3600); // 1 hour

// CORS Settings
define('CORS_ALLOWED_ORIGINS', ['http://localhost:3000', 'http://127.0.0.1:3000']);
define('CORS_ALLOWED_METHODS', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
define('CORS_ALLOWED_HEADERS', ['Content-Type', 'Authorization', 'X-Requested-With']);

// Security Headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'"
]);

// API Response Messages
define('AUTH_MESSAGES', [
    'LOGIN_SUCCESS' => 'Login successful',
    'LOGIN_FAILED' => 'Invalid credentials',
    'LOGOUT_SUCCESS' => 'Logout successful',
    'TOKEN_INVALID' => 'Invalid or expired token',
    'TOKEN_MISSING' => 'Authorization token required',
    'ACCESS_DENIED' => 'Access denied',
    'USER_NOT_FOUND' => 'User not found',
    'PASSWORD_CHANGED' => 'Password changed successfully',
    'ACCOUNT_LOCKED' => 'Account temporarily locked due to multiple failed attempts',
    'VALIDATION_ERROR' => 'Validation error',
    'SERVER_ERROR' => 'Internal server error'
]);

// User Roles and Permissions
define('USER_ROLES', [
    'admin' => [
        'users' => ['create', 'read', 'update', 'delete'],
        'employees' => ['create', 'read', 'update', 'delete'],
        'departments' => ['create', 'read', 'update', 'delete'],
        'payroll' => ['create', 'read', 'update', 'delete'],
        'attendance' => ['create', 'read', 'update', 'delete'],
        'leaves' => ['create', 'read', 'update', 'delete', 'approve'],
        'benefits' => ['create', 'read', 'update', 'delete'],
        'reports' => ['read'],
        'settings' => ['read', 'update']
    ],
    'hr' => [
        'employees' => ['create', 'read', 'update'],
        'departments' => ['read'],
        'attendance' => ['read', 'update'],
        'leaves' => ['read', 'approve'],
        'benefits' => ['read', 'update'],
        'reports' => ['read']
    ],
    'manager' => [
        'employees' => ['read'],
        'attendance' => ['read'],
        'leaves' => ['read', 'approve'],
        'reports' => ['read']
    ],
    'employee' => [
        'profile' => ['read', 'update'],
        'attendance' => ['read'],
        'leaves' => ['create', 'read'],
        'payroll' => ['read_own']
    ]
]);
?>