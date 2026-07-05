<?php
/**
 * Naeem Electronic - Admin Coupons Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Manage Coupons';
$current_page = 'admin';
$db = new Database();
$action = $_GET['action'] ?? 'list';
$coupon = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request.');
        redirect('coupons.php');
    }

    $code = sanitize($_POST['code'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $discount_type = $_POST['discount_type'] ?? 'percentage';
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
    $max_discount_amount = (float)($_POST['max_discount_amount'] ?? 0);
    $usage_limit_per_user = (int)($_POST['usage_limit_per_user'] ?? 1);
    $total_usage_limit = (int)($_POST['total_usage_limit'] ?? 0);
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($code) || $discount_value <= 0) {
        setFlash('error', 'Coupon code and discount value are required.');
        redirect('coupons.php?action=' . ($action === 'edit' ? 'edit&id=' . (int)($_POST['coupon_id'] ?? 0) : 'add'));
    }

    if ($action === 'add') {
        $db->query("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit_per_user, total_usage_limit, start_date, end_date, is_active) VALUES (:code, :description, :discount_type, :discount_value, :min_order_amount, :max_discount_amount, :usage_limit_per_user, :total_usage_limit, :start_date, :end_date, :is_active)");
    } else {
        $coupon_id = (int)($_POST['coupon_id'] ?? 0);
        $db->query("UPDATE coupons SET code = :code, description = :description, discount_type = :discount_type, discount_value = :discount_value, min_order_amount = :min_order_amount, max_discount_amount = :max_discount_amount, usage_limit_per_user = :usage_limit_per_user, total_usage_limit = :total_usage_limit, start_date = :start_date, end_date = :end_date, is_active = :is_active WHERE id = :id");
        $db->bind(':id', $coupon_id);
    }

    $db->bind(':code', $code);
    $db->bind(':description', $description);
    $db->bind(':discount_type', $discount_type);
    $db->bind(':discount_value', $discount_value);
    $db->bind(':min_order_amount', $min_order_amount);
    $db->bind(':max_discount_amount', $max_discount_amount);
    $db->bind(':usage_limit_per_user', $usage_limit_per_user);
    $db->bind(':total_usage_limit', $total_usage_limit);
    $db->bind(':start_date', $start_date);
    $db->bind(':end_date', $end_date);
    $db->bind(':is_active', $is_active);

    if ($db->execute()) {
        setFlash('success', $action === 'add' ? 'Coupon added successfully.' : 'Coupon updated successfully.');
        redirect('coupons.php');
    }

    setFlash('error', 'Failed to save coupon.');
    redirect('coupons.php');
}

if ($action === 'delete' && isset($_GET['id'])) {
    $coupon_id = (int)$_GET['id'];
    $db->query("DELETE FROM coupons WHERE id = :id");
    $db->bind(':id', $coupon_id);
    $db->execute();
    setFlash('success', 'Coupon deleted successfully.');
    redirect('coupons.php');
}

if ($action === 'toggle_status' && isset($_GET['id'])) {
    $coupon_id = (int)$_GET['id'];
    $db->query("UPDATE coupons SET is_active = NOT is_active WHERE id = :id");
    $db->bind(':id', $coupon_id);
    $db->execute();
    setFlash('success', 'Coupon status updated.');
    redirect('coupons.php');
}

if ($action === 'edit' && isset($_GET['id'])) {
    $coupon_id = (int)$_GET['id'];
    $db->query("SELECT * FROM coupons WHERE id = :id");
    $db->bind(':id', $coupon_id);
    $coupon = $db->fetch();
    if (!$coupon) {
        setFlash('error', 'Coupon not found.');
        redirect('coupons.php');
    }
}

$db->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $db->fetchAll();

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
                    <a class="nav-link active" href="coupons.php"><i class="fas fa-tags me-2"></i> Coupons</a>
                    <a class="nav-link" href="banners.php"><i class="fas fa-images me-2"></i> Banners</a>
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>
                </nav>
            </div>
            <div class="col-md-10 p-4">
                <div class="admin-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Manage Coupons</h2>
                            <p class="text-muted mb-0">Create promotional coupons and manage discounts.</p>
                        </div>
                        <a href="coupons.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Add Coupon
                        </a>
                    </div>

                    <?php if ($action === 'add' || $action === 'edit'): ?>
                        <form action="coupons.php?action=<?php echo $action; ?>" method="POST" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="coupon_id" value="<?php echo (int)$coupon['id']; ?>">
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Coupon Code *</label>
                                    <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($coupon['code'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Discount Type</label>
                                    <select name="discount_type" class="form-select">
                                        <option value="percentage" <?php echo isset($coupon['discount_type']) && $coupon['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                                        <option value="fixed" <?php echo isset($coupon['discount_type']) && $coupon['discount_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Discount Value *</label>
                                    <input type="number" name="discount_value" class="form-control" step="0.01" required value="<?php echo htmlspecialchars($coupon['discount_value'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Min Order Amount</label>
                                    <input type="number" name="min_order_amount" class="form-control" step="0.01" value="<?php echo htmlspecialchars($coupon['min_order_amount'] ?? 0); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Max Discount Amount</label>
                                    <input type="number" name="max_discount_amount" class="form-control" step="0.01" value="<?php echo htmlspecialchars($coupon['max_discount_amount'] ?? 0); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Usage Limit Per User</label>
                                    <input type="number" name="usage_limit_per_user" class="form-control" value="<?php echo htmlspecialchars($coupon['usage_limit_per_user'] ?? 1); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Total Usage Limit</label>
                                    <input type="number" name="total_usage_limit" class="form-control" value="<?php echo htmlspecialchars($coupon['total_usage_limit'] ?? 0); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Start Date</label>
                                    <input type="datetime-local" name="start_date" class="form-control" value="<?php echo isset($coupon['start_date']) ? date('Y-m-d\TH:i', strtotime($coupon['start_date'])) : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">End Date</label>
                                    <input type="datetime-local" name="end_date" class="form-control" value="<?php echo isset($coupon['end_date']) ? date('Y-m-d\TH:i', strtotime($coupon['end_date'])) : ''; ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($coupon['description'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo isset($coupon['is_active']) ? ($coupon['is_active'] ? 'checked' : '') : 'checked'; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <a href="coupons.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Create Coupon' : 'Update Coupon'; ?></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Value</th>
                                        <th>Status</th>
                                        <th>Valid Until</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($coupons)): ?>
                                        <tr><td colspan="7" class="text-center">No coupons found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($coupons as $item): ?>
                                            <tr>
                                                <td><?php echo $item['id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['code']); ?></td>
                                                <td><?php echo ucfirst($item['discount_type']); ?></td>
                                                <td><?php echo $item['discount_type'] === 'percentage' ? htmlspecialchars($item['discount_value']) . '%' : formatPrice($item['discount_value']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $item['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $item['end_date'] ? date('Y-m-d', strtotime($item['end_date'])) : 'Unlimited'; ?></td>
                                                <td>
                                                    <a href="coupons.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                                                    <a href="coupons.php?action=toggle_status&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-power-off"></i></a>
                                                    <a href="coupons.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this coupon?');"><i class="fas fa-trash-alt"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
