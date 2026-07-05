<?php
/**
 * Naeem Electronic - Login Page
 * User authentication
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$page_title = 'Login';
$page_description = 'Login to your account';
$current_page = 'login';

$db = new Database();

// Check for login attempts lockout
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
    if (isset($_SESSION['locked_until']) && time() < $_SESSION['locked_until']) {
        $lockout_time = ceil(($_SESSION['locked_until'] - time()) / 60);
        $lockout_message = "Too many failed attempts. Please try again in $lockout_time minutes.";
    } else {
        // Reset lockout
        unset($_SESSION['login_attempts']);
        unset($_SESSION['locked_until']);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request. Please try again.');
    } elseif (isset($lockout_message)) {
        setFlash('error', $lockout_message);
    } else {
        // Check login attempts
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
        }
        
        // Find user by email or username
        $db->query("SELECT * FROM users WHERE (email = :email OR username = :username) AND is_active = 1");
        $db->bind(':email', $email);
        $db->bind(':username', $email);
        $user = $db->fetch();
        
        // Debug: Log user found
        error_log("Login attempt for: $email");
        error_log("User found: " . ($user ? 'YES' : 'NO'));
        
        if ($user) {
            error_log("User ID: " . $user['id']);
            error_log("User active: " . ($user['is_active'] ? 'YES' : 'NO'));
            error_log("Password verify test: " . (password_verify($password, $user['password']) ? 'YES' : 'NO'));
        }
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Check if account is locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $lock_time = ceil((strtotime($user['locked_until']) - time()) / 60);
                setFlash('error', "Account is locked. Please try again in $lock_time minutes.");
            } else {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Reset login attempts
                unset($_SESSION['login_attempts']);
                unset($_SESSION['locked_until']);
                
                // Update last login
                $db->query("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = :id");
                $db->bind(':id', $user['id']);
                $db->execute();
                
                // Handle remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Store token in database (you'd need a remember_tokens table)
                    // For now, we'll extend session
                    ini_set('session.cookie_lifetime', 2592000); // 30 days
                }
                
                // Redirect to intended page or dashboard
                $redirect = $_GET['redirect'] ?? 'dashboard.php';
                redirect($redirect);
            }
        } else {
            // Failed login
            $_SESSION['login_attempts']++;
            
            if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $_SESSION['locked_until'] = time() + LOGIN_LOCKOUT_TIME;
                setFlash('error', 'Too many failed attempts. Account locked for 15 minutes.');
            } else {
                $attempts_left = MAX_LOGIN_ATTEMPTS - $_SESSION['login_attempts'];
                setFlash('error', "Invalid email or password. $attempts_left attempts remaining.");
            }
            
            // Update user login attempts in database
            if ($user) {
                $db->query("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = :id");
                $db->bind(':id', $user['id']);
                $db->execute();
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">Login</h1>
        <p class="mb-0">Welcome back! We missed you</p>
    </div>
</section>

<!-- Login Section -->
<section class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="auth-card">
                    <div class="card-header">
                        <h4 class="mb-0 text-center">Login to Your Account</h4>
                    </div>
                    <div class="card-body">
                        <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email or Username</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="email" name="email" 
                                           placeholder="Enter your email or username" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter your password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                                <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </button>
                            
                            <div class="text-center mb-3">
                                <span class="text-muted">or login with</span>
                            </div>
                            
                            <div class="social-login mb-3">
                                <button type="button" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="fab fa-facebook me-2"></i> Continue with Facebook
                                </button>
                                <button type="button" class="btn btn-outline-danger w-100">
                                    <i class="fab fa-google me-2"></i> Continue with Google
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none fw-bold">Register here</a></p>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tips -->
                <div class="security-tips mt-4">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-shield-alt me-2"></i>Security Tips</h6>
                        <ul class="mb-0 small">
                            <li>Use a strong password with at least 8 characters</li>
                            <li>Never share your password with anyone</li>
                            <li>Log out after using a shared device</li>
                            <li>Keep your browser and software updated</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.page-header {
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    color: white;
}

.auth-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.auth-card .card-header {
    background: #f8f9fa;
    padding: 25px;
    border-bottom: 2px solid #FF6F00;
}

.auth-card .card-body {
    padding: 30px;
}

.input-group-text {
    background: #f8f9fa;
    border-color: #dee2e6;
}

.social-login button {
    font-weight: 500;
}

.security-tips {
    max-width: 500px;
    margin: 0 auto;
}

.security-tips .alert {
    border-radius: 8px;
}
</style>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
