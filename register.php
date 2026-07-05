<?php
/**
 * Naeem Electronic - Registration Page
 * User registration
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$page_title = 'Register';
$page_description = 'Create a new account';
$current_page = 'register';

$db = new Database();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request. Please try again.');
    } elseif (!$terms) {
        setFlash('error', 'You must agree to the Terms & Conditions.');
    } elseif ($password !== $confirm_password) {
        setFlash('error', 'Passwords do not match.');
    } elseif (strlen($password) < 8) {
        setFlash('error', 'Password must be at least 8 characters long.');
    } elseif (!isValidEmail($email)) {
        setFlash('error', 'Please enter a valid email address.');
    } elseif (!isValidPhone($phone)) {
        setFlash('error', 'Please enter a valid phone number.');
    } else {
        // Check if email already exists
        $db->query("SELECT id FROM users WHERE email = :email");
        $db->bind(':email', $email);
        if ($db->fetch()) {
            setFlash('error', 'Email already registered. Please use a different email or login.');
        } else {
            // Check if username already exists
            $db->query("SELECT id FROM users WHERE username = :username");
            $db->bind(':username', $username);
            if ($db->fetch()) {
                setFlash('error', 'Username already taken. Please choose a different username.');
            } else {
                // Check password strength
                $strength = checkPasswordStrength($password);
                if ($strength < 3) {
                    setFlash('error', 'Password is too weak. Please include uppercase, lowercase, numbers, and special characters.');
                } else {
                    // Hash password
                    $hashed_password = hashPassword($password);
                    
                    // Generate verification token
                    $verification_token = bin2hex(random_bytes(32));
                    
                    // Insert user
                    $db->query("INSERT INTO users (username, email, password, full_name, phone, role, is_active, is_verified, verification_token) 
                               VALUES (:username, :email, :password, :full_name, :phone, 'customer', 1, 0, :token)");
                    $db->bind(':username', $username);
                    $db->bind(':email', $email);
                    $db->bind(':password', $hashed_password);
                    $db->bind(':full_name', $full_name);
                    $db->bind(':phone', $phone);
                    $db->bind(':token', $verification_token);
                    
                    if ($db->execute()) {
                        // Send verification email (in production, implement email sending)
                        // $verification_link = SITE_URL . '/verify.php?token=' . $verification_token;
                        // sendEmail($email, 'Verify Your Email', "Click here to verify: $verification_link");
                        
                        // Auto-login after registration
                        $user_id = $db->lastInsertId();
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_name'] = $full_name;
                        $_SESSION['user_role'] = 'customer';
                        
                        setFlash('success', 'Registration successful! Welcome to Naeem Electronic.');
                        redirect('dashboard.php');
                    } else {
                        setFlash('error', 'Registration failed. Please try again.');
                    }
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">Create Account</h1>
        <p class="mb-0">Join Naeem Electronic for exclusive deals and offers</p>
    </div>
</section>

<!-- Register Section -->
<section class="auth-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="auth-card">
                    <div class="card-header">
                        <h4 class="mb-0 text-center">Create Your Account</h4>
                    </div>
                    <div class="card-body">
                        <form action="register.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fullName" class="form-label">Full Name *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" id="fullName" name="full_name" 
                                               placeholder="Enter your full name" required
                                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               placeholder="+92XXXXXXXXXX" required
                                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Enter your email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-at"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Choose a username" required
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                                <small class="text-muted">Username must be unique and contain only letters, numbers, and underscores</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Create a password" required>
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" 
                                               placeholder="Confirm your password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password Strength Indicator -->
                            <div class="mb-3">
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <small class="strength-text" id="strengthText">Password strength</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="terms-conditions.php" target="_blank">Terms & Conditions</a> 
                                        and <a href="privacy-policy.php" target="_blank">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i> Create Account
                            </button>
                            
                            <div class="text-center mb-3">
                                <span class="text-muted">or register with</span>
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
                            <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Login here</a></p>
                        </div>
                    </div>
                </div>
                
                <!-- Benefits -->
                <div class="benefits-card mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-gift text-primary me-2"></i>Benefits of Creating an Account</h5>
                            <ul class="mb-0">
                                <li><i class="fas fa-check text-success me-2"></i>Track your orders</li>
                                <li><i class="fas fa-check text-success me-2"></i>Save items to wishlist</li>
                                <li><i class="fas fa-check text-success me-2"></i>Faster checkout</li>
                                <li><i class="fas fa-check text-success me-2"></i>Exclusive deals and discounts</li>
                                <li><i class="fas fa-check text-success me-2"></i>Order history</li>
                            </ul>
                        </div>
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

.password-strength {
    margin-top: 10px;
}

.strength-bar {
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-fill {
    height: 100%;
    width: 0;
    transition: width 0.3s, background-color 0.3s;
}

.strength-fill.weak {
    width: 25%;
    background-color: #dc3545;
}

.strength-fill.fair {
    width: 50%;
    background-color: #ffc107;
}

.strength-fill.good {
    width: 75%;
    background-color: #0dcaf0;
}

.strength-fill.strong {
    width: 100%;
    background-color: #198754;
}

.social-login button {
    font-weight: 500;
}

.benefits-card .card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.benefits-card ul {
    list-style: none;
    padding: 0;
}

.benefits-card li {
    padding: 5px 0;
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

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    strengthFill.className = 'strength-fill';
    
    switch (strength) {
        case 0:
        case 1:
            strengthFill.classList.add('weak');
            strengthText.textContent = 'Weak';
            strengthText.style.color = '#dc3545';
            break;
        case 2:
            strengthFill.classList.add('fair');
            strengthText.textContent = 'Fair';
            strengthText.style.color = '#ffc107';
            break;
        case 3:
            strengthFill.classList.add('good');
            strengthText.textContent = 'Good';
            strengthText.style.color = '#0dcaf0';
            break;
        case 4:
        case 5:
            strengthFill.classList.add('strong');
            strengthText.textContent = 'Strong';
            strengthText.style.color = '#198754';
            break;
    }
});

// Username validation
document.getElementById('username').addEventListener('input', function() {
    const username = this.value;
    // Allow only letters, numbers, and underscores
    this.value = username.replace(/[^a-zA-Z0-9_]/g, '');
});

// Phone number formatting
document.getElementById('phone').addEventListener('input', function() {
    let phone = this.value.replace(/\D/g, '');
    if (phone.length > 10) {
        phone = phone.slice(0, 10);
    }
    this.value = phone;
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
