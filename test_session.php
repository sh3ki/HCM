<?php
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'authenticated' => isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true,
    'has_token' => isset($_SESSION['access_token']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? null
]);
