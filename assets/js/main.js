/**
 * Naeem Electronic - Main JavaScript
 * Handles all frontend interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initTopBar();
    initNavbar();
    initHeroSlider();
    initCountdown();
    initProductFilters();
    initCart();
    initWishlist();
    initQuickView();
    initQuickViewAddToCart();
    initSearch();
    initBackToTop();
    initMobileMenu();
});

function initQuickView() {
    const quickViewButtons = document.querySelectorAll('.quick-view-btn');
    quickViewButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            if (!productId) return;
            fetchQuickView(productId);
        });
    });
}

function fetchQuickView(productId) {
    fetch(`api/product.php?action=quick_view&id=${encodeURIComponent(productId)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showNotification(data.message || 'Product not found', 'error');
                return;
            }

            const product = data.product;
            const modal = document.getElementById('quickViewModal');
            if (!modal) return;

            document.getElementById('quickViewTitle').textContent = product.name;
            document.getElementById('quickViewImage').src = product.image;
            document.getElementById('quickViewImage').alt = product.name;
            document.getElementById('quickViewPrice').textContent = product.price;
            document.getElementById('quickViewStock').textContent = product.stock_status;
            document.getElementById('quickViewDescription').textContent = product.description;
            document.getElementById('quickViewSku').textContent = product.sku;

            const addToCartModalBtn = document.getElementById('quickViewAddCart');
            if (addToCartModalBtn) {
                addToCartModalBtn.dataset.productId = productId;
            }

            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        })
        .catch(error => {
            console.error('Quick view error:', error);
            showNotification('Unable to load product details', 'error');
        });
}

function initQuickViewAddToCart() {
    const addToCartModalBtn = document.getElementById('quickViewAddCart');
    if (!addToCartModalBtn) return;

    addToCartModalBtn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        if (productId) {
            addToCart(productId);
            const modal = document.getElementById('quickViewModal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
    });
}

// ===== Top Bar =====
function initTopBar() {
    const closeBtn = document.querySelector('.top-bar-close');
    const topBar = document.querySelector('.top-bar');
    
    if (closeBtn && topBar) {
        closeBtn.addEventListener('click', function() {
            topBar.style.display = 'none';
            // Save preference to localStorage
            localStorage.setItem('topBarClosed', 'true');
        });
        
        // Check if user previously closed the top bar
        if (localStorage.getItem('topBarClosed') === 'true') {
            topBar.style.display = 'none';
        }
    }
}

// ===== Navbar =====
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    const searchBtn = document.querySelector('.search-btn');
    const searchContainer = document.querySelector('.search-container');
    
    // Sticky navbar effect
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
            } else {
                navbar.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            }
        });
    }
    
    // Search toggle
    if (searchBtn && searchContainer) {
        searchBtn.addEventListener('click', function() {
            searchContainer.classList.toggle('active');
            if (searchContainer.classList.contains('active')) {
                searchContainer.querySelector('.search-input').focus();
            }
        });
        
        // Close search when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchContainer.contains(e.target) && !searchBtn.contains(e.target)) {
                searchContainer.classList.remove('active');
            }
        });
    }
}

// ===== Hero Slider =====
function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    let currentSlide = 0;
    let slideInterval;
    
    if (slides.length === 0) return;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.remove('active');
            dots[i].classList.remove('active');
        });
        
        slides[index].classList.add('active');
        dots[index].classList.add('active');
        currentSlide = index;
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    function startAutoSlide() {
        slideInterval = setInterval(nextSlide, 5000);
    }
    
    function stopAutoSlide() {
        clearInterval(slideInterval);
    }
    
    // Initialize first slide
    showSlide(0);
    startAutoSlide();
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            stopAutoSlide();
            showSlide(index);
            startAutoSlide();
        });
    });
    
    // Pause on hover
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.addEventListener('mouseenter', stopAutoSlide);
        heroSection.addEventListener('mouseleave', startAutoSlide);
    }
}

// ===== Countdown Timer =====
function initCountdown() {
    const countdownElements = document.querySelectorAll('.countdown-timer');
    
    countdownElements.forEach(countdown => {
        // Set end time to 24 hours from now
        const endTime = new Date();
        endTime.setHours(endTime.getHours() + 24);
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endTime.getTime() - now;
            
            if (distance < 0) {
                // Reset countdown
                endTime.setHours(endTime.getHours() + 24);
                return;
            }
            
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            const hoursEl = countdown.querySelector('.countdown-hours .countdown-value');
            const minutesEl = countdown.querySelector('.countdown-minutes .countdown-value');
            const secondsEl = countdown.querySelector('.countdown-seconds .countdown-value');
            
            if (hoursEl) hoursEl.textContent = hours.toString().padStart(2, '0');
            if (minutesEl) minutesEl.textContent = minutes.toString().padStart(2, '0');
            if (secondsEl) secondsEl.textContent = seconds.toString().padStart(2, '0');
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    });
}

// ===== Product Filters =====
function initProductFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            productCards.forEach(card => {
                if (filter === 'all' || card.dataset.category === filter) {
                    card.style.display = 'block';
                    setTimeout(() => card.style.opacity = '1', 10);
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => card.style.display = 'none', 300);
                }
            });
        });
    });
}

// ===== Cart System =====
function initCart() {
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    const cartIcon = document.querySelector('.cart-icon');
    const cartBadge = document.querySelector('.nav-icon-badge');
    const cartSidebar = document.querySelector('.cart-sidebar');
    const cartClose = document.querySelector('.cart-close');
    const cartOverlay = document.querySelector('.cart-overlay');
    
    // Add to cart
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            addToCart(productId);
        });
    });
    
    // Open cart sidebar
    if (cartIcon) {
        cartIcon.addEventListener('click', function() {
            cartSidebar.classList.add('active');
            cartOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close cart sidebar
    if (cartClose) {
        cartClose.addEventListener('click', closeCart);
    }
    
    if (cartOverlay) {
        cartOverlay.addEventListener('click', closeCart);
    }
    
    function closeCart() {
        cartSidebar.classList.remove('active');
        cartOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Quantity adjustment inside the sidebar cart only
    const qtyBtns = document.querySelectorAll('.cart-sidebar .qty-btn');
    qtyBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const cartItemId = this.dataset.cartItemId;
            const action = this.dataset.action;
            updateCartItemQuantity(cartItemId, action);
        });
    });
    
    // Remove item from the sidebar cart only
    const removeBtns = document.querySelectorAll('.cart-sidebar .cart-remove-btn');
    removeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const cartItemId = this.dataset.cartItemId;
            if (confirm('Are you sure you want to remove this item?')) {
                removeCartItem(cartItemId);
            }
        });
    });
    
    // Apply coupon
    const couponBtn = document.querySelector('.coupon-apply-btn');
    if (couponBtn) {
        couponBtn.addEventListener('click', function() {
            const couponCode = document.querySelector('.coupon-input').value;
            applyCoupon(couponCode);
        });
    }
}

// AJAX: Add to cart
function addToCart(productId) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
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

// AJAX: Update cart item quantity
function updateCartItemQuantity(cartItemId, action) {
    const formData = new FormData();
    formData.append('cart_id', cartItemId);
    formData.append('action', action);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/cart.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data.cart);
            const cartCount = data.cart_count ?? (data.cart && data.cart.cart_count);
            if (cartCount !== undefined) {
                updateCartBadge(cartCount);
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// AJAX: Remove cart item
function removeCartItem(cartItemId) {
    const formData = new FormData();
    formData.append('cart_id', cartItemId);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('api/cart.php?action=remove', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data.cart);
            const cartCount = data.cart_count ?? (data.cart && data.cart.cart_count);
            if (cartCount !== undefined) {
                updateCartBadge(cartCount);
            }
            showNotification('Item removed from cart', 'success');
        }
    })
    .catch(error => console.error('Error:', error));
}

// AJAX: Apply coupon
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
        if (data.success) {
            updateCartDisplay(data.cart);
            showNotification('Coupon applied successfully!', 'success');
        } else {
            showNotification(data.message || 'Invalid coupon', 'error');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Update cart badge
function updateCartBadge(count) {
    const badge = document.querySelector('.nav-icon-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.animation = 'none';
        setTimeout(() => badge.style.animation = 'pulse 0.3s ease', 10);
    }
}

// Update cart display
function updateCartDisplay(cart) {
    const cartItems = document.querySelector('.cart-items');
    const cartSubtotal = document.querySelector('.cart-subtotal');
    const cartDiscount = document.querySelector('.cart-discount');
    const cartTotal = document.querySelector('.cart-total');
    
    if (cartItems && cart.items) {
        cartItems.innerHTML = cart.items.map(item => `
            <div class="cart-item" data-id="${item.id}">
                <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-details">
                    <h4 class="cart-item-name">${item.name}</h4>
                    <p class="cart-item-price">${item.price}</p>
                    <div class="cart-item-qty">
                        <button class="qty-btn" data-action="decrease" data-cart-item-id="${item.id}">-</button>
                        <span>${item.quantity}</span>
                        <button class="qty-btn" data-action="increase" data-cart-item-id="${item.id}">+</button>
                    </div>
                </div>
                <button class="cart-remove-btn" data-cart-item-id="${item.id}">&times;</button>
            </div>
        `).join('');
        
        // Re-initialize event listeners
        initCart();
    }
    
    if (cartSubtotal) cartSubtotal.textContent = cart.subtotal;
    if (cartDiscount) cartDiscount.textContent = cart.discount;
    if (cartTotal) cartTotal.textContent = cart.total;
}

// ===== Wishlist =====
function initWishlist() {
    const wishlistBtns = document.querySelectorAll('.wishlist-btn');
    
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            toggleWishlist(productId, this);
        });
    });
}

// AJAX: Toggle wishlist
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
            if (data.added) {
                btn.innerHTML = '<i class="fas fa-heart"></i>';
                showNotification('Added to wishlist!', 'success');
            } else {
                btn.innerHTML = '<i class="far fa-heart"></i>';
                showNotification('Removed from wishlist', 'success');
            }
            updateWishlistBadge(data.wishlist_count);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Update wishlist badge
function updateWishlistBadge(count) {
    const badge = document.querySelector('.wishlist-badge');
    if (badge) {
        badge.textContent = count;
    }
}

// ===== Search =====
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchResults = document.querySelector('.search-results');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                if (searchResults) searchResults.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
    }
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            if (searchResults) searchResults.style.display = 'none';
        }
    });
}

// AJAX: Perform search
function performSearch(query) {
    fetch(`api/search.php?q=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
        const searchResults = document.querySelector('.search-results');
        if (searchResults && data.results) {
            if (data.results.length > 0) {
                searchResults.innerHTML = data.results.map(product => `
                    <div class="search-result-item">
                        <img src="${product.image}" alt="${product.name}">
                        <div class="search-result-info">
                            <h4>${product.name}</h4>
                            <p>${product.price}</p>
                        </div>
                        <a href="product.php?id=${product.id}" class="search-result-link">View</a>
                    </div>
                `).join('');
                searchResults.style.display = 'block';
            } else {
                searchResults.innerHTML = '<p class="no-results">No products found</p>';
                searchResults.style.display = 'block';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// ===== Back to Top =====
function initBackToTop() {
    const backToTopBtn = document.querySelector('.back-to-top');
    
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });
        
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// ===== Mobile Menu =====
function initMobileMenu() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('#mobileMenu');
    const mobileMenuClose = document.querySelector('#closeMobileMenu');
    const mobileMenuOverlay = document.querySelector('#mobileMenuOverlay');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            if (mobileMenuOverlay) mobileMenuOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    if (mobileMenuClose && mobileMenu) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
    }
    
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    }
    
    function closeMobileMenu() {
        if (mobileMenu) mobileMenu.classList.remove('active');
        if (mobileMenuOverlay) mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Mobile dropdown toggles
    const mobileDropdownToggles = document.querySelectorAll('.mobile-dropdown-toggle');
    mobileDropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.nextElementSibling;
            if (dropdown) {
                dropdown.classList.toggle('active');
                this.classList.toggle('active');
            }
        });
    });
}

// ===== Helper Functions =====

// Get CSRF token from meta tag
function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : '';
}

// Show notification
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => n.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', function() {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
}

// Format price
function formatPrice(price) {
    return 'Rs. ' + parseFloat(price).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}
