<?php
/**
 * Test Email Sending for Employee Credentials
 * This file tests the SMTP email functionality
 */

require_once __DIR__ . '/includes/otp_mailer.php';

// Test email parameters
$testEmail = 'sheikaigarcia@gmail.com'; // Change this to your test email
$testName = 'Test Employee';
$testUsername = 'test_user123';
$testPassword = 'TempPass123456';

echo "<h2>Testing Employee Credentials Email</h2>";
echo "<p>Sending to: <strong>$testEmail</strong></p>";
echo "<p>Username: <strong>$testUsername</strong></p>";
echo "<p>Password: <strong>$testPassword</strong></p>";
echo "<hr>";

$subject = "Welcome to HCM System - Your Login Credentials";
$htmlBody = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1b68ff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .credentials { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #1b68ff; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Welcome to HCM System</h1>
        </div>
        <div class='content'>
            <h2>Hello " . htmlspecialchars($testName) . ",</h2>
            <p>Your employee account has been created successfully. Below are your login credentials:</p>
            <div class='credentials'>
                <p><strong>Username:</strong> " . htmlspecialchars($testUsername) . "</p>
                <p><strong>Temporary Password:</strong> " . htmlspecialchars($testPassword) . "</p>
                <p><strong>Login URL:</strong> <a href='http://localhost/HCM/views/login.php'>Click here to login</a></p>
            </div>
            <p><strong>Important:</strong></p>
            <ul>
                <li>Please use the username and password above to log in</li>
                <li>You will need to verify your email with an OTP code upon first login</li>
                <li>Please keep your credentials secure and do not share them</li>
                <li>Change your password after logging in for the first time</li>
            </ul>
        </div>
        <div class='footer'>
            <p>This is an automated message from HCM System. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
";

try {
    echo "<p>Attempting to send email via SMTP...</p>";
    smtp_send_mail($testEmail, $testName, $subject, $htmlBody);
    echo "<div style='background: #d1fae5; border: 1px solid #10b981; padding: 15px; border-radius: 5px; color: #065f46;'>";
    echo "<strong>✓ SUCCESS!</strong><br>";
    echo "Email sent successfully to <strong>$testEmail</strong><br>";
    echo "Check your inbox (and spam folder) for the credentials email.";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; border: 1px solid #ef4444; padding: 15px; border-radius: 5px; color: #991b1b;'>";
    echo "<strong>✗ ERROR!</strong><br>";
    echo "Failed to send email: " . htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "<strong>Troubleshooting:</strong><br>";
    echo "1. Check that the email credentials in config/email.php are correct<br>";
    echo "2. If using Gmail, make sure 'App Password' is enabled (not regular password)<br>";
    echo "3. Check your internet connection<br>";
    echo "4. Check firewall settings for SMTP port 587";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Email Configuration:</h3>";
echo "<pre>";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP User: " . SMTP_USER . "\n";
echo "From Email: " . SMTP_FROM_EMAIL . "\n";
echo "From Name: " . SMTP_FROM_NAME . "\n";
echo "</pre>";

echo "<p><a href='views/employees.php'>← Back to Employees</a></p>";
?>
