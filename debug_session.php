<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
        .section { margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Session & Authentication Debug</h1>
    
    <?php
    session_start();
    
    echo "<div class='section'>";
    echo "<h2>Session Information</h2>";
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>Session ID</td><td>" . session_id() . "</td></tr>";
    echo "<tr><td>Session Name</td><td>" . session_name() . "</td></tr>";
    echo "<tr><td>Session Save Path</td><td>" . session_save_path() . "</td></tr>";
    echo "<tr><td>Session Cookie Params</td><td><pre>" . print_r(session_get_cookie_params(), true) . "</pre></td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>Session Data</h2>";
    if (empty($_SESSION)) {
        echo "<p><strong>Session is EMPTY</strong></p>";
    } else {
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>Authentication Status</h2>";
    require_once __DIR__ . '/includes/auth_helper.php';
    echo "<table>";
    echo "<tr><th>Check</th><th>Result</th></tr>";
    echo "<tr><td>isAuthenticated()</td><td>" . (isAuthenticated() ? 'TRUE' : 'FALSE') . "</td></tr>";
    echo "<tr><td>Has authenticated key</td><td>" . (isset($_SESSION['authenticated']) ? 'TRUE' : 'FALSE') . "</td></tr>";
    echo "<tr><td>authenticated value</td><td>" . ($_SESSION['authenticated'] ?? 'NOT SET') . "</td></tr>";
    echo "<tr><td>Has access_token</td><td>" . (isset($_SESSION['access_token']) ? 'TRUE' : 'FALSE') . "</td></tr>";
    echo "<tr><td>User ID</td><td>" . ($_SESSION['user_id'] ?? 'NOT SET') . "</td></tr>";
    echo "<tr><td>Username</td><td>" . ($_SESSION['username'] ?? 'NOT SET') . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>PHP Configuration</h2>";
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>session.cookie_lifetime</td><td>" . ini_get('session.cookie_lifetime') . "</td></tr>";
    echo "<tr><td>session.cookie_path</td><td>" . ini_get('session.cookie_path') . "</td></tr>";
    echo "<tr><td>session.cookie_domain</td><td>" . ini_get('session.cookie_domain') . "</td></tr>";
    echo "<tr><td>session.cookie_secure</td><td>" . ini_get('session.cookie_secure') . "</td></tr>";
    echo "<tr><td>session.cookie_httponly</td><td>" . ini_get('session.cookie_httponly') . "</td></tr>";
    echo "<tr><td>session.cookie_samesite</td><td>" . ini_get('session.cookie_samesite') . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>Cookies</h2>";
    if (empty($_COOKIE)) {
        echo "<p><strong>No cookies found</strong></p>";
    } else {
        echo "<pre>" . print_r($_COOKIE, true) . "</pre>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>Test API Call</h2>";
    echo "<button onclick='testDashboardAPI()'>Test Dashboard API</button>";
    echo "<pre id='apiResult'></pre>";
    echo "</div>";
    ?>
    
    <script>
        async function testDashboardAPI() {
            const result = document.getElementById('apiResult');
            result.textContent = 'Loading...';
            
            try {
                const response = await fetch('http://localhost/HCM/api/dashboard.php?type=stats');
                const data = await response.json();
                result.textContent = JSON.stringify(data, null, 2);
            } catch (error) {
                result.textContent = 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>
