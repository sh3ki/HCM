<?php
session_start();

if (!function_exists('findProjectRoot')) {
    function findProjectRoot() {
        $documentRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $scriptRoot = dirname(__DIR__);
        $candidates = [
            $scriptRoot,
            dirname($scriptRoot),
            $documentRoot,
            $documentRoot . '/HCM'
        ];

        $checked = [];

        foreach ($candidates as $root) {
            $root = rtrim(str_replace('\\', '/', (string) $root), '/');
            if ($root === '' || isset($checked[$root])) {
                continue;
            }

            $checked[$root] = true;

            $hasConfig = @is_file($root . '/config/app.php');
            $hasViews = @is_dir($root . '/views');

            if ($hasConfig && $hasViews) {
                return $root;
            }
        }

        return null;
    }
}

$projectRoot = findProjectRoot();
if ($projectRoot === null) {
    http_response_code(500);
    die('Deployment error: could not locate project root. Ensure config/ and views/ are in the same folder.');
}

require_once $projectRoot . '/config/app.php';

// Check if user is authenticated and has tokens
if (isset($_SESSION['access_token'])) {
    // Prepare data for API logout call
    $data = [
        'session_id' => session_id()
    ];

    // Make API call to logout endpoint (with fallback URL formats)
    $authEndpoints = function_exists('api_url_candidates')
        ? api_url_candidates('auth.php?action=logout', ['auth/logout', 'auth.php/logout'])
        : [
            api_url('auth.php?action=logout'),
            api_url('auth/logout'),
            api_url('auth.php/logout')
        ];

    $response = false;
    $httpCode = 0;

    foreach ($authEndpoints as $endpointUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpointUrl);
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

        if ($response !== false && $httpCode !== 404) {
            break;
        }
    }

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