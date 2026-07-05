<?php
/**
 * Force Reset Admin Password
 * This will forcefully reset the admin password
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>Force Reset Admin Password</h2><hr>";

try {
    $db = new Database();
    echo "<p style='color: green;'>✓ Database connected</p>";
    
    // Generate new password hash
    $new_password = 'admin123';
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    echo "<p>New password hash for '$new_password': " . $new_hash . "</p>";
    
    // Update admin user
    $db->query("UPDATE users SET password = :password WHERE email = :email");
    $db->bind(':password', $new_hash);
    $db->bind(':email', 'admin@naeemelectronic.com');
    
    if ($db->execute()) {
        echo "<p style='color: green; font-size: 18px;'>✓ Admin password reset successfully!</p>";
        echo "<hr>";
        echo "<h3>Login Credentials:</h3>";
        echo "<p><strong>Email:</strong> admin@naeemelectronic.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<hr>";
        echo "<p><a href='login.php' style='font-size: 18px;'>Click here to login</a></p>";
        
        // Verify the update
        $db->query("SELECT password FROM users WHERE email = :email");
        $db->bind(':email', 'admin@naeemelectronic.com');
        $user = $db->fetch();
        
        if ($user) {
            $verify = password_verify($new_password, $user['password']);
            echo "<p>Password verification test: " . ($verify ? '<span style="color: green;">✓ PASS</span>' : '<span style="color: red;">✗ FAIL</span>') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to update password</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
