<?php
/**
 * Naeem Electronic - Admin Products Management
 * CRUD operations for products
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Manage Products';
$current_page = 'admin';

$db = new Database();
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request');
        redirect('products.php');
    }
    
    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name'] ?? '');
        $sku = sanitize($_POST['sku'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $brand_id = (int)($_POST['brand_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $stock_status = $_POST['stock_status'] ?? 'in_stock';
        $short_description = sanitize($_POST['short_description'] ?? '');
        $description = $_POST['description'] ?? '';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_new = isset($_POST['is_new']) ? 1 : 0;
        $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
        $warranty = sanitize($_POST['warranty'] ?? '');
        $productImage = $_FILES['product_image'] ?? null;
        $productImageUpload = $productImage && $productImage['error'] !== UPLOAD_ERR_NO_FILE;
        
        if (empty($name) || empty($sku) || $category_id === 0 || $price === 0 || ($action === 'add' && !$productImageUpload)) {
            setFlash('error', 'Please fill in all required fields');
            $product = $_POST;
        } else {
            $slug = generateSlug($name);
            
            // Check if SKU already exists
            $db->query("SELECT id FROM products WHERE sku = :sku");
            $db->bind(':sku', $sku);
            if ($db->fetch() && $action === 'add') {
                setFlash('error', 'SKU already exists');
            } else {
                if ($action === 'add') {
                    $db->query("INSERT INTO products (name, slug, sku, category_id, brand_id, price, discount_price, stock_quantity, stock_status, short_description, description, is_featured, is_new, is_best_seller, warranty) 
                               VALUES (:name, :slug, :sku, :category_id, :brand_id, :price, :discount_price, :stock_quantity, :stock_status, :short_description, :description, :is_featured, :is_new, :is_best_seller, :warranty)");
                    $db->bind(':name', $name);
                    $db->bind(':slug', $slug);
                    $db->bind(':sku', $sku);
                    $db->bind(':category_id', $category_id);
                    $db->bind(':brand_id', $brand_id ?: null);
                    $db->bind(':price', $price);
                    $db->bind(':discount_price', $discount_price);
                    $db->bind(':stock_quantity', $stock_quantity);
                    $db->bind(':stock_status', $stock_status);
                    $db->bind(':short_description', $short_description);
                    $db->bind(':description', $description);
                    $db->bind(':is_featured', $is_featured);
                    $db->bind(':is_new', $is_new);
                    $db->bind(':is_best_seller', $is_best_seller);
                    $db->bind(':warranty', $warranty);
                    
                    if ($db->execute()) {
                        $product_id = $db->lastInsertId();
                        if ($productImageUpload) {
                            $uploadResult = uploadImage($productImage, 'products');
                            if ($uploadResult['success']) {
                                $db->query("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (:product_id, :image_path, 1)");
                                $db->bind(':product_id', $product_id);
                                $db->bind(':image_path', $uploadResult['path']);
                                $db->execute();
                            } else {
                                setFlash('error', 'Product added but image upload failed: ' . $uploadResult['message']);
                                redirect('products.php?action=edit&id=' . $product_id);
                            }
                        }
                        setFlash('success', 'Product added successfully');
                        redirect('products.php?action=edit&id=' . $product_id);
                    } else {
                        setFlash('error', 'Failed to add product');
                    }
                } else {
                    $product_id = (int)$_POST['product_id'];
                    
                    $db->query("UPDATE products SET name = :name, slug = :slug, sku = :sku, category_id = :category_id, brand_id = :brand_id, price = :price, discount_price = :discount_price, stock_quantity = :stock_quantity, stock_status = :stock_status, short_description = :short_description, description = :description, is_featured = :is_featured, is_new = :is_new, is_best_seller = :is_best_seller, warranty = :warranty WHERE id = :id");
                    $db->bind(':name', $name);
                    $db->bind(':slug', $slug);
                    $db->bind(':sku', $sku);
                    $db->bind(':category_id', $category_id);
                    $db->bind(':brand_id', $brand_id ?: null);
                    $db->bind(':price', $price);
                    $db->bind(':discount_price', $discount_price);
                    $db->bind(':stock_quantity', $stock_quantity);
                    $db->bind(':stock_status', $stock_status);
                    $db->bind(':short_description', $short_description);
                    $db->bind(':description', $description);
                    $db->bind(':is_featured', $is_featured);
                    $db->bind(':is_new', $is_new);
                    $db->bind(':is_best_seller', $is_best_seller);
                    $db->bind(':warranty', $warranty);
                    $db->bind(':id', $product_id);
                    
                    if ($db->execute()) {
                        if ($productImageUpload) {
                            $uploadResult = uploadImage($productImage, 'products');
                            if ($uploadResult['success']) {
                                $db->query("UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id");
                                $db->bind(':product_id', $product_id);
                                $db->execute();

                                $db->query("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (:product_id, :image_path, 1)");
                                $db->bind(':product_id', $product_id);
                                $db->bind(':image_path', $uploadResult['path']);
                                $db->execute();
                            } else {
                                setFlash('error', 'Product updated but image upload failed: ' . $uploadResult['message']);
                                redirect('products.php?action=edit&id=' . $product_id);
                            }
                        }
                        setFlash('success', 'Product updated successfully');
                        redirect('products.php');
                    } else {
                        setFlash('error', 'Failed to update product');
                    }
                }
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    $db->query("UPDATE products SET is_active = 0 WHERE id = :id");
    $db->bind(':id', $product_id);
    $db->execute();
    
    setFlash('success', 'Product deleted successfully');
    redirect('products.php');
}

// Get categories for dropdown
$db->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order");
$categories = $db->fetchAll();

// Get brands for dropdown
$db->query("SELECT * FROM brand WHERE is_active = 1 ORDER BY name");
$brands = $db->fetchAll();

// Get product data for edit
$product = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $db->query("SELECT * FROM products WHERE id = :id");
    $db->bind(':id', $product_id);
    $product = $db->fetch();
    
    if (!$product) {
        setFlash('error', 'Product not found');
        redirect('products.php');
    }

    $db->query("SELECT image_path FROM product_images WHERE product_id = :product_id AND is_primary = 1 LIMIT 1");
    $db->bind(':product_id', $product_id);
    $image = $db->fetch();
    $product['image_path'] = $image['image_path'] ?? null;
}

// Get products list for display
if ($action === 'list') {
    $filter = $_GET['filter'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $where = "WHERE p.is_active = 1";
    $params = [];
    
    if ($filter === 'low_stock') {
        $where .= " AND p.stock_quantity < 10";
    }
    
    if ($search) {
        $where .= " AND (p.name LIKE :search OR p.sku LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $db->query("SELECT p.*, c.name as category_name, b.name as brand_name, pi.image_path 
               FROM products p 
               LEFT JOIN categories c ON p.category_id = c.id
               LEFT JOIN brand b ON p.brand_id = b.id
               LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
               $where
               ORDER BY p.created_at DESC");
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $products = $db->fetchAll();
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

.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-section h6 {
    color: #FF6F00;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #FF6F00;
}

.products-table .table img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

.badge-stock {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
}

.badge-stock.in-stock { background: #d4edda; color: #155724; }
.badge-stock.low-stock { background: #fff3cd; color: #856404; }
.badge-stock.out-of-stock { background: #f8d7da; color: #721c24; }
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
                    <a class="nav-link active" href="products.php">
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
                <?php if ($action === 'list'): ?>
                    <div class="admin-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Manage Products</h2>
                            <a href="products.php?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Add Product
                            </a>
                        </div>
                        
                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <form action="products.php" method="GET">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-secondary">Search</button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-8 text-end">
                                <a href="products.php?filter=low_stock" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Low Stock
                                </a>
                                <a href="products.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-filter me-1"></i> All Products
                                </a>
                            </div>
                        </div>
                        
                        <!-- Products Table -->
                        <div class="products-table">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>SKU</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $prod): 
                                            $stock_badge = 'in-stock';
                                            if ($prod['stock_quantity'] < 10) $stock_badge = 'low-stock';
                                            if ($prod['stock_quantity'] == 0) $stock_badge = 'out-of-stock';
                                            
                                            $prod_image = $prod['image_path'] ? UPLOADS_PATH . '/' . $prod['image_path'] : 'https://via.placeholder.com/50';
                                        ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo $prod_image; ?>" alt="">
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                                                    <?php if ($prod['is_featured']): ?><span class="badge bg-primary ms-1">Featured</span><?php endif; ?>
                                                    <?php if ($prod['is_new']): ?><span class="badge bg-success ms-1">New</span><?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($prod['sku']); ?></td>
                                                <td><?php echo htmlspecialchars($prod['category_name'] ?? '-'); ?></td>
                                                <td>
                                                    <?php if ($prod['discount_price']): ?>
                                                        <span class="text-decoration-line-through text-muted small"><?php echo formatPrice($prod['price']); ?></span>
                                                        <span class="text-danger fw-bold"><?php echo formatPrice($prod['discount_price']); ?></span>
                                                    <?php else: ?>
                                                        <?php echo formatPrice($prod['price']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge-stock <?php echo $stock_badge; ?>">
                                                        <?php echo $prod['stock_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $prod['stock_status'] === 'in_stock' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $prod['stock_status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="../product.php?id=<?php echo $prod['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="products.php?action=delete&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <div class="admin-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?></h2>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Products
                            </a>
                        </div>
                        
                        <form action="products.php?action=<?php echo $action; ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <?php endif; ?>
                            
                            <!-- Basic Information -->
                            <div class="form-section">
                                <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Product Name *</label>
                                        <input type="text" class="form-control" name="name" required
                                               value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SKU *</label>
                                        <input type="text" class="form-control" name="sku" required
                                               value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category *</label>
                                        <select class="form-select" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" <?php echo isset($product['category_id']) && $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Brand</label>
                                        <select class="form-select" name="brand_id">
                                            <option value="">Select Brand</option>
                                            <?php foreach ($brands as $brand): ?>
                                                <option value="<?php echo $brand['id']; ?>" <?php echo isset($product['brand_id']) && $product['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($brand['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pricing & Stock -->
                            <div class="form-section">
                                <h6><i class="fas fa-tags me-2"></i>Pricing & Stock</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Price *</label>
                                        <input type="number" class="form-control" name="price" step="0.01" required
                                               value="<?php echo $product['price'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Discount Price</label>
                                        <input type="number" class="form-control" name="discount_price" step="0.01"
                                               value="<?php echo $product['discount_price'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Stock Quantity</label>
                                        <input type="number" class="form-control" name="stock_quantity"
                                               value="<?php echo $product['stock_quantity'] ?? 0; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Stock Status</label>
                                        <select class="form-select" name="stock_status">
                                            <option value="in_stock" <?php echo isset($product['stock_status']) && $product['stock_status'] === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                            <option value="out_of_stock" <?php echo isset($product['stock_status']) && $product['stock_status'] === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                            <option value="pre_order" <?php echo isset($product['stock_status']) && $product['stock_status'] === 'pre_order' ? 'selected' : ''; ?>>Pre-Order</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Warranty</label>
                                        <input type="text" class="form-control" name="warranty"
                                               value="<?php echo htmlspecialchars($product['warranty'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-section">
                                <h6><i class="fas fa-align-left me-2"></i>Description</h6>
                                <div class="mb-3">
                                    <label class="form-label">Short Description</label>
                                    <textarea class="form-control" name="short_description" rows="2"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Full Description</label>
                                    <textarea class="form-control" name="description" rows="5"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Product Flags -->
                            <div class="form-section">
                                <h6><i class="fas fa-flag me-2"></i>Product Flags</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?php echo isset($product['is_featured']) && $product['is_featured'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_featured">Featured Product</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_new" id="is_new" <?php echo isset($product['is_new']) && $product['is_new'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_new">New Arrival</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_best_seller" id="is_best_seller" <?php echo isset($product['is_best_seller']) && $product['is_best_seller'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_best_seller">Best Seller</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Image -->
                            <div class="form-section">
                                <h6><i class="fas fa-image me-2"></i>Product Image</h6>
                                <div class="row align-items-end">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Primary Image <?php echo $action === 'add' ? '*' : '(optional)'; ?></label>
                                        <input type="file" class="form-control" name="product_image" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                                    </div>
                                    <?php if ($action === 'edit' && !empty($product['image_path'])): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Current Image</label>
                                            <div class="border rounded p-2 text-center">
                                                <img src="<?php echo UPLOADS_PATH . '/' . $product['image_path']; ?>" alt="" class="img-fluid" style="max-height:160px;">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <a href="products.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
