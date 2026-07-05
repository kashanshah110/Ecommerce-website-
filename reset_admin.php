<?php
/**
 * Reset Admin Password
 * Run this file to reset the admin password
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Generate new password hash for "admin123"
$new_password = 'admin123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "Generated hash for 'admin123': " . $hash . "\n";

// Update admin user
$db->query("UPDATE users SET password = :password WHERE email = :email");
$db->bind(':password', $hash);
$db->bind(':email', 'admin@naeemelectronic.com');

if ($db->execute()) {
    echo "Admin password updated successfully!\n";
    echo "Email: admin@naeemelectronic.com\n";
    echo "Password: admin123\n";
} else {
    echo "Failed to update admin password.\n";
}
