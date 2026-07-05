<?php
/**
 * Naeem Electronic - About Us Page
 * Company information and story
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'About Us';
$page_description = 'Learn about Naeem Electronic';
$current_page = 'about';

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">About Us</h1>
        <p class="mb-0">Your Trusted Home Appliance Partner Since 2010</p>
    </div>
</section>

<!-- About Section -->
<section class="about-section py-5">
    <div class="container">
        <!-- Our Story -->
        <div class="row mb-5">
            <div class="col-lg-6 mb-4">
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800" alt="Our Store" class="img-fluid rounded">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="about-content">
                    <h2 class="mb-4">Our Story</h2>
                    <p class="lead">Naeem Electronic has been serving Pakistan's home appliance needs for over a decade.</p>
                    <p>Founded in 2010, we started as a small electronics shop with a simple mission: to provide genuine, high-quality home appliances at affordable prices. Today, we have grown into one of Pakistan's most trusted names in the home appliance industry.</p>
                    <p>Our commitment to quality, customer satisfaction, and after-sales service has earned us the trust of thousands of customers across the country. We believe that every Pakistani deserves access to reliable, durable, and affordable home appliances.</p>
                    <p>From kitchen appliances to laundry solutions, from cooling systems to entertainment devices, we offer a comprehensive range of products from top international brands. Our team of experts carefully selects each product to ensure it meets our strict quality standards.</p>
                </div>
            </div>
        </div>
        
        <!-- Mission & Vision -->
        <div class="row mb-5">
            <div class="col-md-6 mb-4">
                <div class="mission-vision-card">
                    <div class="card-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p>To provide Pakistani households with genuine, high-quality home appliances at competitive prices while ensuring exceptional customer service and after-sales support. We strive to make every customer's experience with us memorable and satisfying.</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="mission-vision-card">
                    <div class="card-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p>To become Pakistan's leading home appliance retailer, known for quality, reliability, and customer trust. We aim to expand our reach to every corner of Pakistan while maintaining our commitment to excellence and customer satisfaction.</p>
                </div>
            </div>
        </div>
        
        <!-- Our Values -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-5">Our Core Values</h2>
            </div>
            <div class="col-md-3 mb-4">
                <div class="value-card text-center">
                    <div class="value-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h4>Quality</h4>
                    <p>We only sell genuine products from authorized dealers with manufacturer warranty.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="value-card text-center">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4>Trust</h4>
                    <p>Building lasting relationships with our customers through honesty and transparency.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="value-card text-center">
                    <div class="value-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4>Service</h4>
                    <p>Dedicated customer support and after-sales service to ensure your satisfaction.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="value-card text-center">
                    <div class="value-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h4>Affordability</h4>
                    <p>Competitive pricing with regular discounts and special offers for our customers.</p>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="stats-card">
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="stat-item text-center">
                                <div class="stat-number">10+</div>
                                <div class="stat-label">Years in Business</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="stat-item text-center">
                                <div class="stat-number">50K+</div>
                                <div class="stat-label">Happy Customers</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="stat-item text-center">
                                <div class="stat-number">500+</div>
                                <div class="stat-label">Products</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="stat-item text-center">
                                <div class="stat-number">50+</div>
                                <div class="stat-label">Brands</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Why Choose Us -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-5">Why Choose Naeem Electronic?</h2>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>100% Genuine Products</h4>
                    <p>We source all our products directly from authorized distributors and manufacturers, ensuring authenticity and quality.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Manufacturer Warranty</h4>
                    <p>All products come with official manufacturer warranty, giving you peace of mind with every purchase.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h4>Free Delivery</h4>
                    <p>Enjoy free delivery on orders above Rs. 5,000 across Pakistan with secure packaging and timely delivery.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <h4>Easy Returns</h4>
                    <p>Hassle-free 7-day return policy for most products. We believe in making your shopping experience worry-free.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h4>Expert Support</h4>
                    <p>Our team of experts is available to help you choose the right product and provide technical support.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <h4>Best Prices</h4>
                    <p>We offer competitive pricing with regular discounts and special offers to give you the best value.</p>
                </div>
            </div>
        </div>
        
        <!-- Our Team -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-5">Meet Our Team</h2>
            </div>
            <div class="col-md-3 mb-4">
                <div class="team-card text-center">
                    <div class="team-avatar">
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                    <h4>Naeem Ahmed</h4>
                    <p class="text-muted">Founder & CEO</p>
                    <p class="small">Leading the company with vision and passion for excellence.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="team-card text-center">
                    <div class="team-avatar">
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                    <h4>Sarah Khan</h4>
                    <p class="text-muted">Operations Manager</p>
                    <p class="small">Ensuring smooth operations and customer satisfaction.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="team-card text-center">
                    <div class="team-avatar">
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                    <h4>Ahmed Ali</h4>
                    <p class="text-muted">Sales Manager</p>
                    <p class="small">Expert in helping customers find the perfect appliances.</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="team-card text-center">
                    <div class="team-avatar">
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                    <h4>Fatima Zaidi</h4>
                    <p class="text-muted">Customer Support Lead</p>
                    <p class="small">Dedicated to providing exceptional customer service.</p>
                </div>
            </div>
        </div>
        
        <!-- CTA -->
        <div class="row">
            <div class="col-12">
                <div class="cta-card text-center">
                    <h2 class="mb-3">Ready to Experience the Difference?</h2>
                    <p class="mb-4">Join thousands of satisfied customers who trust Naeem Electronic for their home appliance needs.</p>
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i> Shop Now
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg ms-3">
                        <i class="fas fa-phone me-2"></i> Contact Us
                    </a>
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

.about-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 8px;
}

.about-content h2 {
    color: #1A237E;
    margin-bottom: 20px;
}

.mission-vision-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: 100%;
}

.card-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    margin-bottom: 20px;
}

.mission-vision-card h3 {
    color: #1A237E;
    margin-bottom: 15px;
}

.value-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.value-card:hover {
    transform: translateY(-10px);
}

.value-icon {
    width: 50px;
    height: 50px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FF6F00;
    font-size: 24px;
    margin: 0 auto 15px;
}

.value-card h4 {
    color: #1A237E;
    margin-bottom: 10px;
}

.stats-card {
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    padding: 40px;
    border-radius: 8px;
    color: white;
}

.stat-number {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 18px;
    opacity: 0.9;
}

.feature-item {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: 100%;
}

.feature-icon {
    width: 50px;
    height: 50px;
    background: #fff8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FF6F00;
    font-size: 24px;
    margin-bottom: 15px;
}

.feature-item h4 {
    color: #1A237E;
    margin-bottom: 10px;
}

.team-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.team-card:hover {
    transform: translateY(-5px);
}

.team-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin: 0 auto 15px;
}

.team-card h4 {
    color: #1A237E;
    margin-bottom: 5px;
}

.cta-card {
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    padding: 50px;
    border-radius: 8px;
    color: white;
}

.cta-card h2 {
    margin-bottom: 20px;
}

.cta-card .btn-outline-light {
    border-color: white;
    color: white;
}

.cta-card .btn-outline-light:hover {
    background: white;
    color: #1A237E;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
