<?php
/**
 * Naeem Electronic - Admin Login Page
 * Separate login for admin panel
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in as admin
if (isLoggedIn() && isAdmin()) {
    redirect('index.php');
}

$page_title = 'Admin Login';

$db = new Database();

// Check for login attempts lockout
if (isset($_SESSION['admin_login_attempts']) && $_SESSION['admin_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
    if (isset($_SESSION['admin_locked_until']) && time() < $_SESSION['admin_locked_until']) {
        $lockout_time = ceil(($_SESSION['admin_locked_until'] - time()) / 60);
        $lockout_message = "Too many failed attempts. Please try again in $lockout_time minutes.";
    } else {
        // Reset lockout
        unset($_SESSION['admin_login_attempts']);
        unset($_SESSION['admin_locked_until']);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request. Please try again.');
    } elseif (isset($lockout_message)) {
        setFlash('error', $lockout_message);
    } else {
        // Check login attempts
        if (!isset($_SESSION['admin_login_attempts'])) {
            $_SESSION['admin_login_attempts'] = 0;
        }
        
        // Find admin user by email
        $db->query("SELECT * FROM users WHERE email = :email AND role = 'admin' AND is_active = 1");
        $db->bind(':email', $email);
        $user = $db->fetch();
        
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
                $_SESSION['is_admin'] = true;
                
                // Reset login attempts
                unset($_SESSION['admin_login_attempts']);
                unset($_SESSION['admin_locked_until']);
                
                // Update last login
                $db->query("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = :id");
                $db->bind(':id', $user['id']);
                $db->execute();
                
                // Redirect to admin dashboard
                redirect('index.php');
            }
        } else {
            // Failed login
            $_SESSION['admin_login_attempts']++;
            
            if ($_SESSION['admin_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $_SESSION['admin_locked_until'] = time() + LOGIN_LOCKOUT_TIME;
                setFlash('error', 'Too many failed attempts. Account locked for 15 minutes.');
            } else {
                $attempts_left = MAX_LOGIN_ATTEMPTS - $_SESSION['admin_login_attempts'];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Naeem Electronic</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1A237E 0%, #0D47A1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .admin-login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .admin-login-header {
            background: linear-gradient(135deg, #1A237E, #0D47A1);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .admin-login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .admin-login-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .admin-login-header .logo-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #FF6F00;
        }
        
        .admin-login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            height: 50px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px 20px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #1A237E;
            box-shadow: 0 0 0 3px rgba(26,35,126,0.1);
        }
        
        .input-group-text {
            height: 50px;
            border: 2px solid #e0e0e0;
            border-right: none;
            background: #f8f9fa;
            border-radius: 8px 0 0 8px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }
        
        .btn-login {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #1A237E, #0D47A1);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26,35,126,0.3);
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }
        
        .back-to-site a {
            color: #1A237E;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .back-to-site a:hover {
            color: #FF6F00;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <div class="logo-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h1>Admin Panel</h1>
                <p>Naeem Electronic Management System</p>
            </div>
            
            <div class="admin-login-body">
                <?php 
                $flash_types = ['success', 'error', 'warning', 'info'];
                foreach ($flash_types as $type):
                    $message = getFlash($type);
                    if ($message):
                ?>
                <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php 
                    endif;
                endforeach; 
                ?>
                
                <form action="login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="email">Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter admin email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Login to Admin Panel
                    </button>
                </form>
                
                <div class="back-to-site">
                    <a href="<?php echo SITE_URL; ?>">
                        <i class="fas fa-arrow-left me-2"></i> Back to Website
                    </a>
                </div>
            </div>
        </div>
        
        <p style="text-align: center; color: rgba(255,255,255,0.7); margin-top: 20px; font-size: 13px;">
            © <?php echo date('Y'); ?> Naeem Electronic. All Rights Reserved.
        </p>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
