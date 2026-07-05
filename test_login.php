<?php
/**
 * Complete Login Test Script
 * Test all aspects of the login system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Complete Login System Test</h2><hr>";

// Test 1: Database Connection
echo "<h3>1. Database Connection</h3>";
try {
    $db = new Database();
    echo "<p style='color: green;'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check Users Table
echo "<h3>2. Users Table Check</h3>";
try {
    $db->query("DESCRIBE users");
    $columns = $db->fetchAll();
    echo "<p>Users table columns:</p><ul>";
    foreach ($columns as $col) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking users table: " . $e->getMessage() . "</p>";
}

// Test 3: Count Users
echo "<h3>3. User Count</h3>";
try {
    $db->query("SELECT COUNT(*) as count FROM users");
    $result = $db->fetch();
    echo "<p>Total users: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error counting users: " . $e->getMessage() . "</p>";
}

// Test 4: List All Users
echo "<h3>4. All Users</h3>";
try {
    $db->query("SELECT id, username, email, full_name, role, is_active, is_verified FROM users");
    $users = $db->fetchAll();
    
    if (empty($users)) {
        echo "<p style='color: red;'>✗ No users found in database!</p>";
        echo "<p><strong>SOLUTION:</strong> Import database.sql to create default users</p>";
    } else {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th><th>Verified</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . ($user['is_active'] ? '✓' : '✗') . "</td>";
            echo "<td>" . ($user['is_verified'] ? '✓' : '✗') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error fetching users: " . $e->getMessage() . "</p>";
}

// Test 5: Test Password Hashing
echo "<h3>5. Password Hashing Test</h3>";
$test_password = 'admin123';
$hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "<p>Test password: " . $test_password . "</p>";
echo "<p>Generated hash: " . $hash . "</p>";
echo "<p>Verification test: " . (password_verify($test_password, $hash) ? '<span style="color: green;">✓ PASS</span>' : '<span style="color: red;">✗ FAIL</span>') . "</p>";

// Test 6: Test Against Database Users
echo "<h3>6. Test Login Against Database Users</h3>";
try {
    $db->query("SELECT id, username, email, password, role, is_active FROM users");
    $users = $db->fetchAll();
    
    if (empty($users)) {
        echo "<p style='color: red;'>No users to test against</p>";
    } else {
        foreach ($users as $user) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>User:</strong> " . htmlspecialchars($user['email']) . " (" . htmlspecialchars($user['username']) . ")</p>";
            echo "<p><strong>Role:</strong> " . htmlspecialchars($user['role']) . "</p>";
            echo "<p><strong>Active:</strong> " . ($user['is_active'] ? 'Yes' : 'No') . "</p>";
            echo "<p><strong>Password Hash:</strong> " . substr($user['password'], 0, 50) . "...</p>";
            
            // Test with admin123
            $test_result = password_verify('admin123', $user['password']);
            echo "<p><strong>Test with 'admin123':</strong> " . ($test_result ? '<span style="color: green;">✓ Valid</span>' : '<span style="color: red;">✗ Invalid</span>') . "</p>";
            
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error testing passwords: " . $e->getMessage() . "</p>";
}

// Test 7: Session Configuration
echo "<h3>7. Session Configuration</h3>";
echo "<p>Session status: " . session_status() . "</p>";
echo "<p>Session name: " . SESSION_NAME . "</p>";
echo "<p>Session lifetime: " . SESSION_LIFETIME . " seconds</p>";

// Test 8: Create Test User if None Exist
echo "<h3>8. Create Test User (if needed)</h3>";
try {
    $db->query("SELECT COUNT(*) as count FROM users");
    $result = $db->fetch();
    
    if ($result['count'] == 0) {
        echo "<p style='color: orange;'>No users found. Creating test user...</p>";
        
        $test_password = 'admin123';
        $test_hash = password_hash($test_password, PASSWORD_DEFAULT);
        
        $db->query("INSERT INTO users (username, email, password, full_name, phone, role, is_active, is_verified) VALUES (:username, :email, :password, :full_name, :phone, :role, :is_active, :is_verified)");
        $db->bind(':username', 'admin');
        $db->bind(':email', 'admin@naeemelectronic.com');
        $db->bind(':password', $test_hash);
        $db->bind(':full_name', 'Admin User');
        $db->bind(':phone', '+923001234567');
        $db->bind(':role', 'admin');
        $db->bind(':is_active', 1);
        $db->bind(':is_verified', 1);
        
        if ($db->execute()) {
            echo "<p style='color: green;'>✓ Test user created successfully!</p>";
            echo "<p><strong>Email:</strong> admin@naeemelectronic.com</p>";
            echo "<p><strong>Password:</strong> admin123</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create test user</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Users already exist in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 9: Test Actual Login Query
echo "<h3>9. Test Login Query</h3>";
try {
    $test_email = 'admin@naeemelectronic.com';
    $db->query("SELECT * FROM users WHERE (email = :email OR username = :username) AND is_active = 1");
    $db->bind(':email', $test_email);
    $db->bind(':username', $test_email);
    $user = $db->fetch();
    
    if ($user) {
        echo "<p style='color: green;'>✓ Login query successful</p>";
        echo "<p>Found user: " . htmlspecialchars($user['email']) . "</p>";
        
        $test_password = 'admin123';
        if (password_verify($test_password, $user['password'])) {
            echo "<p style='color: green;'>✓ Password verification successful</p>";
            echo "<p><strong>Login should work!</strong></p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification failed</p>";
            echo "<p>Updating password hash...</p>";
            
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password = :password WHERE id = :id");
            $db->bind(':password', $new_hash);
            $db->bind(':id', $user['id']);
            if ($db->execute()) {
                echo "<p style='color: green;'>✓ Password updated successfully</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>✗ User not found or inactive</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Summary & Recommendations</h3>";
echo "<ul>";
echo "<li>If no users exist, the script will create a test user</li>";
echo "<li>If password verification fails, the script will update the password hash</li>";
echo "<li>Try logging in with: admin@naeemelectronic.com / admin123</li>";
echo "<li>If still failing, check PHP error logs for more details</li>";
echo "</ul>";
