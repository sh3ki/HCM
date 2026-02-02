<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Before session set<br>";
$_SESSION['test'] = 'working';
$_SESSION['authenticated'] = true;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['access_token'] = 'test_token';

echo "Session set. Authenticated: " . ($_SESSION['authenticated'] ? 'true' : 'false') . "<br>";
echo "Headers sent: " . (headers_sent() ? 'YES' : 'NO') . "<br>";
echo "Attempting redirect in 2 seconds...<br>";

// Try to redirect
if (!headers_sent()) {
    header('Location: index.php');
    exit();
} else {
    echo "<strong>ERROR: Headers already sent! Cannot redirect.</strong><br>";
}
?>
