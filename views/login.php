<?php
// Start output buffering to prevent any accidental output before headers
ob_start();
session_start();

// Include auth helper
require_once __DIR__ . '/../includes/auth_helper.php';

// If already authenticated, redirect to appropriate page
if (isAuthenticated()) {
    $roleId = intval($_SESSION['role_id'] ?? 1); // Default to admin if not set
    if ($roleId !== 1) { // If not admin (role_id 1), redirect to employee payslip
        header('Location: employee_payslip.php');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

// Handle login form submission
if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember-me']);

    // Prepare data for API call
    $data = [
        'username' => $username,
        'password' => $password,
        'remember_me' => $remember_me
    ];

    // Make API call to login endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/HCM/api/auth.php/login");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Debug logging
    error_log("Login attempt - HTTP Code: $httpCode");
    error_log("Login response: " . substr($response, 0, 500));

    if ($response === false) {
        error_log("CURL Error: $curlError");
        $error = 'Unable to connect to authentication service';
    } else {
        // Extract JSON from response (in case there are PHP warnings before JSON)
        $jsonStart = strpos($response, '{');
        if ($jsonStart !== false) {
            $jsonResponse = substr($response, $jsonStart);
            $responseData = json_decode($jsonResponse, true);
        } else {
            $responseData = json_decode($response, true);
        }

        if ($httpCode === 200 && $responseData && isset($responseData['success']) && $responseData['success']) {
            // Store authentication data in session
            $_SESSION['user_id'] = $responseData['data']['user']['id'] ?? null;
            $_SESSION['role_id'] = $responseData['data']['user']['role_id'] ?? 1; // Store role_id
            $_SESSION['employee_id'] = $responseData['data']['user']['employee_id'] ?? null;
            $_SESSION['is_new'] = $responseData['data']['user']['is_new'] ?? 0;
            $_SESSION['username'] = $responseData['data']['user']['username'] ?? null;
            $_SESSION['email'] = $responseData['data']['user']['email'] ?? null;
            $_SESSION['employee_email'] = $responseData['data']['user']['employee_email'] ?? null;
            $_SESSION['role'] = $responseData['data']['user']['role'] ?? null;
            $_SESSION['first_name'] = $responseData['data']['user']['first_name'] ?? null;
            $_SESSION['last_name'] = $responseData['data']['user']['last_name'] ?? null;
            $_SESSION['access_token'] = $responseData['data']['access_token'] ?? null;
            $_SESSION['refresh_token'] = $responseData['data']['refresh_token'] ?? null;
            $_SESSION['authenticated'] = true;

            // Clear any output buffer before redirect
            ob_end_clean();
            
            // Redirect based on role_id (1 = Admin, all others = Employee)
            $roleId = intval($_SESSION['role_id'] ?? 1);
            if ($roleId !== 1) {
                header('Location: employee_payslip.php');
                exit();
            } else {
                header('Location: index.php');
                exit();
            }
        } else {
            // Handle various error scenarios
            if ($responseData && isset($responseData['message'])) {
                $error = $responseData['message'];
            } elseif ($httpCode !== 200) {
                $error = "Server error (HTTP $httpCode)";
            } elseif (!$responseData) {
                $error = 'Invalid response from authentication service';
            } else {
                $error = 'Login failed';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HCM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1b68ff',
                        secondary: '#6c757d',
                        success: '#3ad29f',
                        danger: '#dc3545',
                        warning: '#eea303',
                        info: '#17a2b8',
                        light: '#f8f9fa',
                        dark: '#343a40'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-primary rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    HCM System Login
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Human Capital Management System
                </p>
            </div>

            <!-- Login Form -->
            <form class="mt-8 space-y-6" method="POST" action="">
                <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input
                                id="username"
                                name="username"
                                type="text"
                                required
                                class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                                placeholder="Enter your username"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                                placeholder="Enter your password"
                            >
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember-me"
                            name="remember-me"
                            type="checkbox"
                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        >
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-primary hover:text-blue-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-blue-300 group-hover:text-blue-200"></i>
                        </span>
                        Sign in
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Please enter your credentials to access the system.
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Background Pattern -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-100"></div>
        <svg class="absolute inset-0 h-full w-full stroke-blue-200/50" fill="none" viewBox="0 0 200 200">
            <defs>
                <pattern id="pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M.5 40V.5H40" fill="none" stroke-width="1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#pattern)" />
        </svg>
    </div>
</body>
</html>