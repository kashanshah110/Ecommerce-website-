<?php
/**
 * Naeem Electronic - Admin Orders Management
 * Manage and track orders
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Manage Orders';
$current_page = 'admin';

$db = new Database();
$action = $_GET['action'] ?? 'list';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request');
    } elseif (empty($status)) {
        setFlash('error', 'Please select a status');
    } else {
        $db->query("UPDATE orders SET order_status = :status WHERE id = :id");
        $db->bind(':status', $status);
        $db->bind(':id', $order_id);
        
        if ($db->execute()) {
            // Update delivered_at if status is delivered
            if ($status === 'delivered') {
                $db->query("UPDATE orders SET delivered_at = NOW() WHERE id = :id");
                $db->bind(':id', $order_id);
                $db->execute();
            }
            
            // Update cancelled_at if status is cancelled
            if ($status === 'cancelled') {
                $db->query("UPDATE orders SET cancelled_at = NOW() WHERE id = :id");
                $db->bind(':id', $order_id);
                $db->execute();
            }
            
            setFlash('success', 'Order status updated successfully');
        } else {
            setFlash('error', 'Failed to update order status');
        }
        
        redirect('orders.php');
    }
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status'])) {
    $order_id = (int)$_POST['order_id'];
    $payment_status = $_POST['payment_status'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    $valid_payment_statuses = ['pending', 'paid', 'failed', 'refunded'];

    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request');
    } elseif (empty($payment_status) || !in_array($payment_status, $valid_payment_statuses, true)) {
        setFlash('error', 'Please select a valid payment status');
    } else {
        $db->query("UPDATE orders SET payment_status = :payment_status WHERE id = :id");
        $db->bind(':payment_status', $payment_status);
        $db->bind(':id', $order_id);

        if ($db->execute()) {
            $db->query("UPDATE payments SET status = :status" . ($payment_status === 'paid' ? ", paid_at = NOW()" : ", paid_at = NULL") . " WHERE order_id = :order_id");
            $db->bind(':status', $payment_status);
            $db->bind(':order_id', $order_id);
            $db->execute();

            setFlash('success', 'Payment status updated successfully');
        } else {
            setFlash('error', 'Failed to update payment status');
        }

        redirect('orders.php?action=view&id=' . $order_id);
    }
}

// Get orders list
$status_filter = $_GET['status'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($status_filter) {
    $where .= " AND order_status = :status";
    $params[':status'] = $status_filter;
}

$db->query("SELECT o.*, u.full_name as customer_name, u.email as customer_email, 
           (SELECT COALESCE(SUM(quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) as item_count 
           FROM orders o 
           LEFT JOIN users u ON o.user_id = u.id 
           $where
           ORDER BY o.created_at DESC");
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$orders = $db->fetchAll();

// Get order details if viewing specific order
$order_details = null;
$order_items = [];
if ($action === 'view' && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    
    $db->query("SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone, c.code as coupon_code 
               FROM orders o 
               LEFT JOIN users u ON o.user_id = u.id 
               LEFT JOIN coupons c ON o.coupon_id = c.id 
               WHERE o.id = :id");
    $db->bind(':id', $order_id);
    $order_details = $db->fetch();
    
    if ($order_details) {
        $db->query("SELECT oi.*, p.name as product_name, pi.image_path 
                   FROM order_items oi 
                   LEFT JOIN products p ON oi.product_id = p.id 
                   LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
                   WHERE oi.order_id = :order_id");
        $db->bind(':order_id', $order_id);
        $order_items = $db->fetchAll();

        $db->query('SELECT * FROM payments WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1');
        $db->bind(':order_id', $order_id);
        $payment_details = $db->fetch();
    }
}

$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
?>

<style>
.admin-content {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.order-status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.order-status-badge.pending { background: #fff3cd; color: #856404; }
.order-status-badge.processing { background: #d1ecf1; color: #0c5460; }
.order-status-badge.shipped { background: #cce5ff; color: #004085; }
.order-status-badge.delivered { background: #d4edda; color: #155724; }
.order-status-badge.cancelled { background: #f8d7da; color: #721c24; }
.order-status-badge.returned { background: #e2e3e5; color: #383d41; }

.payment-status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
}

.payment-status-badge.paid { background: #d4edda; color: #155724; }
.payment-status-badge.pending { background: #fff3cd; color: #856404; }
.payment-status-badge.failed { background: #f8d7da; color: #721c24; }

.order-timeline {
    position: relative;
    padding-left: 30px;
}

.order-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #dee2e6;
    border: 2px solid white;
}

.timeline-item.completed::before {
    background: #4CAF50;
}

.timeline-item.current::before {
    background: #FF6F00;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
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
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box me-2"></i> Products
                    </a>
                    <a class="nav-link active" href="orders.php">
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
                <?php if ($action === 'list'): ?>
                    <div class="admin-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Manage Orders</h2>
                        </div>
                        
                        <!-- Status Filters -->
                        <div class="btn-group mb-4" role="group">
                            <a href="orders.php" class="btn btn-outline-secondary <?php echo !$status_filter ? 'active' : ''; ?>">
                                All Orders
                            </a>
                            <a href="orders.php?status=pending" class="btn btn-outline-secondary <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                                Pending
                            </a>
                            <a href="orders.php?status=processing" class="btn btn-outline-secondary <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">
                                Processing
                            </a>
                            <a href="orders.php?status=shipped" class="btn btn-outline-secondary <?php echo $status_filter === 'shipped' ? 'active' : ''; ?>">
                                Shipped
                            </a>
                            <a href="orders.php?status=delivered" class="btn btn-outline-secondary <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">
                                Delivered
                            </a>
                            <a href="orders.php?status=cancelled" class="btn btn-outline-secondary <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                                Cancelled
                            </a>
                        </div>
                        
                        <!-- Orders Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo $order['order_number']; ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></small>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo $order['item_count'] ?? 0; ?></td>
                                            <td><strong><?php echo formatPrice($order['final_amount']); ?></strong></td>
                                            <td>
                                                <span class="payment-status-badge <?php echo $order['payment_status']; ?>">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="order-status-badge <?php echo $order['order_status']; ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="orders.php?action=view&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'view' && $order_details): ?>
                    <div class="admin-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Order #<?php echo $order_details['order_number']; ?></h2>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Orders
                            </a>
                        </div>
                        
                        <!-- Order Status Timeline -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="order-timeline">
                                    <div class="timeline-item completed">
                                        <strong>Order Placed</strong>
                                        <div class="text-muted small"><?php echo date('M d, Y - h:i A', strtotime($order_details['created_at'])); ?></div>
                                    </div>
                                    <?php if ($order_details['order_status'] !== 'pending'): ?>
                                        <div class="timeline-item completed">
                                            <strong>Processing</strong>
                                            <div class="text-muted small">Order is being processed</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order_details['order_status'] === 'shipped' || $order_details['order_status'] === 'delivered'): ?>
                                        <div class="timeline-item completed">
                                            <strong>Shipped</strong>
                                            <div class="text-muted small">Order has been shipped</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order_details['order_status'] === 'delivered'): ?>
                                        <div class="timeline-item completed">
                                            <strong>Delivered</strong>
                                            <div class="text-muted small"><?php echo date('M d, Y - h:i A', strtotime($order_details['delivered_at'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order_details['order_status'] === 'cancelled'): ?>
                                        <div class="timeline-item completed">
                                            <strong>Cancelled</strong>
                                            <div class="text-muted small"><?php echo date('M d, Y - h:i A', strtotime($order_details['cancelled_at'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Update Status Form -->
                                <form action="orders.php" method="POST" class="mt-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <label class="form-label">Update Status</label>
                                            <select class="form-select" name="status">
                                                <option value="pending" <?php echo $order_details['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $order_details['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order_details['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order_details['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order_details['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex gap-2 flex-wrap">
                                            <button type="submit" name="update_status" class="btn btn-primary">
                                                <i class="fas fa-sync me-2"></i> Update Status
                                            </button>
                                            <?php if ($order_details['order_status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-success" onclick="document.querySelector('select[name=status]').value='processing'; this.closest('form').submit();">
                                                    <i class="fas fa-check me-2"></i> Confirm Order
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>

                                <!-- Update Payment Status Form -->
                                <form action="orders.php" method="POST" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <label class="form-label">Payment Status</label>
                                            <select class="form-select" name="payment_status">
                                                <option value="pending" <?php echo $order_details['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="paid" <?php echo $order_details['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                <option value="failed" <?php echo $order_details['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                <option value="refunded" <?php echo $order_details['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-flex gap-2 flex-wrap">
                                            <button type="submit" name="update_payment_status" class="btn btn-outline-success">
                                                <i class="fas fa-wallet me-2"></i> Update Payment
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Customer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order_details['customer_name'] ?? 'Guest'); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email'] ?? '-'); ?></p>
                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order_details['customer_phone'] ?? '-'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Shipping Address:</strong></p>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Items</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>SKU</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order_items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($item['image_path']): ?>
                                                                <img src="<?php echo UPLOADS_PATH . '/' . $item['image_path']; ?>" 
                                                                     alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 15px;">
                                                            <?php endif; ?>
                                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['product_sku']); ?></td>
                                                    <td><?php echo formatPrice($item['price']); ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td><strong><?php echo formatPrice($item['total']); ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Subtotal:</strong> <?php echo formatPrice($order_details['total_amount']); ?></p>
                                        <?php if ($order_details['discount_amount'] > 0): ?>
                                            <p><strong>Discount:</strong> -<?php echo formatPrice($order_details['discount_amount']); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Shipping:</strong> <?php echo formatPrice($order_details['shipping_amount']); ?></p>
                                        <hr>
                                        <p class="fs-5 mb-0"><strong>Total:</strong> <?php echo formatPrice($order_details['final_amount']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Payment Method:</strong> <?php echo ucfirst($order_details['payment_method']); ?></p>
                                        <p><strong>Payment Status:</strong> 
                                            <span class="payment-status-badge <?php echo $order_details['payment_status']; ?>">
                                                <?php echo ucfirst($order_details['payment_status']); ?>
                                            </span>
                                        </p>
                                        <?php if ($order_details['coupon_id']): ?>
                                            <p><strong>Coupon Applied:</strong> <?php echo htmlspecialchars($order_details['coupon_code'] ?? 'Yes'); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($payment_details)): ?>
                                            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($payment_details['transaction_id'] ?? '-'); ?></p>
                                        <?php endif; ?>
                                        <p><strong>Estimated Delivery:</strong> 
                                            <?php echo $order_details['estimated_delivery'] ? date('M d, Y', strtotime($order_details['estimated_delivery'])) : '3-5 business days'; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success" onclick="window.print()">
                                        <i class="fas fa-print me-2"></i> Print Invoice
                                    </button>
                                    <a href="mailto:<?php echo $order_details['customer_email'] ?? ''; ?>" class="btn btn-primary">
                                        <i class="fas fa-envelope me-2"></i> Email Customer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($action === 'view'): ?>
                    <div class="admin-content">
                        <div class="alert alert-warning">
                            <strong>Order not found.</strong> The requested order does not exist or the order ID is invalid.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
