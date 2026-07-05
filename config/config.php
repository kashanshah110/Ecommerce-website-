<?php
/**
 * Naeem Electronic - Configuration File
 * Database and Application Settings
 */

// Prevent direct access
if (!defined('APP_PATH')) {
    define('APP_PATH', dirname(__DIR__));
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'naeem_electronic');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'Naeem Electronic');
define('SITE_URL', 'http://localhost/Naeem');
define('SITE_EMAIL', 'info@naeemelectronic.com');
define('SITE_PHONE', '+923001234567');
define('SITE_ADDRESS', 'Islamabad, Pakistan');

// Currency
define('CURRENCY', 'PKR');
define('CURRENCY_SYMBOL', 'Rs. ');

// Paths
define('ASSETS_PATH', SITE_URL . '/assets');
define('UPLOADS_PATH', SITE_URL . '/uploads');
define('UPLOADS_DIR', __DIR__ . '/../uploads');
define('ADMIN_PATH', SITE_URL . '/admin');

// Session Configuration
define('SESSION_NAME', 'naeem_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ADMIN_PER_PAGE', 20);

// File Upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Security
define('HASH_ALGORITHM', 'sha256');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Timezone
date_default_timezone_set('Asia/Karachi');

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
