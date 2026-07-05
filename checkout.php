<?php
/**
 * Naeem Electronic - Checkout Page
 * Multi-step checkout process
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php?redirect=checkout.php');
}

$page_title = 'Checkout';
$page_description = 'Complete your order';
$current_page = 'checkout';

$db = new Database();
$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = [];
$cart_total = 0;
$cart_subtotal = 0;
$cart_discount = 0;
$shipping_cost = 0;
$applied_coupon = null;

$db->query("SELECT c.id as cart_id, c.quantity, p.*, pi.image_path,
            CASE WHEN p.discount_price > 0 AND p.discount_price < p.price 
                 THEN p.discount_price ELSE p.price END as final_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE c.user_id = :user_id AND p.is_active = 1");
$db->bind(':user_id', $user_id);
$cart_items = $db->fetchAll();

if (empty($cart_items)) {
    redirect('cart.php');
}

// Calculate totals
foreach ($cart_items as $item) {
    $cart_subtotal += $item['price'] * $item['quantity'];
    $cart_total += $item['final_price'] * $item['quantity'];
}

$cart_discount = $cart_subtotal - $cart_total;

// Check for applied coupon
if (isset($_SESSION['applied_coupon'])) {
    $coupon_code = $_SESSION['applied_coupon'];
    $db->query("SELECT * FROM coupons WHERE code = :code AND is_active = 1 
                AND start_date <= NOW() AND end_date >= NOW()");
    $db->bind(':code', $coupon_code);
    $coupon = $db->fetch();
    
    if ($coupon) {
        $applied_coupon = $coupon;
        // Recalculate with coupon
        if ($coupon['discount_type'] === 'percentage') {
            $coupon_discount = ($cart_total * $coupon['discount_value']) / 100;
        } else {
            $coupon_discount = $coupon['discount_value'];
        }
        
        if ($coupon['max_discount_amount'] && $coupon_discount > $coupon['max_discount_amount']) {
            $coupon_discount = $coupon['max_discount_amount'];
        }
        
        $cart_total -= $coupon_discount;
        $cart_discount += $coupon_discount;
    }
}

// Calculate shipping
$free_shipping_threshold = getSetting('free_shipping_threshold', 5000);
if ($cart_total >= $free_shipping_threshold) {
    $shipping_cost = 0;
} else {
    $shipping_cost = getSetting('shipping_cost', 200);
}

$final_total = $cart_total + $shipping_cost;

// Get user's saved addresses
$db->query("SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_default DESC");
$db->bind(':user_id', $user_id);
$saved_addresses = $db->fetchAll();

// Get user info
$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $user_id);
$user = $db->fetch();

// Pakistan provinces
$provinces = [
    'Punjab' => ['Lahore', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala', 'Sialkot', 'Sargodha'],
    'Sindh' => ['Karachi', 'Hyderabad', 'Sukkur', 'Larkana', 'Mirpurkhas'],
    'Khyber Pakhtunkhwa' => ['Peshawar', 'Mardan', 'Swat', 'Abbottabad', 'Mingora'],
    'Balochistan' => ['Quetta', 'Gwadar', 'Turbat', 'Sibi'],
    'Azad Kashmir' => ['Muzaffarabad', 'Mirpur', 'Rawalakot'],
    'Gilgit-Baltistan' => ['Gilgit', 'Skardu', 'Hunza']
];

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">Checkout</h1>
        <p class="mb-0">Complete your order in a few simple steps</p>
    </div>
</section>

<!-- Checkout Section -->
<section class="checkout-section py-5">
    <div class="container">
        <!-- Checkout Steps -->
        <div class="checkout-steps mb-5">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Delivery</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Summary</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Payment</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-label">Confirm</div>
            </div>
        </div>
        
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <form id="checkoutForm" action="api/checkout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <!-- Step 1: Delivery Information -->
                    <div class="checkout-step active" id="step1">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-truck me-2"></i>Delivery Information</h4>
                            </div>
                            <div class="card-body">
                                <!-- Saved Addresses -->
                                <?php if ($saved_addresses): ?>
                                    <div class="saved-addresses mb-4">
                                        <h6 class="mb-3">Select Saved Address</h6>
                                        <div class="row">
                                            <?php foreach ($saved_addresses as $index => $addr): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="address-card <?php echo $addr['is_default'] ? 'selected' : ''; ?>" 
                                                         data-address-id="<?php echo $addr['id']; ?>">
                                                        <div class="address-header">
                                                            <strong><?php echo htmlspecialchars($addr['full_name']); ?></strong>
                                                            <?php if ($addr['is_default']): ?>
                                                                <span class="badge bg-primary ms-2">Default</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="mb-1 small">
                                                            <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($addr['phone']); ?>
                                                        </p>
                                                        <p class="mb-1 small">
                                                            <?php echo htmlspecialchars($addr['address']); ?>, 
                                                            <?php echo htmlspecialchars($addr['city']); ?>, 
                                                            <?php echo htmlspecialchars($addr['province']); ?>
                                                        </p>
                                                        <?php if ($addr['postal_code']): ?>
                                                            <p class="mb-0 small">
                                                                <i class="fas fa-map-marker-alt me-1"></i> 
                                                                <?php echo htmlspecialchars($addr['postal_code']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="newAddressBtn">
                                            <i class="fas fa-plus"></i> Add New Address
                                        </button>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- New Address Form -->
                                <div id="newAddressForm" style="<?php echo $saved_addresses ? 'display: none;' : ''; ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="fullName" class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" id="fullName" name="full_name" 
                                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone Number *</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                                   placeholder="+92XXXXXXXXXX" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="isIslamabad" name="islamabad">
                                        <label class="form-check-label" for="isIslamabad">
                                            Deliver to Islamabad (special direct purchase available)
                                        </label>
                                    </div>
                                    <div class="alert alert-warning mb-3" id="islamabadNotice" style="display: none;">
                                        Islamabad delivery is available directly. Province and city selection are not required.
                                    </div>
                                    <div id="locationFields" class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="province" class="form-label">Province *</label>
                                            <select class="form-select" id="province" name="province" required>
                                                <option value="">Select Province</option>
                                                <?php foreach ($provinces as $province => $cities): ?>
                                                    <option value="<?php echo $province; ?>"><?php echo $province; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">City *</label>
                                            <select class="form-select" id="city" name="city" required disabled>
                                                <option value="">Select Province First</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Complete Address *</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" 
                                                  placeholder="House/Flat No., Street, Area" required></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="postalCode" class="form-label">Postal Code</label>
                                            <input type="text" class="form-control" id="postalCode" name="postal_code">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="landmark" class="form-label">Landmark (Optional)</label>
                                            <input type="text" class="form-control" id="landmark" name="landmark" 
                                                   placeholder="Nearby landmark">
                                        </div>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="saveAddress" name="save_address">
                                        <label class="form-check-label" for="saveAddress">
                                            Save this address for future orders
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-primary btn-lg" id="nextStep1">
                            Continue to Summary <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                    
                    <!-- Step 2: Order Summary -->
                    <div class="checkout-step" id="step2">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-box me-2"></i>Order Summary</h4>
                            </div>
                            <div class="card-body">
                                <div class="order-items">
                                    <?php foreach ($cart_items as $item): 
                                        $item_image = $item['image_path'] ? UPLOADS_PATH . '/' . $item['image_path'] : 'https://via.placeholder.com/80x80?text=Product';
                                        $item_total = $item['final_price'] * $item['quantity'];
                                    ?>
                                        <div class="order-item">
                                            <img src="<?php echo $item_image; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <div class="order-item-details">
                                                <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <p class="text-muted small">Qty: <?php echo $item['quantity']; ?></p>
                                            </div>
                                            <div class="order-item-price">
                                                <strong><?php echo formatPrice($item_total); ?></strong>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="order-summary">
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
                                    <div class="summary-row summary-total">
                                        <span>Total</span>
                                        <span class="fs-5 fw-bold"><?php echo formatPrice($final_total); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($applied_coupon): ?>
                                    <div class="alert alert-success mt-3 d-flex justify-content-between align-items-center flex-wrap">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-tag me-2"></i>
                                            <span>Coupon applied: <?php echo htmlspecialchars($applied_coupon['code']); ?></span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeCoupon">
                                            Remove
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="coupon-input mt-3">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="couponCode" placeholder="Enter coupon code">
                                            <button type="button" class="btn btn-outline-secondary" id="applyCoupon">Apply</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline-secondary me-2" id="prevStep2">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="nextStep2">
                            Continue to Payment <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                    
                    <!-- Step 3: Payment Method -->
                    <div class="checkout-step" id="step3">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Method</h4>
                            </div>
                            <div class="card-body">
                                <div class="payment-methods">
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="cod" value="cod" checked>
                                        <label for="cod" class="payment-label">
                                            <div class="payment-icon">
                                                <i class="fas fa-money-bill-wave fa-2x"></i>
                                            </div>
                                            <div class="payment-info">
                                                <h6>Cash on Delivery</h6>
                                                <p class="text-muted small">Pay when you receive your order</p>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="card" value="card">
                                        <label for="card" class="payment-label">
                                            <div class="payment-icon">
                                                <i class="fas fa-credit-card fa-2x"></i>
                                            </div>
                                            <div class="payment-info">
                                                <h6>Credit/Debit Card</h6>
                                                <p class="text-muted small">Pay securely with your card</p>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="jazzcash" value="jazzcash">
                                        <label for="jazzcash" class="payment-label">
                                            <div class="payment-icon">
                                                <i class="fas fa-mobile-alt fa-2x"></i>
                                            </div>
                                            <div class="payment-info">
                                                <h6>JazzCash</h6>
                                                <p class="text-muted small">Pay with JazzCash mobile account</p>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="easypaisa" value="easypaisa">
                                        <label for="easypaisa" class="payment-label">
                                            <div class="payment-icon">
                                                <i class="fas fa-mobile-alt fa-2x"></i>
                                            </div>
                                            <div class="payment-info">
                                                <h6>EasyPaisa</h6>
                                                <p class="text-muted small">Pay with EasyPaisa mobile account</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div id="paymentDetailsContainer" class="mt-4">
                                    <div id="cardDetails" class="payment-details-group d-none">
                                        <h6 class="mb-3">Credit/Debit Card Details</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="cardHolderName" class="form-label">Cardholder Name *</label>
                                                <input type="text" class="form-control" id="cardHolderName" name="card_holder_name" placeholder="Name on card">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cardNumber" class="form-label">Card Number *</label>
                                                <input type="text" class="form-control" id="cardNumber" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="expiryMonth" class="form-label">Expiry Month *</label>
                                                <input type="text" class="form-control" id="expiryMonth" name="expiry_month" placeholder="MM" maxlength="2">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="expiryYear" class="form-label">Expiry Year *</label>
                                                <input type="text" class="form-control" id="expiryYear" name="expiry_year" placeholder="YY" maxlength="2">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="cvv" class="form-label">CVV *</label>
                                                <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123" maxlength="4">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="jazzcashDetails" class="payment-details-group d-none">
                                        <h6 class="mb-3">JazzCash Payment Details</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="jazzcashMobile" class="form-label">JazzCash Mobile Number *</label>
                                                <input type="text" class="form-control" id="jazzcashMobile" name="jazzcash_mobile" placeholder="03XXXXXXXXX" maxlength="12">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="jazzcashTxn" class="form-label">Transaction ID *</label>
                                                <input type="text" class="form-control" id="jazzcashTxn" name="jazzcash_txn_id" placeholder="Transaction ID">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="easypaisaDetails" class="payment-details-group d-none">
                                        <h6 class="mb-3">EasyPaisa Payment Details</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="easypaisaMobile" class="form-label">EasyPaisa Mobile Number *</label>
                                                <input type="text" class="form-control" id="easypaisaMobile" name="easypaisa_mobile" placeholder="03XXXXXXXXX" maxlength="12">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="easypaisaTxn" class="form-label">Transaction ID *</label>
                                                <input type="text" class="form-control" id="easypaisaTxn" name="easypaisa_txn_id" placeholder="Transaction ID">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Your payment information is secure and encrypted
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline-secondary me-2" id="prevStep3">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="nextStep3">
                            Review Order <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                    
                    <!-- Step 4: Order Confirmation -->
                    <div class="checkout-step" id="step4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Confirm Order</h4>
                            </div>
                            <div class="card-body">
                                <div class="order-confirmation">
                                    <div class="confirmation-section mb-4">
                                        <h6><i class="fas fa-truck me-2"></i>Delivery Address</h6>
                                        <div id="confirmAddress"></div>
                                    </div>
                                    
                                    <div class="confirmation-section mb-4">
                                        <h6><i class="fas fa-credit-card me-2"></i>Payment Method</h6>
                                        <div id="confirmPayment"></div>
                                    </div>
                                    
                                    <div class="confirmation-section mb-4">
                                        <h6><i class="fas fa-box me-2"></i>Order Items</h6>
                                        <div id="confirmItems">
                                            <?php echo count($cart_items); ?> items
                                        </div>
                                    </div>
                                    
                                    <div class="confirmation-section">
                                        <h6><i class="fas fa-receipt me-2"></i>Order Total</h6>
                                        <div class="fs-4 fw-bold text-primary"><?php echo formatPrice($final_total); ?></div>
                                    </div>
                                </div>
                                
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="termsCheck" required>
                                    <label class="form-check-label" for="termsCheck">
                                        I agree to the <a href="terms-conditions.php" target="_blank">Terms & Conditions</a> 
                                        and <a href="privacy-policy.php" target="_blank">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline-secondary me-2" id="prevStep4">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" id="placeOrderBtn">
                            <i class="fas fa-lock me-2"></i> Place Order
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Order Summary Sidebar -->
            <div class="col-lg-4">
                <div class="order-summary-sidebar">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="summary-items">
                                <?php foreach (array_slice($cart_items, 0, 3) as $item): 
                                    $item_image = $item['image_path'] ? UPLOADS_PATH . '/' . $item['image_path'] : 'https://via.placeholder.com/60x60?text=Product';
                                ?>
                                    <div class="summary-item">
                                        <img src="<?php echo $item_image; ?>" alt="">
                                        <div>
                                            <p class="mb-0 small"><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p class="mb-0 small text-muted">Qty: <?php echo $item['quantity']; ?></p>
                                        </div>
                                        <span class="small fw-bold"><?php echo formatPrice($item['final_price'] * $item['quantity']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($cart_items) > 3): ?>
                                    <p class="text-center small text-muted">+<?php echo count($cart_items) - 3; ?> more items</p>
                                <?php endif; ?>
                            </div>
                            
                            <hr>
                            
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
                            <div class="summary-row summary-total">
                                <span>Total</span>
                                <span class="fw-bold"><?php echo formatPrice($final_total); ?></span>
                            </div>
                            
                            <div class="estimated-delivery mt-3">
                                <p class="small text-muted mb-1">
                                    <i class="fas fa-truck me-1"></i> Estimated Delivery
                                </p>
                                <p class="small fw-bold">
                                    <?php echo date('M d', strtotime('+3 days')); ?> - <?php echo date('M d', strtotime('+5 days')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.page-header {
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    color: white;
}

.checkout-steps {
    display: flex;
    justify-content: space-between;
    max-width: 600px;
    margin: 0 auto;
    position: relative;
}

.checkout-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #dee2e6;
    z-index: 0;
}

.step {
    text-align: center;
    position: relative;
    z-index: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dee2e6;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 5px;
    font-weight: 600;
    transition: all 0.3s;
}

.step.active .step-number {
    background: #FF6F00;
    color: white;
}

.step.completed .step-number {
    background: #4CAF50;
    color: white;
}

.step-label {
    font-size: 14px;
    color: #6c757d;
}

.step.active .step-label {
    color: #FF6F00;
    font-weight: 600;
}

.checkout-step {
    display: none;
}

.checkout-step.active {
    display: block;
}

.address-card {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s;
}

.address-card:hover,
.address-card.selected {
    border-color: #FF6F00;
    background-color: #fff8f0;
}

.payment-option {
    margin-bottom: 15px;
}

.payment-option input[type="radio"] {
    display: none;
}

.payment-label {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-option input[type="radio"]:checked + .payment-label {
    border-color: #FF6F00;
    background-color: #fff8f0;
}

.payment-icon {
    color: #FF6F00;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.summary-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.summary-total {
    font-size: 1.2rem;
    color: #FF6F00;
    border-top: 2px solid #dee2e6;
    padding-top: 10px;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.order-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.order-item-details {
    flex: 1;
}

.order-item-price {
    font-weight: 600;
}

.confirmation-section h6 {
    color: #FF6F00;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .checkout-steps {
        font-size: 12px;
    }
    
    .step-number {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
}
</style>

<script>
const provinces = <?php echo json_encode($provinces); ?>;
let selectedAddress = null;

// Province change handler
const provinceSelect = document.getElementById('province');
const citySelect = document.getElementById('city');
const isIslamabadCheckbox = document.getElementById('isIslamabad');
const locationFields = document.getElementById('locationFields');
const islamabadNotice = document.getElementById('islamabadNotice');

function updateLocationFields() {
    if (isIslamabadCheckbox && isIslamabadCheckbox.checked) {
        locationFields.style.display = 'none';
        islamabadNotice.style.display = 'block';

        let islamabadProvinceOption = provinceSelect.querySelector('option[value="Islamabad"]');
        if (!islamabadProvinceOption) {
            islamabadProvinceOption = document.createElement('option');
            islamabadProvinceOption.value = 'Islamabad';
            islamabadProvinceOption.textContent = 'Islamabad';
            provinceSelect.appendChild(islamabadProvinceOption);
        }

        provinceSelect.value = 'Islamabad';
        provinceSelect.disabled = false;
        provinceSelect.required = false;

        citySelect.innerHTML = '<option value="Islamabad">Islamabad</option>';
        citySelect.disabled = false;
        citySelect.value = 'Islamabad';
        citySelect.required = false;
    } else {
        locationFields.style.display = 'flex';
        islamabadNotice.style.display = 'none';

        provinceSelect.disabled = false;
        provinceSelect.required = true;
        citySelect.disabled = true;
        citySelect.required = true;
        provinceSelect.value = '';
        citySelect.innerHTML = '<option value="">Select Province First</option>';

        const islamabadProvinceOption = provinceSelect.querySelector('option[value="Islamabad"]');
        if (islamabadProvinceOption) {
            provinceSelect.removeChild(islamabadProvinceOption);
        }
    }
}

if (provinceSelect) {
    provinceSelect.addEventListener('change', function() {
        const province = this.value;
        citySelect.innerHTML = '<option value="">Select City</option>';
        
        if (province && provinces[province]) {
            citySelect.disabled = false;
            provinces[province].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        } else {
            citySelect.disabled = true;
        }
    });
}

if (isIslamabadCheckbox) {
    isIslamabadCheckbox.addEventListener('change', updateLocationFields);
}

updateLocationFields();

function toggleNewAddressFields(disabled) {
    document.querySelectorAll('#newAddressForm input, #newAddressForm textarea, #newAddressForm select').forEach(field => {
        field.disabled = disabled;
    });
}

const addressCards = document.querySelectorAll('.address-card');
const hasSavedAddresses = addressCards.length > 0;
const defaultSelectedCard = document.querySelector('.address-card.selected');
selectedAddress = defaultSelectedCard ? defaultSelectedCard.dataset.addressId : (addressCards[0] ? addressCards[0].dataset.addressId : null);
if (selectedAddress && !defaultSelectedCard && addressCards[0]) {
    addressCards[0].classList.add('selected');
}
toggleNewAddressFields(hasSavedAddresses);

// Saved address selection
addressCards.forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        selectedAddress = this.dataset.addressId;
        document.getElementById('newAddressForm').style.display = 'none';
        toggleNewAddressFields(true);
    });
});

// New address button
const newAddressBtn = document.getElementById('newAddressBtn');
if (newAddressBtn) {
    newAddressBtn.addEventListener('click', function() {
        document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
        selectedAddress = null;
        document.getElementById('newAddressForm').style.display = 'block';
        toggleNewAddressFields(false);
    });
}

// Payment method fields
const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
const cardDetailsSection = document.getElementById('cardDetails');
const jazzcashDetailsSection = document.getElementById('jazzcashDetails');
const easypaisaDetailsSection = document.getElementById('easypaisaDetails');

function updatePaymentFields() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    cardDetailsSection.classList.toggle('d-none', paymentMethod !== 'card');
    jazzcashDetailsSection.classList.toggle('d-none', paymentMethod !== 'jazzcash');
    easypaisaDetailsSection.classList.toggle('d-none', paymentMethod !== 'easypaisa');
}

paymentMethodRadios.forEach(radio => radio.addEventListener('change', updatePaymentFields));
updatePaymentFields();

// Step navigation
document.getElementById('nextStep1').addEventListener('click', function() {
    if (validateStep1()) {
        goToStep(2);
    }
});

document.getElementById('prevStep2').addEventListener('click', function() {
    goToStep(1);
});

document.getElementById('nextStep2').addEventListener('click', function() {
    goToStep(3);
});

document.getElementById('prevStep3').addEventListener('click', function() {
    goToStep(2);
});

document.getElementById('nextStep3').addEventListener('click', function() {
    if (validateStep3()) {
        updateConfirmation();
        goToStep(4);
    }
});

document.getElementById('prevStep4').addEventListener('click', function() {
    goToStep(3);
});

function goToStep(step) {
    // Hide all steps
    document.querySelectorAll('.checkout-step').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.step').forEach(s => {
        s.classList.remove('active');
        if (parseInt(s.dataset.step) < step) {
            s.classList.add('completed');
        } else {
            s.classList.remove('completed');
        }
    });
    
    // Show current step
    document.getElementById('step' + step).classList.add('active');
    document.querySelector('.step[data-step="' + step + '"]').classList.add('active');
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep1() {
    if (!selectedAddress) {
        const fullName = document.getElementById('fullName').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        const isIslamabad = isIslamabadCheckbox && isIslamabadCheckbox.checked;
        const province = provinceSelect ? provinceSelect.value : '';
        const city = citySelect ? citySelect.value : '';
        const address = document.getElementById('address').value.trim();
        
        if (!fullName || !phone || !email || !address || (!isIslamabad && (!province || !city))) {
            showNotification('Please fill in all required fields', 'error');
            return false;
        }
        
        // Validate phone
        if (!/^(\+92|0)?[0-9]{10}$/.test(phone)) {
            showNotification('Please enter a valid Pakistan phone number', 'error');
            return false;
        }
        
        // Validate email
        if (!isValidEmail(email)) {
            showNotification('Please enter a valid email address', 'error');
            return false;
        }
    }
    
    return true;
}

function validateStep3() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

    if (paymentMethod === 'card') {
        const cardHolderName = document.getElementById('cardHolderName').value.trim();
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s+/g, '');
        const expiryMonth = document.getElementById('expiryMonth').value.trim();
        const expiryYear = document.getElementById('expiryYear').value.trim();
        const cvv = document.getElementById('cvv').value.trim();

        if (!cardHolderName || !cardNumber || !expiryMonth || !expiryYear || !cvv) {
            showNotification('Please enter your card details', 'error');
            return false;
        }

        if (!/^[0-9]{16}$/.test(cardNumber)) {
            showNotification('Please enter a valid 16-digit card number', 'error');
            return false;
        }

        if (!/^(0[1-9]|1[0-2])$/.test(expiryMonth)) {
            showNotification('Please enter a valid expiry month', 'error');
            return false;
        }

        if (!/^[0-9]{2}$/.test(expiryYear)) {
            showNotification('Please enter a valid expiry year', 'error');
            return false;
        }

        const expiryDate = new Date(`20${expiryYear}`, parseInt(expiryMonth, 10) - 1, 1);
        const today = new Date();
        if (expiryDate < new Date(today.getFullYear(), today.getMonth(), 1)) {
            showNotification('Your card has expired', 'error');
            return false;
        }

        if (!/^[0-9]{3,4}$/.test(cvv)) {
            showNotification('Please enter a valid CVV', 'error');
            return false;
        }
    }

    if (paymentMethod === 'jazzcash' || paymentMethod === 'easypaisa') {
        const mobileInput = document.getElementById(`${paymentMethod}Mobile`);
        const txnInput = document.getElementById(`${paymentMethod}Txn`);
        const mobile = mobileInput ? mobileInput.value.trim() : '';
        const txn = txnInput ? txnInput.value.trim() : '';

        if (!mobile || !txn) {
            showNotification('Please enter your mobile number and transaction ID', 'error');
            return false;
        }

        if (!/^(\+92|0)?[0-9]{10}$/.test(mobile)) {
            showNotification('Please enter a valid Pakistan mobile number', 'error');
            return false;
        }
    }

    return true;
}

function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@\"]+(\.[^<>()\[\]\\.,;:\s@\"]+)*)|(".+"))@(([^<>()[\]\\.,;:\s@\"]+\.)+[^<>()[\]\\.,;:\s@\"]{2,})$/i;
    return re.test(String(email).toLowerCase());
}

function updateConfirmation() {
    const confirmAddressEl = document.getElementById('confirmAddress');
    if (selectedAddress) {
        confirmAddressEl.innerHTML = '<p>Using selected saved address.</p>';
    } else {
        const fullName = document.getElementById('fullName').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const address = document.getElementById('address').value.trim();
        const isIslamabad = isIslamabadCheckbox && isIslamabadCheckbox.checked;
        const city = isIslamabad ? 'Islamabad' : (citySelect ? citySelect.value : '');
        const province = isIslamabad ? 'Islamabad' : (provinceSelect ? provinceSelect.value : '');
        
        confirmAddressEl.innerHTML = `
            <p><strong>${fullName}</strong></p>
            <p>${phone}</p>
            <p>${address}${city ? ', ' + city : ''}${province ? ', ' + province : ''}</p>
        `;
    }
    
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const paymentLabels = {
        'cod': 'Cash on Delivery',
        'card': 'Credit/Debit Card',
        'jazzcash': 'JazzCash',
        'easypaisa': 'EasyPaisa'
    };
    document.getElementById('confirmPayment').textContent = paymentLabels[paymentMethod];
}

// Coupon handling
const applyCouponBtn = document.getElementById('applyCoupon');
if (applyCouponBtn) {
    applyCouponBtn.addEventListener('click', function() {
        const couponCodeInput = document.getElementById('couponCode');
        const couponCode = couponCodeInput ? couponCodeInput.value.trim() : '';
        if (couponCode) {
            const formData = new FormData();
            formData.append('coupon_code', couponCode);
            formData.append('csrf_token', getCsrfToken());
            
            fetch('api/cart.php?action=apply_coupon', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Coupon applied successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Invalid coupon', 'error');
                }
            });
        }
    });
}

const removeCouponBtn = document.getElementById('removeCoupon');
if (removeCouponBtn) {
    removeCouponBtn.addEventListener('click', function() {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());
        
        fetch('api/cart.php?action=remove_coupon', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Coupon removed', 'success');
                setTimeout(() => location.reload(), 1500);
            }
        });
    });
}

// Form submission
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!document.getElementById('termsCheck').checked) {
        showNotification('Please agree to the Terms & Conditions', 'error');
        return;
    }
    
    const submitBtn = document.getElementById('placeOrderBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
    
    const formData = new FormData(this);
    
    if (selectedAddress) {
        formData.append('address_id', selectedAddress);
    }
    
    fetch('api/checkout.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Order placed successfully!', 'success');
            setTimeout(() => {
                window.location.href = 'order-success.php?order_id=' + data.order_id;
            }, 1500);
        } else {
            showNotification(data.message || 'Failed to place order', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i> Place Order';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i> Place Order';
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
