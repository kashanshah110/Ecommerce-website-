<?php
/**
 * Naeem Electronic - Admin Settings
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Settings';
$current_page = 'admin';
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request.');
        redirect('settings.php');
    }

    $settings = [
        'site_name' => sanitize($_POST['site_name'] ?? SITE_NAME),
        'site_email' => sanitize($_POST['site_email'] ?? SITE_EMAIL),
        'site_phone' => sanitize($_POST['site_phone'] ?? SITE_PHONE),
        'site_address' => sanitize($_POST['site_address'] ?? SITE_ADDRESS),
        'currency' => sanitize($_POST['currency'] ?? CURRENCY),
        'tax_rate' => sanitize($_POST['tax_rate'] ?? 0),
        'shipping_cost' => sanitize($_POST['shipping_cost'] ?? 0),
        'free_shipping_threshold' => sanitize($_POST['free_shipping_threshold'] ?? 0),
        'social_facebook' => sanitize($_POST['social_facebook'] ?? ''),
        'social_instagram' => sanitize($_POST['social_instagram'] ?? ''),
        'social_youtube' => sanitize($_POST['social_youtube'] ?? ''),
        'social_twitter' => sanitize($_POST['social_twitter'] ?? ''),
    ];

    foreach ($settings as $key => $value) {
        updateSetting($key, $value);
    }

    setFlash('success', 'Settings updated successfully.');
    redirect('settings.php');
}

$site_name = getSetting('site_name', SITE_NAME);
$site_email = getSetting('site_email', SITE_EMAIL);
$site_phone = getSetting('site_phone', SITE_PHONE);
$site_address = getSetting('site_address', SITE_ADDRESS);
$currency = getSetting('currency', CURRENCY);
$tax_rate = getSetting('tax_rate', '0');
$shipping_cost = getSetting('shipping_cost', '0');
$free_shipping_threshold = getSetting('free_shipping_threshold', '0');
$social_facebook = getSetting('social_facebook', '');
$social_instagram = getSetting('social_instagram', '');
$social_youtube = getSetting('social_youtube', '');
$social_twitter = getSetting('social_twitter', '');

$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-dashboard">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 admin-sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                    <a class="nav-link" href="products.php"><i class="fas fa-box me-2"></i> Products</a>
                    <a class="nav-link" href="orders.php"><i class="fas fa-shopping-bag me-2"></i> Orders</a>
                    <a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i> Users</a>
                    <a class="nav-link" href="categories.php"><i class="fas fa-folder me-2"></i> Categories</a>
                    <a class="nav-link" href="coupons.php"><i class="fas fa-tags me-2"></i> Coupons</a>
                    <a class="nav-link" href="banners.php"><i class="fas fa-images me-2"></i> Banners</a>
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                    <a class="nav-link active" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>
                </nav>
            </div>
            <div class="col-md-10 p-4">
                <div class="admin-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Settings</h2>
                            <p class="text-muted mb-0">Update your store information and site settings.</p>
                        </div>
                    </div>

                    <form action="settings.php" method="POST" class="admin-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Site Name</label>
                                <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($site_name); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Site Email</label>
                                <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($site_email); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Site Phone</label>
                                <input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($site_phone); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Site Address</label>
                                <input type="text" name="site_address" class="form-control" value="<?php echo htmlspecialchars($site_address); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Currency</label>
                                <input type="text" name="currency" class="form-control" value="<?php echo htmlspecialchars($currency); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax Rate (%)</label>
                                <input type="number" name="tax_rate" class="form-control" step="0.01" value="<?php echo htmlspecialchars($tax_rate); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Shipping Cost</label>
                                <input type="number" name="shipping_cost" class="form-control" step="0.01" value="<?php echo htmlspecialchars($shipping_cost); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Free Shipping Threshold</label>
                                <input type="number" name="free_shipping_threshold" class="form-control" step="0.01" value="<?php echo htmlspecialchars($free_shipping_threshold); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Facebook URL</label>
                                <input type="url" name="social_facebook" class="form-control" value="<?php echo htmlspecialchars($social_facebook); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Instagram URL</label>
                                <input type="url" name="social_instagram" class="form-control" value="<?php echo htmlspecialchars($social_instagram); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">YouTube URL</label>
                                <input type="url" name="social_youtube" class="form-control" value="<?php echo htmlspecialchars($social_youtube); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Twitter URL</label>
                                <input type="url" name="social_twitter" class="form-control" value="<?php echo htmlspecialchars($social_twitter); ?>">
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
