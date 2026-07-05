<?php
/**
 * Naeem Electronic - Cart API
 * Handles cart operations via AJAX
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        // Add item to cart
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        if ($product_id === 0 || $quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            exit;
        }
        
        // Check if product exists and is active
        $db->query("SELECT id, stock_quantity, stock_status FROM products WHERE id = :id AND is_active = 1");
        $db->bind(':id', $product_id);
        $product = $db->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        if ($product['stock_status'] === 'out_of_stock') {
            echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
            exit;
        }
        
        if ($quantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
            exit;
        }
        
        // Check if item already in cart
        $db->query("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $db->bind(':user_id', $user_id);
        $db->bind(':product_id', $product_id);
        $existing_item = $db->fetch();
        
        if ($existing_item) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            if ($new_quantity > $product['stock_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock']);
                exit;
            }
            
            $db->query("UPDATE cart SET quantity = :quantity WHERE id = :id");
            $db->bind(':quantity', $new_quantity);
            $db->bind(':id', $existing_item['id']);
            $db->execute();
        } else {
            // Add new item
            $db->query("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
            $db->bind(':user_id', $user_id);
            $db->bind(':product_id', $product_id);
            $db->bind(':quantity', $quantity);
            $db->execute();
        }
        
        // Get cart count
        $db->query("SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id");
        $db->bind(':user_id', $user_id);
        $count_result = $db->fetch();
        
        echo json_encode(['success' => true, 'message' => 'Product added to cart', 'cart_count' => $count_result['count']]);
        break;
        
    case 'update':
        // Update cart item quantity
        $cart_id = (int)($_POST['cart_id'] ?? 0);
        $action_type = $_POST['action'] ?? ''; // increase or decrease
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        if ($cart_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit;
        }
        
        // Get current cart item
        $db->query("SELECT c.quantity, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = :id AND c.user_id = :user_id");
        $db->bind(':id', $cart_id);
        $db->bind(':user_id', $user_id);
        $cart_item = $db->fetch();
        
        if (!$cart_item) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit;
        }
        
        $new_quantity = $cart_item['quantity'];
        
        if ($action_type === 'increase') {
            $new_quantity++;
        } elseif ($action_type === 'decrease') {
            $new_quantity--;
        } else {
            // Direct quantity update
            $new_quantity = (int)$_POST['quantity'] ?? 1;
        }
        
        if ($new_quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Quantity cannot be less than 1']);
            exit;
        }
        
        if ($new_quantity > $cart_item['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
            exit;
        }
        
        $db->query("UPDATE cart SET quantity = :quantity WHERE id = :id");
        $db->bind(':quantity', $new_quantity);
        $db->bind(':id', $cart_id);
        $db->execute();
        
        // Return updated cart data
        $cart_data = getCartData($db, $user_id);
        echo json_encode(['success' => true, 'cart' => $cart_data, 'cart_count' => $cart_data['cart_count']]);
        break;
        
    case 'remove':
        // Remove item from cart
        $cart_id = (int)($_POST['cart_id'] ?? 0);
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        if ($cart_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit;
        }
        
        $db->query("DELETE FROM cart WHERE id = :id AND user_id = :user_id");
        $db->bind(':id', $cart_id);
        $db->bind(':user_id', $user_id);
        $db->execute();
        
        $cart_data = getCartData($db, $user_id);
        echo json_encode(['success' => true, 'message' => 'Item removed from cart', 'cart' => $cart_data, 'cart_count' => $cart_data['cart_count']]);
        break;
        
    case 'apply_coupon':
        // Apply coupon code
        $coupon_code = sanitize($_POST['coupon_code'] ?? '');
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        if (empty($coupon_code)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
            exit;
        }
        
        // Get coupon details
        $db->query("SELECT * FROM coupons WHERE code = :code AND is_active = 1 
                    AND start_date <= NOW() AND end_date >= NOW()");
        $db->bind(':code', $coupon_code);
        $coupon = $db->fetch();
        
        if (!$coupon) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon']);
            exit;
        }
        
        // Check usage limits
        if ($coupon['total_usage_limit'] && $coupon['used_count'] >= $coupon['total_usage_limit']) {
            echo json_encode(['success' => false, 'message' => 'Coupon usage limit exceeded']);
            exit;
        }
        
        // Check user usage
        $db->query("SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = :coupon_id AND user_id = :user_id");
        $db->bind(':coupon_id', $coupon['id']);
        $db->bind(':user_id', $user_id);
        $usage_result = $db->fetch();
        
        if ($usage_result['count'] >= $coupon['usage_limit_per_user']) {
            echo json_encode(['success' => false, 'message' => 'You have already used this coupon']);
            exit;
        }
        
        // Check minimum order amount
        $cart_total = getCartTotal($db, $user_id);
        if ($cart_total < $coupon['min_order_amount']) {
            echo json_encode(['success' => false, 'message' => 'Minimum order amount is ' . formatPrice($coupon['min_order_amount'])]);
            exit;
        }
        
        // Store coupon in session
        $_SESSION['applied_coupon'] = $coupon_code;
        
        $cart_data = getCartData($db, $user_id);
        echo json_encode(['success' => true, 'message' => 'Coupon applied successfully', 'cart' => $cart_data, 'cart_count' => $cart_data['cart_count']]);
        break;
        
    case 'remove_coupon':
        // Remove applied coupon
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        unset($_SESSION['applied_coupon']);
        
        $cart_data = getCartData($db, $user_id);
        echo json_encode(['success' => true, 'message' => 'Coupon removed', 'cart' => $cart_data, 'cart_count' => $cart_data['cart_count']]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Helper function to get cart data
function getCartData($db, $user_id) {
    $db->query("SELECT c.id as cart_id, c.quantity, p.*, pi.image_path,
                CASE WHEN p.discount_price > 0 AND p.discount_price < p.price 
                     THEN p.discount_price ELSE p.price END as final_price
                FROM cart c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE c.user_id = :user_id AND p.is_active = 1");
    $db->bind(':user_id', $user_id);
    $cart_items = $db->fetchAll();
    
    $subtotal = 0;
    $total = 0;
    $discount = 0;
    
    $items = [];
    foreach ($cart_items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $item_final_total = $item['final_price'] * $item['quantity'];
        
        $subtotal += $item_total;
        $total += $item_final_total;
        $discount += ($item_total - $item_final_total);
        
        $items[] = [
            'id' => $item['cart_id'],
            'name' => $item['name'],
            'image' => UPLOADS_PATH . '/' . ($item['image_path'] ?? ''),
            'price' => formatPrice($item['final_price']),
            'quantity' => $item['quantity'],
            'total' => formatPrice($item_final_total)
        ];
    }
    
    // Apply coupon if exists
    $coupon = null;
    $coupon_discount = 0;
    if (isset($_SESSION['applied_coupon'])) {
        $coupon_code = sanitize($_SESSION['applied_coupon']);
        $db->query("SELECT * FROM coupons WHERE code = :code AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
        $db->bind(':code', $coupon_code);
        $coupon = $db->fetch();
        
        if ($coupon) {
            $db->query("SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = :coupon_id AND user_id = :user_id");
            $db->bind(':coupon_id', $coupon['id']);
            $db->bind(':user_id', $user_id);
            $usage_result = $db->fetch();

            if ($coupon['total_usage_limit'] && $coupon['used_count'] >= $coupon['total_usage_limit']) {
                unset($_SESSION['applied_coupon']);
                $coupon = null;
            } elseif ($usage_result['count'] >= $coupon['usage_limit_per_user']) {
                unset($_SESSION['applied_coupon']);
                $coupon = null;
            } elseif ($total < $coupon['min_order_amount']) {
                unset($_SESSION['applied_coupon']);
                $coupon = null;
            }
        } else {
            unset($_SESSION['applied_coupon']);
        }

        if ($coupon) {
            if ($coupon['discount_type'] === 'percentage') {
                $coupon_discount = ($total * $coupon['discount_value']) / 100;
            } else {
                $coupon_discount = $coupon['discount_value'];
            }
            
            if ($coupon['max_discount_amount'] && $coupon_discount > $coupon['max_discount_amount']) {
                $coupon_discount = $coupon['max_discount_amount'];
            }
            
            if ($coupon_discount > $total) {
                $coupon_discount = $total;
            }
            
            $total -= $coupon_discount;
            $discount += $coupon_discount;
        }
    }
    
    // Calculate shipping
    $free_shipping_threshold = getSetting('free_shipping_threshold', 5000);
    $shipping = $total >= $free_shipping_threshold ? 0 : getSetting('shipping_cost', 200);
    
    return [
        'items' => $items,
        'subtotal' => formatPrice($subtotal),
        'discount' => formatPrice($discount),
        'shipping' => formatPrice($shipping),
        'total' => formatPrice($total + $shipping),
        'cart_count' => count($items),
        'applied_coupon' => $coupon ? ['code' => $coupon['code'], 'discount' => formatPrice($coupon_discount)] : null
    ];
}

// Helper function to get cart total
function getCartTotal($db, $user_id) {
    $db->query("SELECT SUM(CASE WHEN p.discount_price > 0 AND p.discount_price < p.price 
                THEN p.discount_price ELSE p.price END * c.quantity) as total
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = :user_id AND p.is_active = 1");
    $db->bind(':user_id', $user_id);
    $result = $db->fetch();
    return $result['total'] ?? 0;
}
