<?php
/**
 * Naeem Electronic - Admin Dashboard
 * Admin panel for managing the e-commerce system
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Admin Dashboard';
$current_page = 'admin';

$db = new Database();

// Get dashboard statistics
// Total sales
$db->query("SELECT SUM(final_amount) as total_sales FROM orders WHERE payment_status = 'paid'");
$sales_result = $db->fetch();
$total_sales = $sales_result['total_sales'] ?? 0;

// Total orders
$db->query("SELECT COUNT(*) as total_orders FROM orders");
$orders_result = $db->fetch();
$total_orders = $orders_result['total_orders'] ?? 0;

// Total products
$db->query("SELECT COUNT(*) as total_products FROM products WHERE is_active = 1");
$products_result = $db->fetch();
$total_products = $products_result['total_products'] ?? 0;

// Total users
$db->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'customer'");
$users_result = $db->fetch();
$total_users = $users_result['total_users'] ?? 0;

// Pending orders
$db->query("SELECT COUNT(*) as pending_orders FROM orders WHERE order_status = 'pending'");
$pending_result = $db->fetch();
$pending_orders = $pending_result['pending_orders'] ?? 0;

// Low stock products
$db->query("SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity < 10 AND is_active = 1");
$low_stock_result = $db->fetch();
$low_stock = $low_stock_result['low_stock'] ?? 0;

// Recent orders
$db->query("SELECT o.*, u.full_name as customer_name 
           FROM orders o 
           LEFT JOIN users u ON o.user_id = u.id 
           ORDER BY o.created_at DESC 
           LIMIT 10");
$recent_orders = $db->fetchAll();

// Popular products
$db->query("SELECT p.*, COUNT(oi.id) as sold_count 
           FROM products p 
           LEFT JOIN order_items oi ON p.id = oi.product_id
           WHERE p.is_active = 1
           GROUP BY p.id 
           ORDER BY sold_count DESC 
           LIMIT 5");
$popular_products = $db->fetchAll();

// Monthly sales data
$db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(final_amount) as sales 
           FROM orders 
           WHERE payment_status = 'paid' 
           AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
           GROUP BY month 
           ORDER BY month ASC");
$monthly_sales = $db->fetchAll();

$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
?>

<!-- Admin Dashboard Styles -->
<style>
.admin-dashboard {
    background-color: #f5f5f5;
    min-height: 100vh;
}

.admin-sidebar {
    background: linear-gradient(135deg, #1A237E, #0D47A1);
    min-height: calc(100vh - 60px);
    padding: 20px 0;
}

.admin-sidebar .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 12px 20px;
    margin: 5px 10px;
    border-radius: 6px;
    transition: all 0.3s;
}

.admin-sidebar .nav-link:hover,
.admin-sidebar .nav-link.active {
    background: rgba(255,255,255,0.1);
    color: white;
}

.admin-sidebar .nav-link i {
    width: 20px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-icon.sales { background: linear-gradient(135deg, #4CAF50, #45a049); }
.stat-icon.orders { background: linear-gradient(135deg, #2196F3, #1976D2); }
.stat-icon.products { background: linear-gradient(135deg, #FF9800, #F57C00); }
.stat-icon.users { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }

.recent-orders-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.recent-orders-table .table {
    margin-bottom: 0;
}

.recent-orders-table .table th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.order-status {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.order-status.pending { background: #fff3cd; color: #856404; }
.order-status.processing { background: #d1ecf1; color: #0c5460; }
.order-status.shipped { background: #cce5ff; color: #004085; }
.order-status.delivered { background: #d4edda; color: #155724; }
.order-status.cancelled { background: #f8d7da; color: #721c24; }

.alert-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #FF6F00;
}
</style>

<!-- Admin Navigation -->
<nav class="navbar admin-navbar fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-bolt me-2"></i>Naeem Electronic
        </a>
        <span class="navbar-text">
            <span class="badge">Admin Panel</span>
        </span>
        <div class="d-flex align-items-center">
            <span class="user-info me-3">
                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="admin-dashboard">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 admin-sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box me-2"></i> Products
                    </a>
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-bag me-2"></i> Orders
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-folder me-2"></i> Categories
                    </a>
                    <a class="nav-link" href="coupons.php">
                        <i class="fas fa-tags me-2"></i> Coupons
                    </a>
                    <a class="nav-link" href="banners.php">
                        <i class="fas fa-images me-2"></i> Banners
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                    <hr class="border-secondary">
                    <a class="nav-link" href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i> View Site
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard Overview</h2>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Sales</h6>
                                    <h3 class="mb-0"><?php echo formatPrice($total_sales); ?></h3>
                                </div>
                                <div class="stat-icon sales">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Orders</h6>
                                    <h3 class="mb-0"><?php echo $total_orders; ?></h3>
                                </div>
                                <div class="stat-icon orders">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Products</h6>
                                    <h3 class="mb-0"><?php echo $total_products; ?></h3>
                                </div>
                                <div class="stat-icon products">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Users</h6>
                                    <h3 class="mb-0"><?php echo $total_users; ?></h3>
                                </div>
                                <div class="stat-icon users">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alerts -->
                <div class="row mb-4">
                    <?php if ($pending_orders > 0): ?>
                        <div class="col-md-6 mb-3">
                            <div class="alert-card">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle text-warning fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-1"><?php echo $pending_orders; ?> Pending Orders</h6>
                                        <a href="orders.php?status=pending" class="btn btn-sm btn-warning">View Orders</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($low_stock > 0): ?>
                        <div class="col-md-6 mb-3">
                            <div class="alert-card" style="border-left-color: #dc3545;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-box-open text-danger fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-1"><?php echo $low_stock; ?> Products Low Stock</h6>
                                        <a href="products.php?filter=low_stock" class="btn btn-sm btn-danger">View Products</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-lg-8 mb-4">
                        <div class="recent-orders-table">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Recent Orders</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                                                <td><?php echo formatPrice($order['final_amount']); ?></td>
                                                <td>
                                                    <span class="order-status <?php echo $order['order_status']; ?>">
                                                        <?php echo ucfirst($order['order_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Popular Products -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Popular Products</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($popular_products as $product): ?>
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light rounded p-2">
                                                <i class="fas fa-box text-secondary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <small class="text-muted"><?php echo $product['sold_count'] ?? 0; ?> sold</small>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <strong><?php echo formatPrice($product['price']); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Chart -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Monthly Sales (Last 6 Months)</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($monthly_sales): ?>
                                    <div style="height: 300px; display: flex; align-items: flex-end; gap: 20px; padding: 20px;">
                                        <?php 
                                        $max_sales = max(array_column($monthly_sales, 'sales'));
                                        foreach ($monthly_sales as $sale): 
                                            $height = ($sale['sales'] / $max_sales) * 250;
                                            $month = date('M', strtotime($sale['month'] . '-01'));
                                        ?>
                                            <div style="flex: 1; text-align: center;">
                                                <div style="height: <?php echo $height; ?>px; background: linear-gradient(135deg, #1A237E, #FF6F00); border-radius: 4px 4px 0 0;"></div>
                                                <div style="margin-top: 10px;">
                                                    <strong><?php echo $month; ?></strong>
                                                    <br>
                                                    <small><?php echo formatPrice($sale['sales']); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No sales data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
