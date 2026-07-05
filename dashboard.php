<?php
/**
 * Naeem Electronic - User Dashboard
 * User account management and order tracking
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=dashboard.php');
}

$page_title = 'My Dashboard';
$page_description = 'Manage your account and orders';
$current_page = 'dashboard';

$db = new Database();
$user_id = $_SESSION['user_id'];

// Get current tab
$current_tab = $_GET['tab'] ?? 'overview';

// Get user info
$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $user_id);
$user = $db->fetch();

// Get user's addresses
$db->query("SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_default DESC");
$db->bind(':user_id', $user_id);
$addresses = $db->fetchAll();

// Get user's orders
$db->query("SELECT o.*, COUNT(oi.id) as item_count 
           FROM orders o 
           LEFT JOIN order_items oi ON o.id = oi.order_id
           WHERE o.user_id = :user_id 
           GROUP BY o.id 
           ORDER BY o.created_at DESC");
$db->bind(':user_id', $user_id);
$orders = $db->fetchAll();

// Get user's wishlist
$db->query("SELECT w.id as wishlist_id, p.*, pi.image_path, c.name as category_name
           FROM wishlist w
           JOIN products p ON w.product_id = p.id
           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
           LEFT JOIN categories c ON p.category_id = c.id
           WHERE w.user_id = :user_id AND p.is_active = 1
           ORDER BY w.created_at DESC");
$db->bind(':user_id', $user_id);
$wishlist = $db->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">My Dashboard</h1>
        <p class="mb-0">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
    </div>
</section>

<!-- Dashboard Section -->
<section class="dashboard-section py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="dashboard-sidebar">
                    <div class="user-info text-center mb-4">
                        <div class="user-avatar mb-3">
                            <i class="fas fa-user-circle fa-4x"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    
                    <nav class="dashboard-nav">
                        <a href="dashboard.php?tab=overview" class="nav-item <?php echo $current_tab === 'overview' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Overview
                        </a>
                        <a href="dashboard.php?tab=orders" class="nav-item <?php echo $current_tab === 'orders' ? 'active' : ''; ?>">
                            <i class="fas fa-box"></i> My Orders
                            <?php if (count($orders) > 0): ?>
                                <span class="badge bg-secondary"><?php echo count($orders); ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="dashboard.php?tab=wishlist" class="nav-item <?php echo $current_tab === 'wishlist' ? 'active' : ''; ?>">
                            <i class="fas fa-heart"></i> Wishlist
                            <?php if (count($wishlist) > 0): ?>
                                <span class="badge bg-secondary"><?php echo count($wishlist); ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="dashboard.php?tab=addresses" class="nav-item <?php echo $current_tab === 'addresses' ? 'active' : ''; ?>">
                            <i class="fas fa-map-marker-alt"></i> Addresses
                        </a>
                        <a href="dashboard.php?tab=settings" class="nav-item <?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a href="logout.php" class="nav-item text-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <?php switch ($current_tab): 
                    case 'orders': ?>
                        <!-- Orders Tab -->
                        <div class="dashboard-content">
                            <h3 class="mb-4">My Orders</h3>
                            <?php if ($orders): ?>
                                <div class="orders-list">
                                    <?php foreach ($orders as $order): ?>
                                        <div class="order-card">
                                            <div class="order-header">
                                                <div>
                                                    <h6>Order #<?php echo $order['order_number']; ?></h6>
                                                    <small class="text-muted"><?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></small>
                                                </div>
                                                <span class="badge <?php 
                                                    $status_colors = [
                                                        'pending' => 'bg-warning',
                                                        'processing' => 'bg-info',
                                                        'shipped' => 'bg-primary',
                                                        'delivered' => 'bg-success',
                                                        'cancelled' => 'bg-danger',
                                                        'returned' => 'bg-secondary'
                                                    ];
                                                    echo $status_colors[$order['order_status']] ?? 'bg-secondary';
                                                ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </div>
                                            <div class="order-body">
                                                <div class="order-info">
                                                    <p><strong>Items:</strong> <?php echo $order['item_count']; ?></p>
                                                    <p><strong>Total:</strong> <?php echo formatPrice($order['final_amount']); ?></p>
                                                    <p><strong>Payment:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                                                </div>
                                                <div class="order-actions">
                                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        View Details
                                                    </a>
                                                    <?php if ($order['order_status'] === 'pending'): ?>
                                                        <button class="btn btn-outline-danger btn-sm" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                                            Cancel Order
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                    <h4>No orders yet</h4>
                                    <p class="text-muted mb-4">You haven't placed any orders yet.</p>
                                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php break; ?>
                        
                    <?php case 'wishlist': ?>
                        <!-- Wishlist Tab -->
                        <div class="dashboard-content">
                            <h3 class="mb-4">My Wishlist</h3>
                            <?php if ($wishlist): ?>
                                <div class="wishlist-grid">
                                    <?php foreach ($wishlist as $item): 
                                        $item_image = $item['image_path'] ? UPLOADS_PATH . '/' . $item['image_path'] : 'https://via.placeholder.com/200x200?text=Product';
                                        $discount_percentage = 0;
                                        if ($item['discount_price'] && $item['discount_price'] < $item['price']) {
                                            $discount_percentage = calculateDiscount($item['price'], $item['discount_price']);
                                        }
                                    ?>
                                        <div class="wishlist-item">
                                            <div class="wishlist-item-image">
                                                <img src="<?php echo $item_image; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php if ($discount_percentage > 0): ?>
                                                    <span class="discount-badge"><?php echo $discount_percentage; ?>% OFF</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="wishlist-item-info">
                                                <h6>
                                                    <a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                                                </h6>
                                                <p class="text-muted small"><?php echo htmlspecialchars($item['category_name'] ?? ''); ?></p>
                                                <p class="price">
                                                    <?php if ($item['discount_price']): ?>
                                                        <span class="text-decoration-line-through text-muted small"><?php echo formatPrice($item['price']); ?></span>
                                                        <span class="text-danger fw-bold"><?php echo formatPrice($item['discount_price']); ?></span>
                                                    <?php else: ?>
                                                        <span class="fw-bold"><?php echo formatPrice($item['price']); ?></span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="wishlist-item-actions">
                                                <button class="btn btn-primary btn-sm" onclick="moveToCart(<?php echo $item['wishlist_id']; ?>)">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" onclick="removeFromWishlist(<?php echo $item['wishlist_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                                    <h4>Your wishlist is empty</h4>
                                    <p class="text-muted mb-4">Save items you love to your wishlist.</p>
                                    <a href="products.php" class="btn btn-primary">Browse Products</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php break; ?>
                        
                    <?php case 'addresses': ?>
                        <!-- Addresses Tab -->
                        <div class="dashboard-content">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="mb-0">My Addresses</h3>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                    <i class="fas fa-plus"></i> Add New Address
                                </button>
                            </div>
                            <?php if ($addresses): ?>
                                <div class="addresses-grid">
                                    <?php foreach ($addresses as $addr): ?>
                                        <div class="address-card <?php echo $addr['is_default'] ? 'default' : ''; ?>">
                                            <?php if ($addr['is_default']): ?>
                                                <span class="badge bg-primary position-absolute top-0 end-0 m-2">Default</span>
                                            <?php endif; ?>
                                            <h5><?php echo htmlspecialchars($addr['full_name']); ?></h5>
                                            <p class="mb-1"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($addr['phone']); ?></p>
                                            <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($addr['address']); ?></p>
                                            <p class="mb-1"><i class="fas fa-city me-2"></i><?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['province']); ?></p>
                                            <?php if ($addr['postal_code']): ?>
                                                <p class="mb-3"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($addr['postal_code']); ?></p>
                                            <?php endif; ?>
                                            <div class="address-actions">
                                                <?php if (!$addr['is_default']): ?>
                                                    <button class="btn btn-outline-primary btn-sm" onclick="setDefaultAddress(<?php echo $addr['id']; ?>)">
                                                        Set as Default
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-secondary btn-sm">Edit</button>
                                                <button class="btn btn-outline-danger btn-sm" onclick="deleteAddress(<?php echo $addr['id']; ?>)">Delete</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state text-center py-5">
                                    <i class="fas fa-map-marker-alt fa-4x text-muted mb-3"></i>
                                    <h4>No saved addresses</h4>
                                    <p class="text-muted mb-4">Add addresses for faster checkout.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                        Add New Address
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php break; ?>
                        
                    <?php case 'settings': ?>
                        <!-- Settings Tab -->
                        <div class="dashboard-content">
                            <h3 class="mb-4">Account Settings</h3>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Personal Information</h5>
                                </div>
                                <div class="card-body">
                                    <form action="api/update-profile.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" class="form-control" name="full_name" 
                                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone</label>
                                                <input type="tel" class="form-control" name="phone" 
                                                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form action="api/change-password.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" class="form-control" name="current_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" class="form-control" name="new_password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>
                        
                    <?php default: ?>
                        <!-- Overview Tab -->
                        <div class="dashboard-content">
                            <h3 class="mb-4">Dashboard Overview</h3>
                            
                            <div class="stats-grid mb-4">
                                <div class="stat-card">
                                    <div class="stat-icon bg-primary">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h4><?php echo count($orders); ?></h4>
                                        <p>Total Orders</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon bg-danger">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h4><?php echo count($wishlist); ?></h4>
                                        <p>Wishlist Items</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon bg-success">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h4><?php echo count($addresses); ?></h4>
                                        <p>Saved Addresses</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Recent Orders</h5>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($orders): ?>
                                                <?php foreach (array_slice($orders, 0, 3) as $order): ?>
                                                    <div class="order-summary-item">
                                                        <div>
                                                            <strong>#<?php echo $order['order_number']; ?></strong>
                                                            <span class="badge bg-secondary ms-2"><?php echo ucfirst($order['order_status']); ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></small>
                                                            <strong><?php echo formatPrice($order['final_amount']); ?></strong>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <a href="dashboard.php?tab=orders" class="btn btn-outline-primary btn-sm mt-3">View All Orders</a>
                                            <?php else: ?>
                                                <p class="text-muted">No orders yet</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Quick Actions</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <a href="products.php" class="btn btn-outline-primary">
                                                    <i class="fas fa-shopping-cart me-2"></i> Continue Shopping
                                                </a>
                                                <a href="dashboard.php?tab=wishlist" class="btn btn-outline-danger">
                                                    <i class="fas fa-heart me-2"></i> View Wishlist
                                                </a>
                                                <a href="dashboard.php?tab=addresses" class="btn btn-outline-success">
                                                    <i class="fas fa-map-marker-alt me-2"></i> Manage Addresses
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>
                <?php endswitch; ?>
            </div>
        </div>
    </div>
</section>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="api/add-address.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Province</label>
                        <select class="form-select" name="province" required>
                            <option value="">Select Province</option>
                            <option>Punjab</option>
                            <option>Sindh</option>
                            <option>Khyber Pakhtunkhwa</option>
                            <option>Balochistan</option>
                            <option>Islamabad</option>
                            <option>Azad Kashmir</option>
                            <option>Gilgit-Baltistan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Postal Code</label>
                        <input type="text" class="form-control" name="postal_code">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_default" id="defaultAddress">
                        <label class="form-check-label" for="defaultAddress">Set as default address</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Address</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    color: white;
}

.dashboard-sidebar {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
}

.user-avatar {
    font-size: 80px;
    color: #FF6F00;
}

.dashboard-nav {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.dashboard-nav .nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-radius: 6px;
    color: #212121;
    text-decoration: none;
    transition: all 0.3s;
}

.dashboard-nav .nav-item:hover {
    background-color: #f8f9fa;
    color: #FF6F00;
}

.dashboard-nav .nav-item.active {
    background-color: #FF6F00;
    color: white;
}

.dashboard-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 25px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.order-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #dee2e6;
}

.order-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.wishlist-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    position: relative;
}

.wishlist-item-image {
    position: relative;
    margin-bottom: 15px;
}

.wishlist-item-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 4px;
}

.addresses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.address-card {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    position: relative;
}

.address-card.default {
    border-color: #FF6F00;
    background-color: #fff8f0;
}

.order-summary-item {
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.order-summary-item:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .dashboard-sidebar {
        margin-bottom: 20px;
    }
    
    .order-body {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<script>
function moveToCart(wishlistId) {
    const formData = new FormData();
    formData.append('wishlist_id', wishlistId);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/wishlist.php?action=move_to_cart', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Moved to cart!', 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function removeFromWishlist(wishlistId) {
    if (confirm('Remove this item from wishlist?')) {
        const formData = new FormData();
        formData.append('wishlist_id', wishlistId);
        formData.append('csrf_token', getCsrfToken());
        
        fetch('api/wishlist.php?action=remove', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Removed from wishlist', 'success');
                setTimeout(() => location.reload(), 1500);
            }
        });
    }
}

function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        // Implement cancel order API
        showNotification('Order cancellation requested', 'info');
    }
}

function setDefaultAddress(addressId) {
    // Implement set default address API
    showNotification('Default address updated', 'success');
}

function deleteAddress(addressId) {
    if (confirm('Delete this address?')) {
        // Implement delete address API
        showNotification('Address deleted', 'success');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
