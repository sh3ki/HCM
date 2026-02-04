<?php
/**
 * Migration Script: Add auto_password_changed and is_new columns
 * Run this once to add the required columns to the users table
 */

require_once __DIR__ . '/../config/database.php';

echo "<h2>Database Migration: Add auto_password_changed and is_new columns</h2>";
echo "<hr>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'auto_password_changed'");
    $autoPasswordExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_new'");
    $isNewExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'requires_password_change'");
    $requiresPasswordExists = $stmt->rowCount() > 0;
    
    echo "<h3>Current Status:</h3>";
    echo "<ul>";
    echo "<li>auto_password_changed: " . ($autoPasswordExists ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "<li>is_new: " . ($isNewExists ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "<li>requires_password_change: " . ($requiresPasswordExists ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "</ul>";
    echo "<hr>";
    
    $changes = [];
    
    // Add auto_password_changed column
    if (!$autoPasswordExists) {
        echo "<p>Adding <strong>auto_password_changed</strong> column...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN auto_password_changed TINYINT(1) DEFAULT 0 COMMENT 'True when using auto-generated password, false after user changes it' AFTER is_active");
        echo "<p style='color: green;'>✓ auto_password_changed column added successfully!</p>";
        $changes[] = 'auto_password_changed added';
    } else {
        echo "<p style='color: blue;'>ℹ auto_password_changed column already exists</p>";
    }
    
    // Add is_new column
    if (!$isNewExists) {
        echo "<p>Adding <strong>is_new</strong> column...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_new TINYINT(1) DEFAULT 0 COMMENT 'True for new accounts requiring OTP verification, false after verified' AFTER auto_password_changed");
        echo "<p style='color: green;'>✓ is_new column added successfully!</p>";
        $changes[] = 'is_new added';
        
        // Set existing users to is_new = 0 (already verified)
        echo "<p>Updating existing users to is_new = 0...</p>";
        $stmt = $pdo->exec("UPDATE users SET is_new = 0");
        echo "<p style='color: green;'>✓ Updated $stmt existing users</p>";
    } else {
        echo "<p style='color: blue;'>ℹ is_new column already exists</p>";
    }
    
    // Add requires_password_change column
    if (!$requiresPasswordExists) {
        echo "<p>Adding <strong>requires_password_change</strong> column...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN requires_password_change TINYINT(1) DEFAULT 0 COMMENT 'Prompt user to change password on next login' AFTER is_new");
        echo "<p style='color: green;'>✓ requires_password_change column added successfully!</p>";
        $changes[] = 'requires_password_change added';
    } else {
        echo "<p style='color: blue;'>ℹ requires_password_change column already exists</p>";
    }
    
    echo "<hr>";
    
    // Verify final structure
    echo "<h3>Final Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Comment</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $highlight = in_array($row['Field'], ['auto_password_changed', 'is_new', 'requires_password_change']) ? 'background: #d1fae5;' : '';
        echo "<tr style='$highlight'>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td style='font-size: 11px;'>" . htmlspecialchars($row['Comment'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div style='background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2 style='color: #065f46; margin-top: 0;'>✅ Migration Completed Successfully!</h2>";
    if (!empty($changes)) {
        echo "<p style='color: #065f46;'><strong>Changes made:</strong></p>";
        echo "<ul style='color: #065f46;'>";
        foreach ($changes as $change) {
            echo "<li>$change</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: #065f46;'>No changes needed - all columns already exist!</p>";
    }
    echo "<p style='color: #065f46;'><strong>What this enables:</strong></p>";
    echo "<ul style='color: #065f46;'>";
    echo "<li><strong>auto_password_changed</strong>: Set to TRUE when employee is created with auto-generated password, becomes FALSE permanently after user changes it</li>";
    echo "<li><strong>is_new</strong>: Set to TRUE when account is created, becomes FALSE after OTP verification</li>";
    echo "<li><strong>requires_password_change</strong>: Can be used to force password change on next login</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p><a href='../views/employees.php' style='background: #1b68ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>← Back to Employees</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px;'>";
    echo "<h2 style='color: #991b1b;'>❌ Migration Failed!</h2>";
    echo "<p style='color: #991b1b;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: #991b1b;'>Please check your database connection and try again.</p>";
    echo "</div>";
}
?>
