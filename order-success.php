<?php
/**
 * Naeem Electronic - Order Success Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=order-success.php');
}

$page_title = 'Order Success';
$page_description = 'Your order has been placed successfully.';
$current_page = 'order-success';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    redirect('dashboard.php?tab=orders');
}

$db = new Database();
$db->query('SELECT * FROM orders WHERE id = :id AND user_id = :user_id');
$db->bind(':id', $order_id);
$db->bind(':user_id', $_SESSION['user_id']);
$order = $db->fetch();

if (!$order) {
    redirect('dashboard.php?tab=orders');
}

$db->query('SELECT * FROM order_items WHERE order_id = :order_id');
$db->bind(':order_id', $order_id);
$order_items = $db->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">Thank You!</h1>
        <p class="mb-0">Your order has been placed successfully.</p>
    </div>
</section>

<section class="order-success-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-check-circle fa-4x text-success"></i>
                            <h2 class="mt-3">Order Placed Successfully</h2>
                            <p class="text-muted">Your order number is <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>.</p>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <h6>Order Status</h6>
                                <p><?php echo ucfirst($order['order_status']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <h6>Payment Status</h6>
                                <p><?php echo ucfirst($order['payment_status']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <h6>Total Paid</h6>
                                <p><?php echo formatPrice($order['final_amount']); ?></p>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['product_sku']); ?></td>
                                            <td><?php echo (int)$item['quantity']; ?></td>
                                            <td><?php echo formatPrice($item['price']); ?></td>
                                            <td><?php echo formatPrice($item['total']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <a href="dashboard.php?tab=orders" class="btn btn-outline-primary">View My Orders</a>
                            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.order-success-section .card {
    border: none;
}
.order-success-section h2 {
    color: var(--primary-color);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
