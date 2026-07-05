<?php
/**
 * Naeem Electronic - Admin Banners Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Manage Banners';
$current_page = 'admin';

$db = new Database();
$action = $_GET['action'] ?? 'list';
$banner = null;

function uploadBannerImage($file) {
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'No image uploaded', 'path' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Image upload failed', 'path' => null];
    }

    if ($file['size'] > 5242880) {
        return ['success' => false, 'message' => 'Image size must be 5MB or less', 'path' => null];
    }

    $fileInfo = getimagesize($file['tmp_name']);
    if ($fileInfo === false || !isset($allowedTypes[$fileInfo['mime']])) {
        return ['success' => false, 'message' => 'Invalid image format', 'path' => null];
    }

    $extension = $allowedTypes[$fileInfo['mime']];
    $uploadDir = __DIR__ . '/../uploads/banners/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('banner_', true) . '.' . $extension;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Failed to move uploaded image', 'path' => null];
    }

    return ['success' => true, 'path' => 'banners/' . $filename, 'full_path' => $destination];
}

function deleteBannerImageFile($relativePath) {
    $filePath = __DIR__ . '/../uploads/' . ltrim($relativePath, '/');
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request. Please try again.');
        redirect('banners.php');
    }

    $title = sanitize($_POST['title'] ?? '');
    $link = sanitize($_POST['link'] ?? '');
    $position = $_POST['position'] ?? 'hero';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    if (empty($title)) {
        setFlash('error', 'Banner title is required.');
        redirect('banners.php?action=' . ($action === 'edit' ? 'edit&id=' . (int)($_POST['banner_id'] ?? 0) : 'add'));
    }

    $imagePath = null;
    $uploadedImage = null;
    if (isset($_FILES['image'])) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadedImage = uploadBannerImage($_FILES['image']);
            if (!$uploadedImage['success']) {
                setFlash('error', $uploadedImage['message']);
                redirect('banners.php?action=' . ($action === 'edit' ? 'edit&id=' . (int)($_POST['banner_id'] ?? 0) : 'add'));
            }
            $imagePath = $uploadedImage['path'];
        }
    }

    if ($action === 'add') {
        if (!$imagePath) {
            setFlash('error', 'Banner image is required.');
            redirect('banners.php?action=add');
        }

        $db->query("INSERT INTO banners (title, image, link, position, sort_order, is_active, start_date, end_date) VALUES (:title, :image, :link, :position, :sort_order, :is_active, :start_date, :end_date)");
        $db->bind(':title', $title);
        $db->bind(':image', $imagePath);
        $db->bind(':link', $link);
        $db->bind(':position', $position);
        $db->bind(':sort_order', $sort_order);
        $db->bind(':is_active', $is_active);
        $db->bind(':start_date', $start_date);
        $db->bind(':end_date', $end_date);

        if ($db->execute()) {
            setFlash('success', 'Banner added successfully.');
            redirect('banners.php');
        }

        setFlash('error', 'Failed to add banner.');
        redirect('banners.php?action=add');
    }

    if ($action === 'edit') {
        $banner_id = (int)($_POST['banner_id'] ?? 0);
        $db->query("SELECT * FROM banners WHERE id = :id");
        $db->bind(':id', $banner_id);
        $banner = $db->fetch();

        if (!$banner) {
            setFlash('error', 'Banner not found.');
            redirect('banners.php');
        }

        if ($imagePath) {
            deleteBannerImageFile($banner['image']);
            $banner['image'] = $imagePath;
        }

        $db->query("UPDATE banners SET title = :title, image = :image, link = :link, position = :position, sort_order = :sort_order, is_active = :is_active, start_date = :start_date, end_date = :end_date WHERE id = :id");
        $db->bind(':title', $title);
        $db->bind(':image', $banner['image']);
        $db->bind(':link', $link);
        $db->bind(':position', $position);
        $db->bind(':sort_order', $sort_order);
        $db->bind(':is_active', $is_active);
        $db->bind(':start_date', $start_date);
        $db->bind(':end_date', $end_date);
        $db->bind(':id', $banner_id);

        if ($db->execute()) {
            setFlash('success', 'Banner updated successfully.');
            redirect('banners.php');
        }

        setFlash('error', 'Failed to update banner.');
        redirect('banners.php?action=edit&id=' . $banner_id);
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $banner_id = (int)$_GET['id'];

    $db->query("SELECT image FROM banners WHERE id = :id");
    $db->bind(':id', $banner_id);
    $banner = $db->fetch();

    if ($banner) {
        deleteBannerImageFile($banner['image']);
        $db->query("DELETE FROM banners WHERE id = :id");
        $db->bind(':id', $banner_id);
        $db->execute();
        setFlash('success', 'Banner deleted successfully.');
    } else {
        setFlash('error', 'Banner not found.');
    }

    redirect('banners.php');
}

if ($action === 'edit' && isset($_GET['id'])) {
    $banner_id = (int)$_GET['id'];
    $db->query("SELECT * FROM banners WHERE id = :id");
    $db->bind(':id', $banner_id);
    $banner = $db->fetch();

    if (!$banner) {
        setFlash('error', 'Banner not found.');
        redirect('banners.php');
    }
}

if ($action === 'toggle_status' && isset($_GET['id'])) {
    $banner_id = (int)$_GET['id'];
    $db->query("UPDATE banners SET is_active = NOT is_active WHERE id = :id");
    $db->bind(':id', $banner_id);
    $db->execute();
    setFlash('success', 'Banner status updated.');
    redirect('banners.php');
}

$db->query("SELECT * FROM banners ORDER BY position, sort_order, created_at DESC");
$banners = $db->fetchAll();

$hide_main_nav = true;
include __DIR__ . '/../includes/header.php';
?>

<style>
.admin-content {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
.banner-image-thumb {
    width: 100px;
    height: 56px;
    object-fit: cover;
    border-radius: 6px;
}
.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
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
            <div class="col-md-2 admin-sidebar">
                <nav class="nav flex-column">
                    <a class="nav-link" href="index.php">
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
                    <a class="nav-link active" href="banners.php">
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

            <div class="col-md-10 p-4">
                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <div class="admin-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><?php echo $action === 'add' ? 'Add Banner' : 'Edit Banner'; ?></h2>
                            <a href="banners.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Banners
                            </a>
                        </div>

                        <form action="banners.php?action=<?php echo $action; ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="banner_id" value="<?php echo (int)$banner['id']; ?>">
                            <?php endif; ?>

                            <div class="form-section">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title *</label>
                                        <input type="text" class="form-control" name="title" required value="<?php echo htmlspecialchars($banner['title'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Link</label>
                                        <input type="text" class="form-control" name="link" value="<?php echo htmlspecialchars($banner['link'] ?? ''); ?>" placeholder="https://example.com">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Position</label>
                                        <select class="form-select" name="position">
                                            <?php $positions = ['hero' => 'Hero', 'category' => 'Category', 'sidebar' => 'Sidebar', 'footer' => 'Footer']; ?>
                                            <?php foreach ($positions as $key => $label): ?>
                                                <option value="<?php echo $key; ?>" <?php echo isset($banner['position']) && $banner['position'] === $key ? 'selected' : ($key === 'hero' && !isset($banner['position']) ? 'selected' : ''); ?>><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" name="sort_order" value="<?php echo htmlspecialchars($banner['sort_order'] ?? 0); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Status</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo isset($banner['is_active']) && $banner['is_active'] ? 'checked' : (!isset($banner['is_active']) ? 'checked' : ''); ?>>
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input type="datetime-local" class="form-control" name="start_date" value="<?php echo isset($banner['start_date']) ? date('Y-m-d\TH:i', strtotime($banner['start_date'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End Date</label>
                                        <input type="datetime-local" class="form-control" name="end_date" value="<?php echo isset($banner['end_date']) ? date('Y-m-d\TH:i', strtotime($banner['end_date'])) : ''; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Image <?php echo $action === 'edit' ? '(leave empty to keep current)' : '*'; ?></label>
                                        <input type="file" class="form-control" name="image" <?php echo $action === 'add' ? 'required' : ''; ?> accept="image/*">
                                    </div>
                                    <?php if ($action === 'edit' && !empty($banner['image'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Current Image</label>
                                            <div>
                                                <img src="<?php echo UPLOADS_PATH . '/' . $banner['image']; ?>" class="banner-image-thumb" alt="Current Banner">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="banners.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?php echo $action === 'add' ? 'Add Banner' : 'Update Banner'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="admin-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Manage Banners</h2>
                            <a href="banners.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Add Banner
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Position</th>
                                        <th>Sort</th>
                                        <th>Status</th>
                                        <th>Schedule</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($banners)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No banners found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($banners as $item): ?>
                                            <tr>
                                                <td><?php echo $item['id']; ?></td>
                                                <td>
                                                    <img src="<?php echo !empty($item['image']) ? UPLOADS_PATH . '/' . $item['image'] : 'https://via.placeholder.com/100x56?text=No+Image'; ?>" class="banner-image-thumb" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                                </td>
                                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                                <td><?php echo ucfirst($item['position']); ?></td>
                                                <td><?php echo (int)$item['sort_order']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $item['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($item['start_date'] || $item['end_date']): ?>
                                                        <?php echo $item['start_date'] ? date('Y-m-d', strtotime($item['start_date'])) : '-'; ?>
                                                        to <?php echo $item['end_date'] ? date('Y-m-d', strtotime($item['end_date'])) : '-'; ?>
                                                    <?php else: ?>
                                                        Always
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="banners.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="banners.php?action=toggle_status&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Toggle Status">
                                                        <i class="fas fa-power-off"></i>
                                                    </a>
                                                    <a href="banners.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this banner?');" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
