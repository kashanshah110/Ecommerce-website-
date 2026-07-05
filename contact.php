<?php
/**
 * Naeem Electronic - Contact Us Page
 * Contact form and company information
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Contact Us';
$page_description = 'Get in touch with Naeem Electronic';
$current_page = 'contact';

$db = new Database();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrf_token)) {
        setFlash('error', 'Invalid request');
    } elseif (empty($name) || empty($email) || empty($subject) || empty($message)) {
        setFlash('error', 'Please fill in all required fields');
    } elseif (!isValidEmail($email)) {
        setFlash('error', 'Please enter a valid email address');
    } else {
        // Insert into contacts table
        $db->query("INSERT INTO contacts (name, email, phone, subject, message) 
                   VALUES (:name, :email, :phone, :subject, :message)");
        $db->bind(':name', $name);
        $db->bind(':email', $email);
        $db->bind(':phone', $phone);
        $db->bind(':subject', $subject);
        $db->bind(':message', $message);
        
        if ($db->execute()) {
            // Send email notification (in production)
            // sendEmail(SITE_EMAIL, 'New Contact Form Submission', "Name: $name\nEmail: $email\nSubject: $subject\nMessage: $message");
            
            setFlash('success', 'Thank you for contacting us! We will get back to you soon.');
        } else {
            setFlash('error', 'Failed to submit your message. Please try again.');
        }
    }
}

// Get site settings
$site_name = getSetting('site_name', SITE_NAME);
$site_email = getSetting('site_email', SITE_EMAIL);
$site_phone = getSetting('site_phone', SITE_PHONE);
$site_address = getSetting('site_address', SITE_ADDRESS);

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
    <div class="container">
        <h1 class="mb-2">Contact Us</h1>
        <p class="mb-0">We'd love to hear from you. Get in touch with us!</p>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section py-5">
    <div class="container">
        <div class="row">
            <!-- Contact Information -->
            <div class="col-lg-4 mb-4">
                <div class="contact-info-card">
                    <h3 class="mb-4">Contact Information</h3>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Address</h4>
                            <p><?php echo $site_address; ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Phone</h4>
                            <p><a href="tel:<?php echo $site_phone; ?>"><?php echo $site_phone; ?></a></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Email</h4>
                            <p><a href="mailto:<?php echo $site_email; ?>"><?php echo $site_email; ?></a></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Business Hours</h4>
                            <p>Monday - Saturday: 9:00 AM - 10:00 PM</p>
                            <p>Sunday: 10:00 AM - 8:00 PM</p>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="social-links mt-4">
                        <h4>Follow Us</h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="col-lg-8 mb-4">
                <div class="contact-form-card">
                    <h3 class="mb-4">Send us a Message</h3>
                    
                    <form action="contact.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="general" <?php echo isset($_POST['subject']) && $_POST['subject'] === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="order" <?php echo isset($_POST['subject']) && $_POST['subject'] === 'order' ? 'selected' : ''; ?>>Order Related</option>
                                <option value="product" <?php echo isset($_POST['subject']) && $_POST['subject'] === 'product' ? 'selected' : ''; ?>>Product Information</option>
                                <option value="support" <?php echo isset($_POST['subject']) && $_POST['subject'] === 'support' ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="complaint" <?php echo isset($_POST['subject']) && $_POST['subject'] === 'complaint' ? 'selected' : ''; ?>>Complaint</option>
                                <option value="other" <?php echo isset($_POST['subject']) && $_POST['subject'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required
                                      placeholder="Write your message here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Map -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="map-card">
                    <h3 class="mb-4">Find Us on Map</h3>
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3324.5!2d73.0!3d33.5!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzPCsDMwJzAwLjAiTiA3M8KwMDAnMDAuMCJF!5e0!4m5!1s0x0%3A0x0!7z!5e0!3m2!1sen!2s!4v1"
                            width="100%" 
                            height="400" 
                            style="border:0; border-radius: 8px;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="faq-card">
                    <h3 class="mb-4 text-center">Frequently Asked Questions</h3>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What payment methods do you accept?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We accept Cash on Delivery (COD), Credit/Debit Cards, JazzCash, and EasyPaisa. All online payments are processed securely.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    How long does delivery take?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Standard delivery takes 3-5 business days for major cities and 5-7 business days for other areas. Express delivery options are also available.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We offer a 7-day return policy for most products. Items must be in their original condition with all accessories and packaging.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Do you offer warranty on products?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, all our products come with manufacturer warranty ranging from 1-3 years depending on the product. Extended warranty options are also available.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    How can I track my order?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Once your order is shipped, you will receive a tracking number via email and SMS. You can also track your order from your dashboard.
                                </div>
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

.contact-info-card,
.contact-form-card,
.map-card,
.faq-card {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 25px;
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #1A237E, #FF6F00);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    margin-right: 20px;
    flex-shrink: 0;
}

.contact-details h4 {
    font-size: 18px;
    margin-bottom: 5px;
    color: #1A237E;
}

.contact-details p {
    margin: 0;
    color: #6c757d;
}

.contact-details a {
    color: #6c757d;
    text-decoration: none;
}

.contact-details a:hover {
    color: #FF6F00;
}

.social-icons {
    display: flex;
    gap: 15px;
    margin-top: 15px;
}

.social-icon {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1A237E;
    transition: all 0.3s;
}

.social-icon:hover {
    background: #FF6F00;
    color: white;
}

.map-container {
    border-radius: 8px;
    overflow: hidden;
}

.accordion-button {
    font-weight: 600;
}

.accordion-button:not(.collapsed) {
    background-color: #fff8f0;
    color: #FF6F00;
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 111, 0, 0.25);
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
