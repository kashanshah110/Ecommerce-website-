<?php
/**
 * Naeem Electronic - Shopping Cart Page
 * Displays cart items and allows checkout
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Shopping Cart';
$page_description = 'Review your items before checkout';
$current_page = 'cart';

// Redirect if not logged in (optional - you can allow guest checkout)
// if (!isLoggedIn()) {
//     redirect('login.php?redirect=cart.php');
// }

$db = new Database();

// Get cart items
$cart_items = [];
$cart_total = 0;
$cart_subtotal = 0;
$cart_discount = 0;
$shipping_cost = 0;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Get cart items with product details
    $db->query("SELECT c.id as cart_id, c.quantity, p.*, pi.image_path, 
                CASE WHEN p.discount_price > 0 AND p.discount_price < p.price 
                     THEN p.discount_price ELSE p.price END as final_price
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE c.user_id = :user_id AND p.is_active = 1");
    $db->bind(':user_id', $user_id);
    $cart_items = $db->fetchAll();
    
    // Calculate totals
    foreach ($cart_items as $item) {
        $cart_subtotal += $item['price'] * $item['quantity'];
        $cart_total += $item['final_price'] * $item['quantity'];
    }
    
    $cart_discount = $cart_subtotal - $cart_total;
    
    // Calculate shipping
    $free_shipping_threshold = getSetting('free_shipping_threshold', 5000);
    if ($cart_total >= $free_shipping_threshold) {
        $shipping_cost = 0;
    } else {
        $shipping_cost = getSetting('shipping_cost', 200);
    }
    
    $final_total = $cart_total + $shipping_cost;
} elseif (isset($_SESSION['guest_cart'])) {
    // Handle guest cart from session
    foreach ($_SESSION['guest_cart'] as $product_id => $quantity) {
        $db->query("SELECT p.*, pi.image_path,
                    CASE WHEN p.discount_price > 0 AND p.discount_price < p.price 
                         THEN p.discount_price ELSE p.price END as final_price
                    FROM products p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE p.id = :id AND p.is_active = 1");
        $db->bind(':id', $product_id);
        $product = $db->fetch();
        
        if ($product) {
            $product['cart_id'] = $product_id;
            $product['quantity'] = $quantity;
            $cart_items[] = $product;
            
            $cart_subtotal += $product['price'] * $quantity;
            $cart_total += $product['final_price'] * $quantity;
        }
    }
    
    $cart_discount = $cart_subtotal - $cart_total;
    
    $free_shipping_threshold = getSetting('free_shipping_threshold', 5000);
    if ($cart_total >= $free_shipping_threshold) {
        $shipping_cost = 0;
    } else {
        $shipping_cost = getSetting('shipping_cost', 200);
    }
    
    $final_total = $cart_total + $shipping_cost;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">Shopping Cart</h1>
        <p class="mb-0"><?php echo count($cart_items); ?> item(s) in your cart</p>
    </div>
</section>

<!-- Cart Section -->
<section class="cart-section py-5">
    <div class="container">
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart text-center py-5">
                <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                <h2>Your cart is empty</h2>
                <p class="text-muted mb-4">🛒 Time to shop! Add some products to your cart.</p>
                <a href="products.php" class="btn btn-primary btn-lg">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8 mb-4">
                    <div class="cart-items-card">
                        <div class="card-header">
                            <h4 class="mb-0">Cart Items</h4>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): 
                                $item_image = $item['image_path'] ? UPLOADS_PATH . '/' . $item['image_path'] : 'https://via.placeholder.com/100x100?text=Product';
                                $item_total = $item['final_price'] * $item['quantity'];
                            ?>
                                <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
                                    <div class="cart-item-image">
                                        <img src="<?php echo $item_image; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="cart-item-details">
                                        <h5 class="cart-item-name">
                                            <a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                                        </h5>
                                        <p class="cart-item-sku text-muted small">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                        <?php if ($item['discount_price'] && $item['discount_price'] < $item['price']): ?>
                                            <p class="cart-item-price">
                                                <span class="text-decoration-line-through text-muted"><?php echo formatPrice($item['price']); ?></span>
                                                <span class="text-danger fw-bold"><?php echo formatPrice($item['final_price']); ?></span>
                                            </p>
                                        <?php else: ?>
                                            <p class="cart-item-price fw-bold"><?php echo formatPrice($item['final_price']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cart-item-quantity">
                                        <div class="quantity-control">
                                            <button class="btn btn-outline-secondary btn-sm qty-btn" 
                                                    data-action="decrease" data-cart-item-id="<?php echo $item['cart_id']; ?>">-</button>
                                            <input type="number" class="form-control form-control-sm qty-input" 
                                                   value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                   data-cart-item-id="<?php echo $item['cart_id']; ?>">
                                            <button class="btn btn-outline-secondary btn-sm qty-btn" 
                                                    data-action="increase" data-cart-item-id="<?php echo $item['cart_id']; ?>">+</button>
                                        </div>
                                    </div>
                                    <div class="cart-item-total">
                                        <p class="fw-bold"><?php echo formatPrice($item_total); ?></p>
                                    </div>
                                    <div class="cart-item-remove">
                                        <button class="btn btn-outline-danger btn-sm cart-remove-btn" data-cart-item-id="<?php echo $item['cart_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Continue Shopping -->
                    <div class="mt-3">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
                
                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="cart-summary-card">
                        <div class="card-header">
                            <h4 class="mb-0">Order Summary</h4>
                        </div>
                        <div class="card-body">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span><?php echo formatPrice($cart_subtotal); ?></span>
                            </div>
                            <?php if ($cart_discount > 0): ?>
                                <div class="summary-row text-success">
                                    <span>Discount</span>
                                    <span>-<?php echo formatPrice($cart_discount); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <?php if ($shipping_cost === 0): ?>
                                    <span class="text-success">FREE</span>
                                <?php else: ?>
                                    <span><?php echo formatPrice($shipping_cost); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($cart_total < $free_shipping_threshold): ?>
                                <p class="text-info small mb-3">
                                    <i class="fas fa-info-circle"></i> 
                                    Add <?php echo formatPrice($free_shipping_threshold - $cart_total); ?> more for FREE shipping!
                                </p>
                            <?php endif; ?>
                            <hr>
                            <div class="summary-row summary-total">
                                <span>Total</span>
                                <span class="fw-bold fs-5"><?php echo formatPrice($final_total); ?></span>
                            </div>
                            
                            <!-- Coupon -->
                            <div class="coupon-section mt-4">
                                <label class="form-label">Have a coupon?</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="couponCode" placeholder="Enter coupon code">
                                    <button class="btn btn-outline-secondary" type="button" id="applyCouponBtn">Apply</button>
                                </div>
                                <div id="couponMessage" class="mt-2"></div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <a href="checkout.php" class="btn btn-primary btn-lg w-100 mt-4">
                                <i class="fas fa-lock"></i> Proceed to Checkout
                            </a>
                            
                            <!-- Trust Badges -->
                            <div class="trust-badges mt-4 text-center">
                                <div class="d-flex justify-content-center gap-3">
                                    <i class="fas fa-shield-alt fa-2x text-muted" title="Secure Payment"></i>
                                    <i class="fas fa-truck fa-2x text-muted" title="Free Delivery"></i>
                                    <i class="fas fa-undo fa-2x text-muted" title="Easy Returns"></i>
                                </div>
                                <p class="small text-muted mt-2">Secure checkout with SSL encryption</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.page-header {
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    color: white;
}

.cart-items-card,
.cart-summary-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.cart-items-card .card-header,
.cart-summary-card .card-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
}

.cart-items-card .card-body,
.cart-summary-card .card-body {
    padding: 20px;
}

.cart-item {
    display: grid;
    grid-template-columns: 100px 1fr auto auto auto;
    gap: 20px;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid #dee2e6;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
}

.cart-item-name a {
    color: #212121;
    text-decoration: none;
}

.cart-item-name a:hover {
    color: #FF6F00;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 5px;
}

.quantity-control .qty-input {
    width: 60px;
    text-align: center;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.summary-total {
    font-size: 1.2rem;
    color: #FF6F00;
}

.trust-badges i {
    opacity: 0.6;
    transition: opacity 0.3s;
}

.trust-badges i:hover {
    opacity: 1;
}

@media (max-width: 768px) {
    .cart-item {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .cart-item-image {
        margin: 0 auto;
    }
    
    .quantity-control {
        justify-content: center;
    }
}
</style>

<script>
// Update quantity on cart page
function updateCartPageItemQuantity(cartId, quantity) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', quantity);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/cart.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message || 'Failed to update quantity', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function removeCartPageItem(cartId) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/cart.php?action=remove', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message || 'Failed to remove item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Update quantity
document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const cartId = this.dataset.cartItemId;
        const action = this.dataset.action;
        const input = document.querySelector(`.qty-input[data-cart-item-id="${cartId}"]`);
        let quantity = parseInt(input.value) || 1;
        
        if (action === 'increase') {
            quantity++;
        } else if (action === 'decrease' && quantity > 1) {
            quantity--;
        }
        
        input.value = quantity;
        updateCartPageItemQuantity(cartId, quantity);
    });
});

// Quantity input change
document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', function() {
        const cartId = this.dataset.cartItemId;
        let quantity = parseInt(this.value);
        if (quantity < 1 || isNaN(quantity)) {
            quantity = 1;
            this.value = quantity;
        }
        updateCartPageItemQuantity(cartId, quantity);
    });
});

// Remove item
document.querySelectorAll('.cart-remove-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const cartId = this.dataset.cartItemId;
        if (confirm('Are you sure you want to remove this item?')) {
            removeCartPageItem(cartId);
        }
    });
});

// Apply coupon
const applyCouponBtn = document.getElementById('applyCouponBtn');
if (applyCouponBtn) {
    applyCouponBtn.addEventListener('click', function() {
        const couponCodeInput = document.getElementById('couponCode');
        const couponCode = couponCodeInput ? couponCodeInput.value.trim() : '';
        if (couponCode) {
            applyCoupon(couponCode);
        }
    });
}

function updateCartItemQuantity(cartId, quantity) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', quantity);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/cart.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message || 'Failed to update quantity', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function removeCartItem(cartId) {
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/cart.php?action=remove', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message || 'Failed to remove item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function applyCoupon(couponCode) {
    const formData = new FormData();
    formData.append('coupon_code', couponCode);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/cart.php?action=apply_coupon', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('couponMessage');
        if (data.success) {
            messageDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
            setTimeout(() => location.reload(), 1500);
        } else {
            messageDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + (data.message || 'Invalid coupon') + '</span>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
