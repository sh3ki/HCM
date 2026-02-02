<?php
// Simple debug endpoint to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Settings API\n";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST data:\n";
    $input = file_get_contents('php://input');
    echo "Raw input: " . $input . "\n";

    if (!empty($_POST)) {
        echo "Form POST:\n";
        print_r($_POST);
    }

    if (!empty($_FILES)) {
        echo "Files:\n";
        print_r($_FILES);
    }
}

echo "\nSession data:\n";
session_start();
print_r($_SESSION);

// Test database connection
try {
    require_once __DIR__ . '/../config/database.php';
    echo "\nDatabase: Connected successfully\n";
} catch (Exception $e) {
    echo "\nDatabase error: " . $e->getMessage() . "\n";
}

// Test auth
try {
    require_once __DIR__ . '/../includes/auth_helper.php';
    $isAuth = isAuthenticated();
    echo "Authenticated: " . ($isAuth ? "YES" : "NO") . "\n";

    if ($isAuth) {
        $user = getCurrentUser();
        echo "Current user: " . print_r($user, true) . "\n";
    }
} catch (Exception $e) {
    echo "Auth error: " . $e->getMessage() . "\n";
}
?>