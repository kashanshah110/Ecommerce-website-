<?php
/**
 * Naeem Electronic - Admin Categories Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Manage Categories';
$current_page = 'admin';
$db = new Database();
$action = $_GET['action'] ?? 'list';
$category = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request.');
        redirect('categories.php');
    }

    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        setFlash('error', 'Category name is required.');
        redirect('categories.php?action=' . ($action === 'edit' ? 'edit&id=' . (int)($_POST['category_id'] ?? 0) : 'add'));
    }

    $slug = generateSlug($name);

    if ($action === 'add') {
        $db->query("INSERT INTO categories (name, slug, description, parent_id, sort_order, is_active) VALUES (:name, :slug, :description, :parent_id, :sort_order, :is_active)");
    } else {
        $category_id = (int)($_POST['category_id'] ?? 0);
        $db->query("UPDATE categories SET name = :name, slug = :slug, description = :description, parent_id = :parent_id, sort_order = :sort_order, is_active = :is_active WHERE id = :id");
        $db->bind(':id', $category_id);
    }

    $db->bind(':name', $name);
    $db->bind(':slug', $slug);
    $db->bind(':description', $description);
    $db->bind(':parent_id', $parent_id);
    $db->bind(':sort_order', $sort_order);
    $db->bind(':is_active', $is_active);

    if ($db->execute()) {
        setFlash('success', $action === 'add' ? 'Category added successfully.' : 'Category updated successfully.');
        redirect('categories.php');
    }

    setFlash('error', 'Failed to save category.');
    redirect('categories.php');
}

if ($action === 'delete' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    $db->query("UPDATE categories SET is_active = 0 WHERE id = :id");
    $db->bind(':id', $category_id);
    $db->execute();
    setFlash('success', 'Category disabled successfully.');
    redirect('categories.php');
}

if ($action === 'toggle_status' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    $db->query("UPDATE categories SET is_active = NOT is_active WHERE id = :id");
    $db->bind(':id', $category_id);
    $db->execute();
    setFlash('success', 'Category status updated.');
    redirect('categories.php');
}

if ($action === 'edit' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    $db->query("SELECT * FROM categories WHERE id = :id");
    $db->bind(':id', $category_id);
    $category = $db->fetch();
    if (!$category) {
        setFlash('error', 'Category not found.');
        redirect('categories.php');
    }
}

$db->query("SELECT c.*, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.id ORDER BY c.sort_order, c.name");
$categories = $db->fetchAll();
$db->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order, name");
$parent_categories = $db->fetchAll();

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
                    <a class="nav-link active" href="categories.php"><i class="fas fa-folder me-2"></i> Categories</a>
                    <a class="nav-link" href="coupons.php"><i class="fas fa-tags me-2"></i> Coupons</a>
                    <a class="nav-link" href="banners.php"><i class="fas fa-images me-2"></i> Banners</a>
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i> Reports</a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>
                </nav>
            </div>
            <div class="col-md-10 p-4">
                <div class="admin-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Manage Categories</h2>
                            <p class="text-muted mb-0">Create and manage product categories for your store.</p>
                        </div>
                        <a href="categories.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Add Category
                        </a>
                    </div>

                    <?php if ($action === 'add' || $action === 'edit'): ?>
                        <form action="categories.php?action=<?php echo $action; ?>" method="POST" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="category_id" value="<?php echo (int)$category['id']; ?>">
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name *</label>
                                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parent Category</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">None</option>
                                        <?php foreach ($parent_categories as $parent): ?>
                                            <?php if (isset($category['id']) && $parent['id'] === $category['id']) continue; ?>
                                            <option value="<?php echo $parent['id']; ?>" <?php echo isset($category['parent_id']) && $category['parent_id'] == $parent['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($parent['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control" value="<?php echo htmlspecialchars($category['sort_order'] ?? 0); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo isset($category['is_active']) ? ($category['is_active'] ? 'checked' : '') : 'checked'; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <a href="categories.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Create Category' : 'Update Category'; ?></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Parent</th>
                                        <th>Products</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr><td colspan="6" class="text-center">No categories found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $item): ?>
                                            <tr>
                                                <td><?php echo $item['id']; ?></td>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['parent_name'] ?? 'Main'); ?></td>
                                                <td>--</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $item['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="categories.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-edit"></i></a>
                                                    <a href="categories.php?action=toggle_status&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-power-off"></i></a>
                                                    <a href="categories.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Disable this category?');"><i class="fas fa-trash-alt"></i></a>
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
