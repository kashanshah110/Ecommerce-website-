<?php
/**
 * Naeem Electronic - Homepage
 * Main landing page with hero, features, categories, and products
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Home';
$page_description = 'Your One-Stop Shop for Premium Home Appliances in Pakistan';
$current_page = 'home';

$db = new Database();

// Get featured products
$db->query("SELECT p.*, pi.image_path, c.name as category_name 
           FROM products p 
           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
           LEFT JOIN categories c ON p.category_id = c.id
           WHERE p.is_active = 1 AND (p.is_featured = 1 OR p.is_new = 1 OR p.is_best_seller = 1)
           ORDER BY p.created_at DESC 
           LIMIT 8");
$featured_products = $db->fetchAll();

// Get categories
$db->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order LIMIT 6");
$categories = $db->fetchAll();

// Get banners for hero slider
$db->query("SELECT * FROM banners WHERE position = 'hero' AND is_active = 1 
            AND (start_date IS NULL OR start_date <= NOW()) 
            AND (end_date IS NULL OR end_date >= NOW())
            ORDER BY sort_order LIMIT 5");
$banners = $db->fetchAll();

// If no banners, use default hero images
$hero_slides = [];
if ($banners) {
    foreach ($banners as $banner) {
        $hero_slides[] = [
            'image' => UPLOADS_PATH . '/' . $banner['image'],
            'title' => $banner['title'],
            'link' => $banner['link']
        ];
    }
} else {
    // Default hero slides
    $hero_slides = [
        [
            'image' => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=1920',
            'title' => 'Complete Kitchen Setup',
            'link' => 'products.php?category=kitchen-appliances'
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=1920',
            'title' => 'Modern Washing Machines',
            'link' => 'products.php?category=washing-machines'
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=1920',
            'title' => 'Premium Microwave Ovens',
            'link' => 'products.php?category=microwave-ovens'
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1570222094114-d054a817e56b?w=1920',
            'title' => 'Smart Home Appliances',
            'link' => 'products.php'
        ],
        [
            'image' => 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?w=1920',
            'title' => 'Fresh Juicers & Blenders',
            'link' => 'products.php?category=juicers-blenders'
        ]
    ];
}

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-slider">
        <?php foreach ($hero_slides as $index => $slide): ?>
            <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                <img src="<?php echo $slide['image']; ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>">
                <div class="hero-overlay">
                    <div class="hero-content">
                        <h1 class="hero-title">Welcome to Naeem Electronic</h1>
                        <p class="hero-subtitle">Your One-Stop Shop for Premium Home Appliances</p>
                        <div class="hero-buttons">
                            <a href="<?php echo $slide['link']; ?>" class="btn btn-primary">Shop Now</a>
                            <a href="products.php" class="btn btn-outline">Explore Products</a>
                        </div>
                        <div class="trust-badges">
                            <div class="trust-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>100% Genuine Products</span>
                            </div>
                            <div class="trust-badge">
                                <i class="fas fa-truck"></i>
                                <span>Free Delivery</span>
                            </div>
                            <div class="trust-badge">
                                <i class="fas fa-undo"></i>
                                <span>Easy Returns</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="hero-dots">
        <?php foreach ($hero_slides as $index => $slide): ?>
            <div class="hero-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-shield-alt feature-icon"></i>
                <h3 class="feature-title">100% Secure Payment</h3>
                <p>Your payments are protected with SSL encryption</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-truck feature-icon"></i>
                <h3 class="feature-title">Free Delivery</h3>
                <p>Free delivery across Pakistan on orders above Rs. 5,000</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-certificate feature-icon"></i>
                <h3 class="feature-title">Genuine Products</h3>
                <p>100% original products with manufacturer warranty</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-headset feature-icon"></i>
                <h3 class="feature-title">24/7 Support</h3>
                <p>Customer support available round the clock</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-award feature-icon"></i>
                <h3 class="feature-title">1-3 Year Warranty</h3>
                <p>Extended warranty on all products</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-exchange-alt feature-icon"></i>
                <h3 class="feature-title">Easy Returns</h3>
                <p>Hassle-free return policy within 7 days</p>
            </div>
        </div>
    </div>
</section>

<!-- Category Section -->
<section class="category-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Shop by Category</h2>
            <p class="section-subtitle">Browse our wide range of home appliances</p>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $category): 
                // Get product count for this category
                $db->query("SELECT COUNT(*) as count FROM products WHERE category_id = :category_id AND is_active = 1");
                $db->bind(':category_id', $category['id']);
                $count_result = $db->fetch();
                $product_count = $count_result['count'];
                
                // Use category image or placeholder
                $category_image = $category['image'] ? UPLOADS_PATH . '/' . $category['image'] : 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600';
            ?>
                <div class="category-card">
                    <img src="<?php echo $category_image; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                    <div class="category-overlay">
                        <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="category-count"><?php echo $product_count; ?> Products</p>
                        <a href="products.php?category=<?php echo $category['slug']; ?>" class="category-btn">View All</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-primary">View All Categories</a>
        </div>
    </div>
</section>

<!-- Product Section -->
<section class="product-section">
    <div class="container">
        <div class="product-header">
            <h2 class="product-title">🔥 Exclusive Deals & Offers</h2>
            <p class="section-subtitle">Grab the best deals on premium home appliances</p>
        </div>
        
        <div class="countdown-timer">
            <div class="countdown-item">
                <span class="countdown-value countdown-hours" id="hours">00</span>
                <span class="countdown-label">Hours</span>
            </div>
            <div class="countdown-item">
                <span class="countdown-value countdown-minutes" id="minutes">00</span>
                <span class="countdown-label">Minutes</span>
            </div>
            <div class="countdown-item">
                <span class="countdown-value countdown-seconds" id="seconds">00</span>
                <span class="countdown-label">Seconds</span>
            </div>
        </div>
        
        <div class="product-filters mt-4">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="new">New Arrivals</button>
            <button class="filter-btn" data-filter="featured">Featured</button>
            <button class="filter-btn" data-filter="bestseller">Best Sellers</button>
        </div>
        
        <div class="product-grid mt-4">
            <?php foreach ($featured_products as $product): 
                $discount_percentage = 0;
                if ($product['discount_price'] && $product['discount_price'] < $product['price']) {
                    $discount_percentage = calculateDiscount($product['price'], $product['discount_price']);
                }
                
                $product_image = $product['image_path'] ? UPLOADS_PATH . '/' . $product['image_path'] : 'https://via.placeholder.com/400x400?text=Product';
                
                $filter_class = '';
                if ($product['is_new']) $filter_class = 'new';
                elseif ($product['is_featured']) $filter_class = 'featured';
                elseif ($product['is_best_seller']) $filter_class = 'bestseller';
            ?>
                <div class="product-card" data-category="<?php echo $filter_class; ?>">
                    <div class="product-image">
                        <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php if ($discount_percentage > 0): ?>
                            <span class="discount-badge"><?php echo $discount_percentage; ?>% OFF</span>
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
                            <span>(<?php echo $product['reviews_count']; ?> reviews)</span>
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
        
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-primary">View All Products</a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="features-section" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Why Choose Naeem Electronic?</h2>
            <p class="section-subtitle">Your trusted partner for quality home appliances</p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-medal feature-icon" style="font-size: 60px;"></i>
                    <h4 class="mt-3">Quality Assurance</h4>
                    <p>We only sell genuine products from authorized dealers</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-tags feature-icon" style="font-size: 60px;"></i>
                    <h4 class="mt-3">Best Prices</h4>
                    <p>Competitive pricing with regular discounts and offers</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <i class="fas fa-tools feature-icon" style="font-size: 60px;"></i>
                    <h4 class="mt-3">Expert Support</h4>
                    <p>Professional installation and after-sales service</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
