<?php
/**
 * Naeem Electronic - Admin Users Management
 * Manage users and their roles
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$page_title = 'Manage Users';
$current_page = 'admin';

$db = new Database();
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request');
        redirect('users.php');
    }
    
    if ($action === 'edit') {
        $user_id = (int)$_POST['user_id'];
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($full_name) || empty($email)) {
            setFlash('error', 'Please fill in all required fields');
        } else {
            // Check if email already exists for another user
            $db->query("SELECT id FROM users WHERE email = :email AND id != :id");
            $db->bind(':email', $email);
            $db->bind(':id', $user_id);
            if ($db->fetch()) {
                setFlash('error', 'Email already exists');
            } else {
                $db->query("UPDATE users SET full_name = :full_name, email = :email, phone = :phone, role = :role, is_active = :is_active WHERE id = :id");
                $db->bind(':full_name', $full_name);
                $db->bind(':email', $email);
                $db->bind(':phone', $phone);
                $db->bind(':role', $role);
                $db->bind(':is_active', $is_active);
                $db->bind(':id', $user_id);
                
                if ($db->execute()) {
                    setFlash('success', 'User updated successfully');
                    redirect('users.php');
                } else {
                    setFlash('error', 'Failed to update user');
                }
            }
        }
    }
}

// Handle status toggle
if ($action === 'toggle_status' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    $db->query("UPDATE users SET is_active = NOT is_active WHERE id = :id");
    $db->bind(':id', $user_id);
    $db->execute();
    
    setFlash('success', 'User status updated');
    redirect('users.php');
}

// Get users list
$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (full_name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

$db->query("SELECT * FROM users $where ORDER BY created_at DESC");
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$users = $db->fetchAll();

// Get user data for edit
$user = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $db->query("SELECT * FROM users WHERE id = :id");
    $db->bind(':id', $user_id);
    $user = $db->fetch();
    
    if (!$user) {
        setFlash('error', 'User not found');
        redirect('users.php');
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

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.role-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.role-badge.admin { background: #d4edda; color: #155724; }
.role-badge.customer { background: #d1ecf1; color: #0c5460; }
.role-badge.staff { background: #fff3cd; color: #856404; }

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
}

.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.inactive { background: #f8d7da; color: #721c24; }
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
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-bag me-2"></i> Orders
                    </a>
                    <a class="nav-link active" href="users.php">
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
                            <h2>Manage Users</h2>
                        </div>
                        
                        <!-- Search -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form action="users.php" method="GET">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-secondary">Search</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $usr): 
                                        $initials = strtoupper(substr($usr['full_name'], 0, 1));
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3"><?php echo $initials; ?></div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($usr['full_name']); ?></strong>
                                                        <?php if ($usr['id'] === $_SESSION['user_id']): ?>
                                                            <span class="badge bg-primary ms-1">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                            <td><?php echo htmlspecialchars($usr['phone'] ?? '-'); ?></td>
                                            <td>
                                                <span class="role-badge <?php echo $usr['role']; ?>">
                                                    <?php echo ucfirst($usr['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $usr['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $usr['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($usr['created_at'])); ?></td>
                                            <td><?php echo $usr['last_login'] ? date('M d, Y', strtotime($usr['last_login'])) : 'Never'; ?></td>
                                            <td>
                                                <a href="users.php?action=edit&id=<?php echo $usr['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($usr['id'] !== $_SESSION['user_id']): ?>
                                                    <a href="users.php?action=toggle_status&id=<?php echo $usr['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-power-off"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'edit' && $user): ?>
                    <div class="admin-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Edit User</h2>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Users
                            </a>
                        </div>
                        
                        <form action="users.php?action=edit" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" required
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role">
                                        <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Joined</label>
                                    <input type="text" class="form-control" value="<?php echo date('M d, Y - h:i A', strtotime($user['created_at'])); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <a href="users.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Update User
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
