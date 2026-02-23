<?php
// Database Configuration for HCM System
// Environment: Dynamic (supports localhost and live hosting)

if (!function_exists('db_env')) {
    function db_env($key, $default = null) {
        $value = getenv($key);

        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }

        if ($value === false && isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        }

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}

$rawHost = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? db_env('APP_HOST', 'localhost'))));

if (strpos($rawHost, '://') !== false) {
    $parsed = parse_url($rawHost);
    $rawHost = strtolower((string) ($parsed['host'] ?? $rawHost));
}

$currentHost = preg_replace('/:\\d+$/', '', $rawHost);

$isLocalhost = in_array($currentHost, ['localhost', '127.0.0.1', '::1'], true)
    || str_ends_with($currentHost, '.local')
    || str_ends_with($currentHost, '.test');

$defaultDbHost = 'localhost';
$defaultDbName = $isLocalhost ? 'hcm_system' : 'hr4_hcm_system';
$defaultDbUser = $isLocalhost ? 'root' : 'hr4_hcm';
$defaultDbPass = $isLocalhost ? '' : 'hcm123';

define('DB_HOST', db_env('DB_HOST', $defaultDbHost));
define('DB_NAME', db_env('DB_NAME', $defaultDbName));
define('DB_USER', db_env('DB_USER', $defaultDbUser));
define('DB_PASS', db_env('DB_PASS', $defaultDbPass));
define('DB_CHARSET', 'utf8mb4');

// Database connection options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);

// Connection timeout
define('DB_TIMEOUT', 30);

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);

    // For compatibility with existing code that uses $conn
    $conn = $pdo;
} catch (PDOException $e) {
    // Log error (in production, don't expose database details)
    error_log("Database connection failed: " . $e->getMessage());

    // In development, show error details. In production, show generic message.
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        die("Database connection failed. Please try again later.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

// For production, consider using environment variables:
// define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
// define('DB_NAME', $_ENV['DB_NAME'] ?? 'hcm_system');
// define('DB_USER', $_ENV['DB_USER'] ?? 'root');
// define('DB_PASS', $_ENV['DB_PASS'] ?? '');
?>