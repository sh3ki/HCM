<?php
session_start();

// Check if user is authenticated and has tokens
if (isset($_SESSION['access_token'])) {
    // Prepare data for API logout call
    $data = [
        'session_id' => session_id()
    ];

    // Make API call to logout endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/HCM/api/auth/logout");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $_SESSION['access_token']
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log the API response (optional, for debugging)
    if ($response !== false) {
        // Extract JSON from response (in case there are PHP warnings before JSON)
        $jsonStart = strpos($response, '{');
        if ($jsonStart !== false) {
            $jsonResponse = substr($response, $jsonStart);
            $responseData = json_decode($jsonResponse, true);
        } else {
            $responseData = json_decode($response, true);
        }
        // You can log this if needed: error_log("Logout API Response: " . print_r($responseData, true));
    }
}

// Always destroy session data locally regardless of API response
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>