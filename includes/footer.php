<?php
/**
 * Naeem Electronic - Footer Include
 * Contains footer with all sections
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Get settings
$site_name = getSetting('site_name', SITE_NAME);
$site_email = getSetting('site_email', SITE_EMAIL);
$site_phone = getSetting('site_phone', SITE_PHONE);
$site_address = getSetting('site_address', SITE_ADDRESS);

$social_facebook = getSetting('social_facebook', '');
$social_instagram = getSetting('social_instagram', '');
$social_youtube = getSetting('social_youtube', '');
$social_twitter = getSetting('social_twitter', '');

// Get categories for footer
$db = new Database();
$db->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order LIMIT 6");
$footer_categories = $db->fetchAll();
?>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Column 1: Brand Info -->
            <div class="footer-col">
                <div class="footer-brand">
                    <h3><i class="fas fa-bolt"></i> <?php echo $site_name; ?></h3>
                    <p class="footer-tagline">Your Trusted Home Appliance Partner</p>
                </div>
                <p class="footer-description">
                    We offer premium quality home appliances at competitive prices. With years of experience and thousands of satisfied customers, we are your go-to destination for all your appliance needs.
                </p>
                <div class="social-icons">
                    <?php if ($social_facebook): ?>
                        <a href="<?php echo $social_facebook; ?>" target="_blank" class="social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($social_instagram): ?>
                        <a href="<?php echo $social_instagram; ?>" target="_blank" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($social_youtube): ?>
                        <a href="<?php echo $social_youtube; ?>" target="_blank" class="social-icon">
                            <i class="fab fa-youtube"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($social_twitter): ?>
                        <a href="<?php echo $social_twitter; ?>" target="_blank" class="social-icon">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Column 2: Quick Links -->
            <div class="footer-col">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/about.php">About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/terms-conditions.php">Terms & Conditions</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/return-policy.php">Return Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/faq.php">FAQs</a></li>
                </ul>
            </div>
            
            <!-- Column 3: Categories -->
            <div class="footer-col">
                <h4 class="footer-title">Categories</h4>
                <ul class="footer-links">
                    <?php foreach ($footer_categories as $category): ?>
                        <li><a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['slug']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?php echo SITE_URL; ?>/products.php">See All Products <i class="fas fa-arrow-right"></i></a></li>
                </ul>
            </div>
            
            <!-- Column 4: Contact Info -->
            <div class="footer-col">
                <h4 class="footer-title">Contact Info</h4>
                <div class="footer-contact-item">
                    <i class="fas fa-phone"></i>
                    <span>
                        <a href="tel:<?php echo $site_phone; ?>"><?php echo $site_phone; ?></a>
                    </span>
                </div>
                <div class="footer-contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>
                        <a href="mailto:<?php echo $site_email; ?>"><?php echo $site_email; ?></a>
                    </span>
                </div>
                <div class="footer-contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo $site_address; ?></span>
                </div>
                <div class="footer-contact-item">
                    <i class="fas fa-clock"></i>
                    <span>Mon-Sun: 9AM - 10PM</span>
                </div>
            </div>
            
            <!-- Column 5: Newsletter -->
            <div class="footer-col">
                <h4 class="footer-title">Newsletter</h4>
                <p class="footer-description">Subscribe for exclusive deals and get 10% off on your first order!</p>
                <form class="newsletter-form" action="<?php echo SITE_URL; ?>/api/newsletter.php" method="POST">
                    <input type="email" name="email" class="newsletter-input" placeholder="Enter your email" required>
                    <button type="submit" class="newsletter-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. All Rights Reserved.</p>
            <div class="payment-methods">
                <i class="fab fa-cc-visa payment-method"></i>
                <i class="fab fa-cc-mastercard payment-method"></i>
                <i class="fab fa-cc-paypal payment-method"></i>
                <i class="fas fa-money-bill-wave payment-method"></i>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Cart Sidebar -->
<div class="cart-sidebar-overlay cart-overlay"></div>
<div class="cart-sidebar">
    <div class="cart-header">
        <h3>Shopping Cart</h3>
        <button class="cart-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="cart-body">
        <div class="cart-items">
            <!-- Cart items will be loaded dynamically -->
            <p class="text-center py-4">Your cart is empty</p>
        </div>
    </div>
    <div class="cart-footer">
        <div class="cart-summary">
            <div class="cart-summary-row">
                <span>Subtotal:</span>
                <span class="cart-subtotal">Rs. 0.00</span>
            </div>
            <div class="cart-summary-row">
                <span>Discount:</span>
                <span class="cart-discount">-Rs. 0.00</span>
            </div>
            <div class="cart-summary-row cart-total-row">
                <span>Total:</span>
                <span class="cart-total">Rs. 0.00</span>
            </div>
        </div>
        <div class="cart-coupon">
            <input type="text" class="coupon-input" placeholder="Enter coupon code">
            <button class="coupon-apply-btn">Apply</button>
        </div>
        <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
    </div>
</div>

<!-- Search Results Dropdown -->
<div class="search-results" style="display: none;"></div>

<!-- Notification Container -->
<div class="notification-container"></div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickViewTitle">Product Quick View</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 text-center">
                        <img id="quickViewImage" src="" alt="" class="img-fluid rounded mb-3">
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Price:</strong> <span id="quickViewPrice"></span></p>
                        <p class="mb-2"><strong>SKU:</strong> <span id="quickViewSku"></span></p>
                        <p class="mb-2"><strong>Status:</strong> <span id="quickViewStock"></span></p>
                        <p id="quickViewDescription" class="text-muted"></p>
                        <button class="btn btn-primary mt-3" id="quickViewAddCart" type="button">Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo ASSETS_PATH; ?>/js/main.js"></script>

</body>
</html>
