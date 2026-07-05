<?php
/**
 * Naeem Electronic - Admin Reports
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Reports';
$current_page = 'admin';
$db = new Database();

$db->query("SELECT COUNT(*) as total_orders FROM orders");
$orders_result = $db->fetch();
$total_orders = $orders_result['total_orders'] ?? 0;

$db->query("SELECT SUM(final_amount) as total_sales FROM orders WHERE payment_status = 'paid'");
$sales_result = $db->fetch();
$total_sales = $sales_result['total_sales'] ?? 0;

$db->query("SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'");
$customers_result = $db->fetch();
$total_customers = $customers_result['total_customers'] ?? 0;

$db->query("SELECT COUNT(*) as total_products FROM products WHERE is_active = 1");
$products_result = $db->fetch();
$total_products = $products_result['total_products'] ?? 0;

$db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(final_amount) as sales FROM orders WHERE payment_status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month ASC");
$monthly_sales = $db->fetchAll();

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
                    <a class="nav-link active" href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>
                </nav>
            </div>
            <div class="col-md-10 p-4">
                <div class="admin-content">
                    <h2 class="mb-4">Reports</h2>
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card p-4 h-100">
                                <h6 class="text-muted mb-3">Total Sales</h6>
                                <h3><?php echo formatPrice($total_sales); ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card p-4 h-100">
                                <h6 class="text-muted mb-3">Total Orders</h6>
                                <h3><?php echo $total_orders; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card p-4 h-100">
                                <h6 class="text-muted mb-3">Customers</h6>
                                <h3><?php echo $total_customers; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card p-4 h-100">
                                <h6 class="text-muted mb-3">Active Products</h6>
                                <h3><?php echo $total_products; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($monthly_sales)): ?>
                                    <tr><td colspan="2" class="text-center">No sales data available.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($monthly_sales as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['month']); ?></td>
                                            <td><?php echo formatPrice($row['sales']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
