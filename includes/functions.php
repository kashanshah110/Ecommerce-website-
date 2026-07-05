<?php
/**
 * Naeem Electronic - Helper Functions
 * Common utility functions used throughout the application
 */

require_once __DIR__ . '/../config/config.php';

// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Generate CSRF token
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Verify CSRF token
function verifyCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect to URL
function redirect($url) {
    header("Location: $url");
    exit();
}

// Set flash message
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

// Get flash message
function getFlash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

// Format price
function formatPrice($price) {
    return CURRENCY_SYMBOL . number_format($price, 2);
}

// Calculate discount percentage
function calculateDiscount($original, $discounted) {
    if ($original > 0) {
        return round((($original - $discounted) / $original) * 100);
    }
    return 0;
}

// Generate slug from string
function generateSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// Generate unique order number
function generateOrderNumber() {
    return 'NE' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (Pakistan format)
function isValidPhone($phone) {
    return preg_match('/^(\+92|0)?[0-9]{10}$/', $phone);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Check password strength
function checkPasswordStrength($password) {
    $strength = 0;
    if (strlen($password) >= 8) $strength++;
    if (preg_match('/[a-z]/', $password)) $strength++;
    if (preg_match('/[A-Z]/', $password)) $strength++;
    if (preg_match('/[0-9]/', $password)) $strength++;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength++;
    
    return $strength;
}

// Upload image
function uploadImage($file, $directory = 'products') {
    $targetDir = UPLOADS_DIR . '/' . $directory . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Check file type
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $directory . '/' . $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

// Delete image
function deleteImage($path) {
    $fullPath = UPLOADS_DIR . '/' . $path;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        return true;
    }
    return false;
}

// Get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Pagination
function paginate($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset
    ];
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 604800) return floor($time / 86400) . ' days ago';
    if ($time < 2592000) return floor($time / 604800) . ' weeks ago';
    if ($time < 31536000) return floor($time / 2592000) . ' months ago';
    return floor($time / 31536000) . ' years ago';
}

// Truncate text
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

// Get setting value
function getSetting($key, $default = null) {
    $db = new Database();
    $db->query("SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1");
    $db->bind(':key', $key);
    $result = $db->fetch();
    return $result ? $result['setting_value'] : $default;
}

// Update setting
function updateSetting($key, $value) {
    $db = new Database();
    $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) 
                ON DUPLICATE KEY UPDATE setting_value = :value");
    $db->bind(':key', $key);
    $db->bind(':value', $value);
    return $db->execute();
}

// Send email (basic implementation - configure SMTP in production)
function sendEmail($to, $subject, $message, $headers = '') {
    $defaultHeaders = "MIME-Version: 1.0\r\n";
    $defaultHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $defaultHeaders .= "From: " . SITE_NAME . " <" . SITE_EMAIL . ">\r\n";
    
    return mail($to, $subject, $message, $defaultHeaders . $headers);
}

// Log activity
function logActivity($user_id, $action, $details = null) {
    $db = new Database();
    $db->query("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
                VALUES (:user_id, :action, :details, :ip, NOW())");
    $db->bind(':user_id', $user_id);
    $db->bind(':action', $action);
    $db->bind(':details', $details);
    $db->bind(':ip', getClientIP());
    $db->execute();
}
