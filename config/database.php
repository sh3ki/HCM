<?php
// Database Configuration for HCM System
// Environment: Development

define('DB_HOST', 'localhost');
define('DB_NAME', 'hcm_system');
define('DB_USER', 'root');
define('DB_PASS', '');
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