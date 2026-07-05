<?php
/**
 * Naeem Electronic - Header Include
 * Contains top bar and navigation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Generate CSRF token for this session
$csrf_token = generateCsrfToken();

// Get cart count
$cart_count = 0;
if (isLoggedIn()) {
    $db = new Database();
    $db->query("SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->fetch();
    $cart_count = $result['count'];
} elseif (isset($_SESSION['guest_cart'])) {
    $cart_count = count($_SESSION['guest_cart']);
}

// Get wishlist count
$wishlist_count = 0;
if (isLoggedIn()) {
    $db = new Database();
    $db->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $result = $db->fetch();
    $wishlist_count = $result['count'];
}

// Get categories for navigation
$db = new Database();
$db->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order");
$main_categories = $db->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Your Trusted Home Appliance Partner'; ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
</head>
<body>

<?php
$isAdminArea = (isset($hide_main_nav) && $hide_main_nav) || (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
?>

<?php if (!$isAdminArea): ?>
<!-- Top Bar -->
<div class="top-bar" id="topBar">
    <div class="container">
        <div class="top-bar-content">
            <div class="marquee-container">
                <div class="marquee-text">
                    🎉 Welcome to Naeem Electronic - Pakistan's Trusted Home Appliance Store! | Free Delivery on Orders Above Rs. 5,000 | 24/7 Customer Support | 🏷️ Use coupon WELCOME10 for 10% off your first order
                </div>
            </div>
            <button class="top-bar-close" id="closeTopBar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<!-- Navigation -->
<nav class="navbar">
    <div class="navbar-wrapper">
        <!-- Logo -->
        <a href="<?php echo SITE_URL; ?>" class="navbar-brand">
            <i class="fas fa-bolt navbar-brand-icon"></i>
            <span>Naeem Electronic</span>
        </a>
        
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn nav-icon d-xl-none">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Navigation Links (Desktop) -->
        <ul class="navbar-nav d-none d-xl-flex">
            <li><a href="<?php echo SITE_URL; ?>" class="nav-link <?php echo isset($current_page) && $current_page == 'home' ? 'active' : ''; ?>">Home</a></li>
            <li class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Products <i class="fas fa-chevron-down"></i></a>
                <ul class="dropdown-menu">
                    <?php foreach ($main_categories as $category): ?>
                        <li class="dropdown-submenu">
                            <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>" class="dropdown-item">
                                <?php echo htmlspecialchars($category['name']); ?> <i class="fas fa-chevron-right float-end"></i>
                            </a>
                            <?php
                            // Get subcategories
                            $db->query("SELECT * FROM categories WHERE parent_id = :parent_id AND is_active = 1 ORDER BY sort_order");
                            $db->bind(':parent_id', $category['id']);
                            $subcategories = $db->fetchAll();
                            if ($subcategories):
                            ?>
                            <ul class="dropdown-menu">
                                <?php foreach ($subcategories as $sub): ?>
                                    <li><a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $sub['slug']; ?>" class="dropdown-item"><?php echo htmlspecialchars($sub['name']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="<?php echo SITE_URL; ?>/products.php" class="dropdown-item">View All Products</a></li>
                </ul>
            </li>
            <li><a href="<?php echo SITE_URL; ?>/about.php" class="nav-link <?php echo isset($current_page) && $current_page == 'about' ? 'active' : ''; ?>">About</a></li>
            <li><a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link <?php echo isset($current_page) && $current_page == 'contact' ? 'active' : ''; ?>">Contact</a></li>
            <li><a href="<?php echo SITE_URL; ?>/blog.php" class="nav-link <?php echo isset($current_page) && $current_page == 'blog' ? 'active' : ''; ?>">Blog</a></li>
        </ul>
        
        <!-- Right Icons -->
        <div class="navbar-icons">
            <!-- Search -->
            <div class="search-container d-none d-md-block">
                <button class="search-btn nav-icon" type="button">
                    <i class="fas fa-search"></i>
                </button>
                <input type="text" class="search-input" placeholder="Search products...">
            </div>
            
            <!-- User -->
            <div class="dropdown">
                <button class="nav-icon dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard.php" class="dropdown-item"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard.php?tab=orders" class="dropdown-item"><i class="fas fa-box me-2"></i> My Orders</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard.php?tab=wishlist" class="dropdown-item"><i class="fas fa-heart me-2"></i> Wishlist</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard.php?tab=settings" class="dropdown-item"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a href="<?php echo SITE_URL; ?>/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/login.php" class="dropdown-item"><i class="fas fa-sign-in-alt me-2"></i> Login</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/register.php" class="dropdown-item"><i class="fas fa-user-plus me-2"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Wishlist -->
            <a href="<?php echo isLoggedIn() ? SITE_URL . '/dashboard.php?tab=wishlist' : SITE_URL . '/login.php'; ?>" class="nav-icon">
                <i class="fas fa-heart"></i>
                <?php if ($wishlist_count > 0): ?>
                    <span class="nav-icon-badge wishlist-badge"><?php echo $wishlist_count; ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Cart -->
            <a href="<?php echo SITE_URL; ?>/cart.php" class="nav-icon cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="nav-icon-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    <div class="mobile-menu" id="mobileMenu">
        <button class="mobile-menu-close" id="closeMobileMenu">
            <i class="fas fa-times"></i>
        </button>
        <div class="mobile-menu-content">
            <ul class="mobile-nav-links">
                <li><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li>
                    <a href="#" class="mobile-dropdown-toggle">Products <i class="fas fa-chevron-down"></i></a>
                    <ul class="mobile-dropdown">
                        <?php foreach ($main_categories as $category): ?>
                            <li><a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="<?php echo SITE_URL; ?>/products.php">View All Products</a></li>
                    </ul>
                </li>
                <li><a href="<?php echo SITE_URL; ?>/about.php">About</a></li>
                <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                <li><a href="<?php echo SITE_URL; ?>/blog.php">Blog</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/login.php">Login</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Flash Messages -->
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
<?php endif; ?>
