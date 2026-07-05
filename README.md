# Naeem Electronic - E-Commerce Website

A complete, modern e-commerce website for home appliances built with PHP, MySQL, and Bootstrap 5.

## Features

### Frontend
- **Responsive Design**: Mobile-first design that works on all devices
- **Modern UI**: Clean, attractive interface with Bootstrap 5
- **Hero Slider**: Auto-sliding image carousel with animations
- **Product Catalog**: Browse products with filters and sorting
- **Product Details**: Detailed product pages with image galleries
- **Shopping Cart**: AJAX-powered cart with real-time updates
- **Multi-step Checkout**: Secure checkout process with multiple payment options
- **User Dashboard**: Order tracking, wishlist, and account management
- **Live Search**: Instant product search with autocomplete
- **Wishlist**: Save favorite products for later

### Backend
- **Admin Panel**: Complete admin dashboard for managing the store
- **Product Management**: Add, edit, delete products with images
- **Order Management**: Track and manage orders with status updates
- **User Management**: Manage users and their roles
- **Category Management**: Organize products into categories
- **Coupon System**: Create and manage discount coupons
- **Reports**: Sales and performance analytics
- **Settings**: Configure site-wide settings

### Security
- **CSRF Protection**: Cross-site request forgery prevention
- **XSS Prevention**: Cross-site scripting protection
- **SQL Injection Prevention**: Prepared statements and input validation
- **Password Hashing**: Secure password storage with bcrypt
- **Session Security**: Secure session management
- **Rate Limiting**: Protection against brute force attacks
- **File Upload Security**: Secure file upload validation

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5, jQuery, Font Awesome
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache or Nginx

## Installation

### Prerequisites

- XAMPP, WAMP, or any PHP/MySQL server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (optional, for dependency management)

### Step 1: Clone/Download the Project

```bash
# If using Git
git clone https://github.com/yourusername/naeem-electronic.git
cd naeem-electronic

# Or download and extract the zip file to your web server directory
```

### Step 2: Configure Database

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `naeem_electronic`
3. Import the `database.sql` file located in the project root

```bash
# Or use command line
mysql -u root -p naeem_electronic < database.sql
```

### Step 3: Configure Application

Edit the `config/config.php` file and update the following settings:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'naeem_electronic');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_URL', 'http://localhost/Naeem');
define('SITE_NAME', 'Naeem Electronic');
define('SITE_EMAIL', 'info@naeemelectronic.com');
define('SITE_PHONE', '+92-300-1234567');
define('SITE_ADDRESS', 'Islamabad, Pakistan');
```

### Step 4: Set File Permissions

Ensure the following directories have write permissions:

```bash
# On Windows (XAMPP), permissions are usually set automatically
# On Linux/Mac, run:
chmod 755 uploads/
chmod 755 config/
```

### Step 5: Access the Website

Open your browser and navigate to:
```
http://localhost/Naeem
```

### Step 6: Admin Access

Default admin credentials:
- **Email**: admin@naeemelectronic.com
- **Password**: admin123

**Important**: Change the admin password immediately after first login!

Access admin panel at:
```
http://localhost/Naeem/admin
```

## Project Structure

```
naeem-electronic/
├── admin/                  # Admin panel
│   ├── index.php          # Admin dashboard
│   ├── products.php       # Product management
│   ├── orders.php         # Order management
│   └── users.php          # User management
├── api/                    # API endpoints
│   ├── cart.php           # Cart operations
│   ├── wishlist.php       # Wishlist operations
│   └── search.php         # Live search
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css       # Main stylesheet
│   ├── js/
│   │   └── main.js         # Main JavaScript
│   └── images/             # Images
├── config/                 # Configuration files
│   ├── config.php          # Main configuration
│   └── database.php       # Database connection
├── includes/               # PHP includes
│   ├── header.php         # Header with navigation
│   ├── footer.php         # Footer
│   ├── functions.php      # Helper functions
│   └── security.php       # Security middleware
├── uploads/                # User uploads
├── index.php              # Homepage
├── products.php           # Products listing
├── product.php            # Product details
├── cart.php               # Shopping cart
├── checkout.php           # Checkout
├── login.php              # Login
├── register.php           # Registration
├── dashboard.php          # User dashboard
├── contact.php            # Contact form
├── about.php              # About us
└── database.sql           # Database schema
```

## Database Schema

The project includes the following tables:

- `users` - User accounts and authentication
- `products` - Product information
- `categories` - Product categories
- `subcategories` - Product subcategories
- `orders` - Order information
- `order_items` - Order line items
- `cart` - Shopping cart items
- `wishlist` - User wishlist
- `reviews` - Product reviews
- `coupons` - Discount coupons
- `coupon_usage` - Coupon usage tracking
- `addresses` - User addresses
- `payments` - Payment information
- `blogs` - Blog posts
- `blog_comments` - Blog comments
- `contacts` - Contact form submissions
- `settings` - Site settings
- `banners` - Hero slider banners
- `product_images` - Product images
- `product_specifications` - Product specifications
- `brand` - Brand information
- `shipping` - Shipping rates

## Configuration Options

### Email Configuration

To enable email functionality, configure SMTP settings in your PHP environment or use a service like SendGrid/Mailgun.

### Payment Integration

The project supports multiple payment methods:
- Cash on Delivery (COD) - Built-in
- Credit/Debit Cards - Requires payment gateway integration
- JazzCash - Requires API integration
- EasyPaisa - Requires API integration

### CDN Configuration

To use a CDN for static assets, update the paths in `config/config.php`.

## Development

### Adding New Pages

1. Create the PHP file in the root directory
2. Include the header: `require_once __DIR__ . '/includes/header.php';`
3. Include the footer: `require_once __DIR__ . '/includes/footer.php';`
4. Set page title and description variables before including header

### Adding New API Endpoints

1. Create the PHP file in the `api/` directory
2. Include required files: `config/config.php`, `config/database.php`, `includes/functions.php`
3. Set JSON content-type header
4. Return JSON responses

### Adding New Admin Pages

1. Create the PHP file in the `admin/` directory
2. Include admin authentication check
3. Use the admin sidebar template from existing pages

## Security Best Practices

1. **Always use prepared statements** for database queries
2. **Validate and sanitize all user input**
3. **Use CSRF tokens** on all forms
4. **Hash passwords** using bcrypt
5. **Set secure session cookies** in production
6. **Enable HTTPS** in production
7. **Keep dependencies updated**
8. **Regular security audits**

## Troubleshooting

### Database Connection Issues

If you get a database connection error:
1. Check MySQL server is running
2. Verify database credentials in `config/config.php`
3. Ensure the database exists and was imported correctly

### File Upload Issues

If file uploads fail:
1. Check `uploads/` directory has write permissions
2. Verify PHP upload settings in `php.ini`:
   - `upload_max_filesize`
   - `post_max_size`
   - `file_uploads`

### Session Issues

If sessions aren't working:
1. Check `session.save_path` in `php.ini`
2. Ensure the directory has write permissions
3. Verify session cookie settings

## Deployment

### Production Deployment

1. **Update Configuration**:
   - Change database credentials
   - Update `SITE_URL` to production domain
   - Enable HTTPS in `includes/security.php`

2. **Environment Variables**:
   - Set environment variables for sensitive data
   - Remove debug mode in production

3. **Web Server Configuration**:
   - Configure Apache/Nginx virtual host
   - Enable HTTPS with SSL certificate
   - Set up proper file permissions

4. **Database Backup**:
   - Set up automated database backups
   - Configure backup retention policy

5. **Monitoring**:
   - Set up error logging
   - Monitor server resources
   - Set up uptime monitoring

## Support

For support, contact:
- Email: info@naeemelectronic.com
- Phone: +92-300-1234567

## License

This project is proprietary software. All rights reserved.

## Credits

- Developed by: Naeem Electronic Team
- Framework: Bootstrap 5
- Icons: Font Awesome
- Fonts: Google Fonts (Poppins, Roboto)

## Changelog

### Version 1.0.0 (2026)
- Initial release
- Complete e-commerce functionality
- Admin panel
- User authentication
- Shopping cart and checkout
- Product management
- Order management
