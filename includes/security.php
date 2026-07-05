<?php
/**
 * Naeem Electronic - Security Middleware
 * Centralized security functions and middleware
 */

require_once __DIR__ . '/functions.php';

/**
 * Security Headers
 * Sets security-related HTTP headers
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Strict Transport Security (HTTPS) - uncomment in production
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    
    // Content Security Policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self';");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions Policy
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/**
 * Rate Limiter
 * Prevents brute force attacks
 */
class RateLimiter {
    private $maxAttempts;
    private $timeWindow;
    private $identifier;
    
    public function __construct($maxAttempts = 5, $timeWindow = 300) { // 5 attempts in 5 minutes
        $this->maxAttempts = $maxAttempts;
        $this->timeWindow = $timeWindow;
        $this->identifier = $this->getIdentifier();
    }
    
    private function getIdentifier() {
        // Use IP address as identifier
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public function checkRateLimit() {
        $attempts = $this->getAttempts();
        
        if ($attempts >= $this->maxAttempts) {
            return false;
        }
        
        $this->incrementAttempts();
        return true;
    }
    
    private function getAttempts() {
        $key = 'rate_limit_' . $this->identifier;
        return isset($_SESSION[$key]) ? $_SESSION[$key]['count'] : 0;
    }
    
    private function incrementAttempts() {
        $key = 'rate_limit_' . $this->identifier;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'first_attempt' => time()
            ];
        } else {
            // Reset if time window has passed
            if (time() - $_SESSION[$key]['first_attempt'] > $this->timeWindow) {
                $_SESSION[$key] = [
                    'count' => 1,
                    'first_attempt' => time()
                ];
            } else {
                $_SESSION[$key]['count']++;
            }
        }
    }
    
    public function getRemainingTime() {
        $key = 'rate_limit_' . $this->identifier;
        if (isset($_SESSION[$key])) {
            $elapsed = time() - $_SESSION[$key]['first_attempt'];
            return max(0, $this->timeWindow - $elapsed);
        }
        return 0;
    }
    
    public function reset() {
        $key = 'rate_limit_' . $this->identifier;
        unset($_SESSION[$key]);
    }
}

/**
 * Input Validation
 * Comprehensive input validation
 */
class InputValidator {
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate URL
     */
    public static function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate integer
     */
    public static function validateInt($value, $min = null, $max = null) {
        $options = [];
        if ($min !== null) $options['min_range'] = $min;
        if ($max !== null) $options['max_range'] = $max;
        
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => $options]) !== false;
    }
    
    /**
     * Validate float
     */
    public static function validateFloat($value, $min = null, $max = null) {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false) return false;
        
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        
        return true;
    }
    
    /**
     * Sanitize string
     */
    public static function sanitizeString($string, $maxLength = null) {
        $string = trim($string);
        $string = strip_tags($string);
        
        if ($maxLength !== null) {
            $string = substr($string, 0, $maxLength);
        }
        
        return $string;
    }
    
    /**
     * Validate phone number (Pakistan format)
     */
    public static function validatePhone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid Pakistani phone number
        // Format: 03XXXXXXXXX or 92XXXXXXXXXX
        return preg_match('/^(03[0-9]{9}|92[0-9]{10})$/', $phone) === 1;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePasswordStrength($password) {
        $score = 0;
        
        if (strlen($password) >= 8) $score++;
        if (strlen($password) >= 12) $score++;
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;
        
        return $score; // 0-6 scale
    }
}

/**
 * File Upload Security
 * Secure file upload handling
 */
class FileUploadSecurity {
    private $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    private $maxSize = 5242880; // 5MB
    
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    public function validateFile($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            return ['valid' => false, 'error' => 'File too large (max 5MB)'];
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['valid' => false, 'error' => 'Invalid file extension'];
        }
        
        // Check for double extensions
        if (count(explode('.', $file['name'])) > 2) {
            return ['valid' => false, 'error' => 'Invalid file name'];
        }
        
        return ['valid' => true];
    }
    
    public function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize basename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        
        // Generate random string
        $random = bin2hex(random_bytes(8));
        
        return $basename . '_' . $random . '.' . $extension;
    }
    
    public function moveUploadedFile($file, $destination) {
        $validation = $this->validateFile($file);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        $secureName = $this->generateSecureFilename($file['name']);
        $destination = rtrim($destination, '/') . '/' . $secureName;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'filename' => $secureName];
        }
        
        return ['success' => false, 'error' => 'Failed to move file'];
    }
}

/**
 * SQL Injection Prevention
 * Additional SQL injection protection
 */
class SQLInjectionProtection {
    /**
     * Detect SQL injection patterns
     */
    public static function detectSQLInjection($input) {
        $patterns = [
            '/\b(OR|AND)\s+\d+\s*=\s*\d+/i',
            '/\b(OR|AND)\s+["\']?\w+["\']?\s*=\s*["\']?\w+["\']?/i',
            '/\b(UNION|SELECT|INSERT|UPDATE|DELETE|DROP|ALTER|CREATE)\b/i',
            '/--/',
            '/\/\*/',
            '/\*\/',
            '/;/',
            '/\bEXEC\b/i',
            '/\bXP_CMD\b/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize input for SQL
     */
    public static function sanitizeForSQL($input) {
        if (self::detectSQLInjection($input)) {
            error_log("SQL injection attempt detected: " . $input);
            return '';
        }
        
        return $input;
    }
}

/**
 * XSS Prevention
 * Additional XSS protection
 */
class XSSProtection {
    /**
     * Detect XSS patterns
     */
    public static function detectXSS($input) {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<\?php/',
            '/<\?/',
            '/eval\s*\(/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize output
     */
    public static function sanitizeOutput($input) {
        if (self::detectXSS($input)) {
            error_log("XSS attempt detected: " . $input);
        }
        
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Session Security
 * Enhanced session security
 */
class SessionSecurity {
    /**
     * Secure session start
     */
    public static function secureSessionStart() {
        // Set secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 86400, // 24 hours
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
            $_SESSION['last_regenerated'] = time();
        } else {
            // Regenerate every 30 minutes
            if (time() - $_SESSION['last_regenerated'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['last_regenerated'] = time();
            }
        }
        
        // Check for session fixation
        if (!isset($_SESSION['ip_address'])) {
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        } elseif ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            // Session hijacking attempt - destroy session
            session_destroy();
            session_start();
            session_regenerate_id(true);
        }
    }
    
    /**
     * Destroy session securely
     */
    public static function destroySession() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        
        session_destroy();
    }
}

/**
 * Apply security headers on every request
 */
setSecurityHeaders();
