<?php
/**
 * Naeem Electronic - Product Detail Page
 * Displays full product information with gallery, specs, reviews
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    redirect('products.php');
}

$db = new Database();

// Get product details
$db->query("SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name 
           FROM products p 
           LEFT JOIN categories c ON p.category_id = c.id
           LEFT JOIN brand b ON p.brand_id = b.id
           WHERE p.id = :id AND p.is_active = 1");
$db->bind(':id', $product_id);
$product = $db->fetch();

if (!$product) {
    setFlash('error', 'Product not found');
    redirect('products.php');
}

// Increment view count
$db->query("UPDATE products SET views = views + 1 WHERE id = :id");
$db->bind(':id', $product_id);
$db->execute();

// Get product images
$db->query("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, sort_order ASC");
$db->bind(':product_id', $product_id);
$product_images = $db->fetchAll();

// Get product specifications
$db->query("SELECT * FROM product_specifications WHERE product_id = :product_id");
$db->bind(':product_id', $product_id);
$specifications = $db->fetchAll();

// Get product reviews
$db->query("SELECT r.*, u.full_name, u.avatar 
           FROM reviews r 
           LEFT JOIN users u ON r.user_id = u.id
           WHERE r.product_id = :product_id AND r.is_approved = 1
           ORDER BY r.created_at DESC");
$db->bind(':product_id', $product_id);
$reviews = $db->fetchAll();

// Get related products
$db->query("SELECT p.*, pi.image_path 
           FROM products p 
           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
           WHERE p.category_id = :category_id AND p.id != :product_id AND p.is_active = 1
           ORDER BY RAND() LIMIT 4");
$db->bind(':category_id', $product['category_id']);
$db->bind(':product_id', $product_id);
$related_products = $db->fetchAll();

// Calculate discount
$discount_percentage = 0;
if ($product['discount_price'] && $product['discount_price'] < $product['price']) {
    $discount_percentage = calculateDiscount($product['price'], $product['discount_price']);
}

$page_title = $product['name'];
$page_description = $product['short_description'] ?? 'Premium home appliance from Naeem Electronic';
$current_page = 'product';

// Check if product is in wishlist
$is_in_wishlist = false;
if (isLoggedIn()) {
    $db->query("SELECT * FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
    $db->bind(':user_id', $_SESSION['user_id']);
    $db->bind(':product_id', $product_id);
    $wishlist_check = $db->fetch();
    $is_in_wishlist = $wishlist_check ? true : false;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-3">
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php">Products</a></li>
            <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_slug']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </div>
</nav>

<!-- Product Detail Section -->
<section class="product-detail-section py-5">
    <div class="container">
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4">
                <div class="product-gallery">
                    <div class="main-image">
                        <?php 
                        $main_image = '';
                        foreach ($product_images as $img) {
                            if ($img['is_primary'] || !$main_image) {
                                $main_image = UPLOADS_PATH . '/' . $img['image_path'];
                                break;
                            }
                        }
                        if (!$main_image) {
                            $main_image = 'https://via.placeholder.com/600x600?text=Product';
                        }
                        ?>
                        <img src="<?php echo $main_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="mainProductImage" class="img-fluid">
                    </div>
                    <?php if (count($product_images) > 1): ?>
                        <div class="thumbnail-images mt-3 d-flex gap-2">
                            <?php foreach ($product_images as $index => $img): ?>
                                <img src="<?php echo UPLOADS_PATH . '/' . $img['image_path']; ?>" 
                                     alt="Thumbnail <?php echo $index + 1; ?>" 
                                     class="thumbnail img-fluid <?php echo $img['is_primary'] ? 'active' : ''; ?>"
                                     onclick="changeMainImage('<?php echo UPLOADS_PATH . '/' . $img['image_path']; ?>', this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-detail-info">
                    <?php if ($product['brand_name']): ?>
                        <p class="text-secondary mb-2">Brand: <?php echo htmlspecialchars($product['brand_name']); ?></p>
                    <?php endif; ?>
                    
                    <h1 class="product-detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-rating mb-3">
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
                        <span class="ms-2"><?php echo $product['rating']; ?> out of 5</span>
                        <span class="ms-2">(<?php echo $product['reviews_count']; ?> reviews)</span>
                    </div>
                    
                    <div class="product-detail-price mb-3">
                        <?php if ($product['discount_price']): ?>
                            <span class="original-price fs-4 text-decoration-line-through text-muted"><?php echo formatPrice($product['price']); ?></span>
                            <span class="discounted-price fs-2 fw-bold text-danger"><?php echo formatPrice($product['discount_name']); ?></span>
                            <?php if ($discount_percentage > 0): ?>
                                <span class="discount-badge ms-2"><?php echo $discount_percentage; ?>% OFF</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="discounted-price fs-2 fw-bold"><?php echo formatPrice($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stock-status mb-3">
                        <?php if ($product['stock_status'] === 'in_stock'): ?>
                            <span class="badge bg-success">In Stock</span>
                            <span class="ms-2"><?php echo $product['stock_quantity']; ?> items available</span>
                        <?php elseif ($product['stock_status'] === 'out_of_stock'): ?>
                            <span class="badge bg-danger">Out of Stock</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Pre-Order</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($product['warranty']): ?>
                        <p class="mb-3"><i class="fas fa-shield-alt text-primary"></i> Warranty: <?php echo htmlspecialchars($product['warranty']); ?></p>
                    <?php endif; ?>
                    
                    <p class="product-short-description mb-4"><?php echo htmlspecialchars($product['short_description']); ?></p>
                    
                    <!-- Quantity and Add to Cart -->
                    <div class="product-actions mb-4">
                        <div class="quantity-selector mb-3">
                            <label class="me-3">Quantity:</label>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="decreaseQuantity()">-</button>
                                <input type="number" id="productQuantity" class="form-control" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" style="width: 60px;">
                                <button type="button" class="btn btn-outline-secondary" onclick="increaseQuantity()">+</button>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-lg me-2" onclick="addToCart(<?php echo $product_id; ?>)">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <button class="btn btn-outline-primary btn-lg me-2" onclick="buyNow(<?php echo $product_id; ?>)">
                                Buy Now
                            </button>
                            <button class="btn btn-outline-danger btn-lg wishlist-btn <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
                                    onclick="toggleWishlist(<?php echo $product_id; ?>, this)">
                                <i class="<?php echo $is_in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Share Buttons -->
                    <div class="share-buttons mb-4">
                        <p class="mb-2">Share this product:</p>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/product.php?id=' . $product_id); ?>" 
                           target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/product.php?id=' . $product_id); ?>" 
                           target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode(SITE_URL . '/product.php?id=' . $product_id); ?>" 
                           target="_blank" class="btn btn-outline-danger btn-sm">
                            <i class="fab fa-pinterest"></i>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode('Check out this product: ' . $product['name'] . ' - ' . SITE_URL . '/product.php?id=' . $product_id); ?>" 
                           target="_blank" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                    
                    <!-- Features -->
                    <div class="product-features">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i> 100% Genuine Product</li>
                            <li><i class="fas fa-check text-success me-2"></i> Free Delivery on orders above Rs. 5,000</li>
                            <li><i class="fas fa-check text-success me-2"></i> Easy 7-day returns</li>
                            <li><i class="fas fa-check text-success me-2"></i> Secure payment options</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Tabs -->
        <div class="product-tabs mt-5">
            <ul class="nav nav-tabs" id="productTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button">Description</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button">Specifications</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button">Reviews (<?php echo count($reviews); ?>)</button>
                </li>
            </ul>
            <div class="tab-content mt-3" id="productTabContent">
                <!-- Description -->
                <div class="tab-pane fade show active" id="description">
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                </div>
                
                <!-- Specifications -->
                <div class="tab-pane fade" id="specifications">
                    <?php if ($specifications): ?>
                        <table class="table table-striped">
                            <tbody>
                                <?php foreach ($specifications as $spec): ?>
                                    <tr>
                                        <th><?php echo htmlspecialchars($spec['spec_key']); ?></th>
                                        <td><?php echo htmlspecialchars($spec['spec_value']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No specifications available</p>
                    <?php endif; ?>
                </div>
                
                <!-- Reviews -->
                <div class="tab-pane fade" id="reviews">
                    <?php if (isLoggedIn()): ?>
                        <div class="review-form mb-4">
                            <h4>Write a Review</h4>
                            <form action="api/reviews.php" method="POST" id="reviewForm">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <div class="mb-3">
                                    <label>Rating</label>
                                    <div class="star-rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                            <label for="star<?php echo $i; ?>" class="star"><i class="far fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="reviewTitle">Title</label>
                                    <input type="text" class="form-control" id="reviewTitle" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="reviewComment">Review</label>
                                    <textarea class="form-control" id="reviewComment" name="comment" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="mb-3"><a href="login.php">Login</a> to write a review</p>
                    <?php endif; ?>
                    
                    <?php if ($reviews): ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item mb-4 pb-4 border-bottom">
                                    <div class="review-header">
                                        <h5><?php echo htmlspecialchars($review['full_name']); ?></h5>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <?php if ($review['title']): ?>
                                        <h6 class="mt-2"><?php echo htmlspecialchars($review['title']); ?></h6>
                                    <?php endif; ?>
                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    <?php if ($review['is_verified_purchase']): ?>
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Verified Purchase</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if ($related_products): ?>
            <div class="related-products mt-5">
                <h3 class="mb-4">Related Products</h3>
                <div class="row">
                    <?php foreach ($related_products as $related): 
                        $related_discount = 0;
                        if ($related['discount_price'] && $related['discount_price'] < $related['price']) {
                            $related_discount = calculateDiscount($related['price'], $related['discount_price']);
                        }
                        
                        $related_image = $related['image_path'] ? UPLOADS_PATH . '/' . $related['image_path'] : 'https://via.placeholder.com/400x400?text=Product';
                    ?>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo $related_image; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                                    <?php if ($related_discount > 0): ?>
                                        <span class="discount-badge"><?php echo $related_discount; ?>% OFF</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h4 class="product-name">
                                        <a href="product.php?id=<?php echo $related['id']; ?>"><?php echo htmlspecialchars($related['name']); ?></a>
                                    </h4>
                                    <div class="product-price">
                                        <?php if ($related['discount_price']): ?>
                                            <span class="original-price"><?php echo formatPrice($related['price']); ?></span>
                                            <span class="discounted-price"><?php echo formatPrice($related['discount_price']); ?></span>
                                        <?php else: ?>
                                            <span class="discounted-price"><?php echo formatPrice($related['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="add-to-cart-btn" data-product-id="<?php echo $related['id']; ?>">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.product-gallery .main-image img {
    width: 100%;
    height: 500px;
    object-fit: cover;
    border-radius: 8px;
}

.thumbnail-images .thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.thumbnail-images .thumbnail:hover,
.thumbnail-images .thumbnail.active {
    border-color: var(--secondary-color);
}

.product-detail-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.product-detail-price {
    padding: 1rem 0;
    border-top: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
}

.quantity-selector input {
    text-align: center;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    gap: 5px;
}

.star-rating input {
    display: none;
}

.star-rating label {
    cursor: pointer;
    font-size: 24px;
    color: #ddd;
}

.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #FFB300;
}

.review-item {
    padding: 1rem 0;
}

.review-header {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.review-rating {
    color: #FFB300;
}
</style>

<script>
function changeMainImage(src, element) {
    document.getElementById('mainProductImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    element.classList.add('active');
}

function increaseQuantity() {
    const input = document.getElementById('productQuantity');
    const max = parseInt(input.getAttribute('max'));
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('productQuantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCart(productId) {
    const quantity = document.getElementById('productQuantity').value;
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/cart.php?action=add', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            showNotification('Product added to cart!', 'success');
        } else {
            showNotification(data.message || 'Failed to add product', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function buyNow(productId) {
    addToCart(productId);
    setTimeout(() => {
        window.location.href = 'checkout.php';
    }, 500);
}

function toggleWishlist(productId, btn) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/wishlist.php?action=toggle', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.classList.toggle('active');
            const icon = btn.querySelector('i');
            if (data.added) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                showNotification('Added to wishlist!', 'success');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                showNotification('Removed from wishlist', 'success');
            }
            updateWishlistBadge(data.wishlist_count);
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
