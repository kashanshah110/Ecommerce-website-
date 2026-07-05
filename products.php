<?php
/**
 * Naeem Electronic - Products Listing Page
 * Displays all products with filtering and pagination
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Products';
$page_description = 'Browse our wide range of premium home appliances';
$current_page = 'products';

$db = new Database();

// Get filters from URL
$category_slug = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$sort_by = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Build query
$where = "WHERE p.is_active = 1";
$params = [];

// Category filter
if ($category_slug) {
    $db->query("SELECT id, name FROM categories WHERE slug = :slug");
    $db->bind(':slug', $category_slug);
    $category = $db->fetch();
    if ($category) {
        $where .= " AND p.category_id = :category_id";
        $params[':category_id'] = $category['id'];
        $page_title = htmlspecialchars($category['name'] ?? 'Products') . ' - Products';
    }
}

// Search filter
if ($search) {
    $where .= " AND (p.name LIKE :search OR p.short_description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Price filter
if ($price_min > 0) {
    $where .= " AND p.price >= :price_min";
    $params[':price_min'] = $price_min;
}
if ($price_max > 0) {
    $where .= " AND p.price <= :price_max";
    $params[':price_max'] = $price_max;
}

// Sort order
switch ($sort_by) {
    case 'price_low':
        $order_by = "ORDER BY p.price ASC";
        break;
    case 'price_high':
        $order_by = "ORDER BY p.price DESC";
        break;
    case 'rating':
        $order_by = "ORDER BY p.rating DESC";
        break;
    case 'popular':
        $order_by = "ORDER BY p.views DESC";
        break;
    case 'newest':
    default:
        $order_by = "ORDER BY p.created_at DESC";
        break;
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM products p $where";
$db->query($count_query);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$total_result = $db->fetch();
$total_products = $total_result['total'];

// Pagination
$per_page = PRODUCTS_PER_PAGE;
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;

// Get products
$query = "SELECT p.*, pi.image_path, c.name as category_name, c.slug as category_slug 
          FROM products p 
          LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
          LEFT JOIN categories c ON p.category_id = c.id
          $where
          $order_by
          LIMIT $per_page OFFSET $offset";
$db->query($query);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$products = $db->fetchAll();

// Get all categories for sidebar
$db->query("SELECT c.*, COUNT(p.id) as product_count 
           FROM categories c 
           LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
           WHERE c.parent_id IS NULL AND c.is_active = 1
           GROUP BY c.id
           ORDER BY c.sort_order");
$categories = $db->fetchAll();

// Get price range
$db->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1");
$price_range = $db->fetch();

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2"><?php echo $page_title; ?></h1>
        <p class="mb-0">Browse our wide range of premium home appliances</p>
    </div>
</section>

<!-- Products Section -->
<section class="products-section py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <div class="filter-card mb-4">
                        <h5 class="filter-title">Categories</h5>
                        <ul class="filter-list">
                            <li>
                                <a href="products.php" class="<?php echo !$category_slug ? 'active' : ''; ?>">
                                    All Products
                                </a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="products.php?category=<?php echo $cat['slug']; ?>" 
                                       class="<?php echo $category_slug === $cat['slug'] ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                        <span class="count">(<?php echo $cat['product_count']; ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="filter-card mb-4">
                        <h5 class="filter-title">Price Range</h5>
                        <form action="products.php" method="GET" class="price-filter-form">
                            <?php if ($category_slug): ?>
                                <input type="hidden" name="category" value="<?php echo $category_slug; ?>">
                            <?php endif; ?>
                            <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                            <?php if ($sort_by): ?>
                                <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label>Min Price: Rs. <?php echo number_format($price_min); ?></label>
                                <input type="range" name="price_min" class="form-range" 
                                       min="<?php echo floor($price_range['min_price']); ?>" 
                                       max="<?php echo ceil($price_range['max_price']); ?>" 
                                       value="<?php echo $price_min; ?>" 
                                       id="priceMin" oninput="updatePriceLabel('min', this.value)">
                            </div>
                            <div class="mb-3">
                                <label>Max Price: Rs. <?php echo number_format($price_max); ?></label>
                                <input type="range" name="price_max" class="form-range" 
                                       min="<?php echo floor($price_range['min_price']); ?>" 
                                       max="<?php echo ceil($price_range['max_price']); ?>" 
                                       value="<?php echo $price_max; ?>" 
                                       id="priceMax" oninput="updatePriceLabel('max', this.value)">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                        </form>
                    </div>
                    
                    <div class="filter-card mb-4">
                        <h5 class="filter-title">Sort By</h5>
                        <ul class="filter-list">
                            <li>
                                <a href="<?php echo buildSortUrl('newest'); ?>" class="<?php echo $sort_by === 'newest' ? 'active' : ''; ?>">
                                    Newest First
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo buildSortUrl('price_low'); ?>" class="<?php echo $sort_by === 'price_low' ? 'active' : ''; ?>">
                                    Price: Low to High
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo buildSortUrl('price_high'); ?>" class="<?php echo $sort_by === 'price_high' ? 'active' : ''; ?>">
                                    Price: High to Low
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo buildSortUrl('rating'); ?>" class="<?php echo $sort_by === 'rating' ? 'active' : ''; ?>">
                                    Top Rated
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo buildSortUrl('popular'); ?>" class="<?php echo $sort_by === 'popular' ? 'active' : ''; ?>">
                                    Most Popular
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="results-header d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0">Showing <?php echo $total_products > 0 ? ($offset + 1) : 0; ?> - <?php echo min($offset + $per_page, $total_products); ?> of <?php echo $total_products; ?> products</p>
                    <div class="view-toggle">
                        <button class="btn btn-outline-secondary btn-sm active" id="gridView">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="listView">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
                
                <?php if ($products): ?>
                    <div class="product-grid" id="productsContainer">
                        <?php foreach ($products as $product): 
                            $discount_percentage = 0;
                            if ($product['discount_price'] && $product['discount_price'] < $product['price']) {
                                $discount_percentage = calculateDiscount($product['price'], $product['discount_price']);
                            }
                            
                            $product_image = $product['image_path'] ? UPLOADS_PATH . '/' . $product['image_path'] : 'https://via.placeholder.com/400x400?text=Product';
                        ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php if ($discount_percentage > 0): ?>
                                        <span class="discount-badge"><?php echo $discount_percentage; ?>% OFF</span>
                                    <?php endif; ?>
                                    <?php if ($product['is_new']): ?>
                                        <span class="badge bg-success position-absolute top-0 end-0 m-2">New</span>
                                    <?php endif; ?>
                                    <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <button class="quick-view-btn" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-eye"></i> Quick View
                                    </button>
                                </div>
                                <div class="product-info">
                                    <span class="stock-status <?php echo $product['stock_status'] === 'in_stock' ? 'in-stock' : 'out-of-stock'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $product['stock_status'])); ?>
                                    </span>
                                    <h3 class="product-name">
                                        <a href="product.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                                    </h3>
                                    <div class="product-rating">
                                        <?php 
                                        $rating = round($product['rating']);
                                        for ($i = 1; $i <= 5; $i++): 
                                            if ($i <= $rating): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; 
                                        endfor; 
                                        ?>
                                        <span>(<?php echo $product['reviews_count']; ?>)</span>
                                    </div>
                                    <div class="product-price">
                                        <?php if ($product['discount_price']): ?>
                                            <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                                            <span class="discounted-price"><?php echo formatPrice($product['discount_price']); ?></span>
                                        <?php else: ?>
                                            <span class="discounted-price"><?php echo formatPrice($product['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo buildPageUrl($page - 1); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i === $page || $i === 1 || $i === $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildPageUrl($i); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo buildPageUrl($page + 1); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h4>No products found</h4>
                        <p class="text-muted">Try adjusting your filters or search terms</p>
                        <a href="products.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.page-header {
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    color: white;
}

.filter-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filter-title {
    font-weight: 600;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #FF6F00;
}

.filter-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.filter-list li {
    margin-bottom: 8px;
}

.filter-list a {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    border-radius: 4px;
    color: #212121;
    transition: all 0.3s;
}

.filter-list a:hover {
    background-color: #f5f5f5;
    color: #FF6F00;
}

.filter-list a.active {
    background-color: #FF6F00;
    color: white;
}

.filter-list .count {
    opacity: 0.7;
}

.results-header {
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-toggle .btn {
    margin-left: 5px;
}

.view-toggle .btn.active {
    background-color: #FF6F00;
    color: white;
    border-color: #FF6F00;
}
</style>

<?php
// Helper functions for URL building
function buildSortUrl($sort) {
    global $category_slug, $search, $price_min, $price_max;
    $params = [];
    if ($category_slug) $params['category'] = $category_slug;
    if ($search) $params['search'] = $search;
    if ($price_min > 0) $params['price_min'] = $price_min;
    if ($price_max > 0) $params['price_max'] = $price_max;
    $params['sort'] = $sort;
    return 'products.php?' . http_build_query($params);
}

function buildPageUrl($page_num) {
    global $category_slug, $sort_by, $search, $price_min, $price_max;
    $params = [];
    if ($category_slug) $params['category'] = $category_slug;
    if ($sort_by) $params['sort'] = $sort_by;
    if ($search) $params['search'] = $search;
    if ($price_min > 0) $params['price_min'] = $price_min;
    if ($price_max > 0) $params['price_max'] = $price_max;
    $params['page'] = $page_num;
    return 'products.php?' . http_build_query($params);
}
?>

<script>
function updatePriceLabel(type, value) {
    const label = document.querySelector(type === 'min' ? '.filter-card label:first-of-type' : '.filter-card label:nth-of-type(2)');
    label.textContent = (type === 'min' ? 'Min Price' : 'Max Price') + ': Rs. ' + parseInt(value).toLocaleString();
}

// View toggle
document.getElementById('gridView').addEventListener('click', function() {
    document.getElementById('productsContainer').classList.remove('list-view');
    document.getElementById('productsContainer').classList.add('grid-view');
    this.classList.add('active');
    document.getElementById('listView').classList.remove('active');
});

document.getElementById('listView').addEventListener('click', function() {
    document.getElementById('productsContainer').classList.remove('grid-view');
    document.getElementById('productsContainer').classList.add('list-view');
    this.classList.add('active');
    document.getElementById('gridView').classList.remove('active');
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
