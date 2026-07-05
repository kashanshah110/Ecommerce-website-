<?php
/**
 * Debug Login Script
 * Check database connection and user data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>Login Debug Information</h2>";

// Test database connection
echo "<h3>1. Database Connection</h3>";
try {
    $db = new Database();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if users table exists
echo "<h3>2. Check Users Table</h3>";
try {
    $db->query("SHOW TABLES LIKE 'users'");
    $result = $db->fetch();
    if ($result) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Users table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking users table: " . $e->getMessage() . "</p>";
}

// Count users in database
echo "<h3>3. User Count</h3>";
try {
    $db->query("SELECT COUNT(*) as count FROM users");
    $result = $db->fetch();
    echo "<p>Total users: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error counting users: " . $e->getMessage() . "</p>";
}

// List all users
echo "<h3>4. All Users in Database</h3>";
try {
    $db->query("SELECT id, username, email, full_name, role, is_active, is_verified FROM users");
    $users = $db->fetchAll();
    
    if (empty($users)) {
        echo "<p style='color: red;'>✗ No users found in database</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Active</th><th>Verified</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($user['is_verified'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error fetching users: " . $e->getMessage() . "</p>";
}

// Test password verification
echo "<h3>5. Test Password Verification</h3>";
try {
    $db->query("SELECT email, password FROM users LIMIT 1");
    $user = $db->fetch();
    
    if ($user) {
        $test_password = 'admin123';
        $is_valid = password_verify($test_password, $user['password']);
        
        echo "<p>Testing password '$test_password' against user: " . htmlspecialchars($user['email']) . "</p>";
        echo "<p>Password valid: " . ($is_valid ? '<span style="color: green;">Yes</span>' : '<span style="color: red;">No</span>') . "</p>";
        
        if (!$is_valid) {
            echo "<p style='color: orange;'>The password hash in the database may not match 'admin123'</p>";
            echo "<p>Generating new hash for 'admin123'...</p>";
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "<p>New hash: " . $new_hash . "</p>";
        }
    } else {
        echo "<p style='color: red;'>No users found to test password</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error testing password: " . $e->getMessage() . "</p>";
}

// Check session configuration
echo "<h3>6. Session Configuration</h3>";
echo "<p>Session status: " . session_status() . "</p>";
echo "<p>Session name: " . SESSION_NAME . "</p>";
echo "<p>Session lifetime: " . SESSION_LIFETIME . " seconds</p>";

echo "<h3>7. Configuration</h3>";
echo "<p>DB_HOST: " . DB_HOST . "</p>";
echo "<p>DB_NAME: " . DB_NAME . "</p>";
echo "<p>DB_USER: " . DB_USER . "</p>";
echo "<p>SITE_URL: " . SITE_URL . "</p>";

echo "<hr>";
echo "<p><strong>Recommendations:</strong></p>";
echo "<ul>";
echo "<li>If no users exist, import the database.sql file</li>";
echo "<li>If password verification fails, run reset_admin.php</li>";
echo "<li>If users table doesn't exist, import the database schema</li>";
echo "</ul>";
