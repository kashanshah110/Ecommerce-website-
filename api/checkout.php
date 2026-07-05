<?php
/**
 * Naeem Electronic - Checkout API
 * Handles checkout form submission and order creation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];

$payment_method = $_POST['payment_method'] ?? 'cod';
$address_id = (int)($_POST['address_id'] ?? 0);
$save_address = isset($_POST['save_address']);

if (!in_array($payment_method, ['cod', 'card', 'jazzcash', 'easypaisa'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit;
}

// Load cart items
$db->query("SELECT c.id as cart_id, c.quantity, p.*,
            CASE WHEN p.discount_price > 0 AND p.discount_price < p.price THEN p.discount_price ELSE p.price END as final_price
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = :user_id AND p.is_active = 1");
$db->bind(':user_id', $user_id);
$cart_items = $db->fetchAll();

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
    exit;
}

// Determine shipping and total amounts
$subtotal = 0;
$discount = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $discount += ($item['price'] - $item['final_price']) * $item['quantity'];
}
$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += $item['final_price'] * $item['quantity'];
}

// Apply coupon discount if available
$coupon_id = null;
$coupon_discount = 0;
if (isset($_SESSION['applied_coupon'])) {
    $coupon_code = sanitize($_SESSION['applied_coupon']);
    $db->query("SELECT * FROM coupons WHERE code = :code AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
    $db->bind(':code', $coupon_code);
    $coupon = $db->fetch();

    if (!$coupon) {
        unset($_SESSION['applied_coupon']);
        echo json_encode(['success' => false, 'message' => 'Applied coupon is no longer valid']);
        exit;
    }

    if ($coupon['total_usage_limit'] && $coupon['used_count'] >= $coupon['total_usage_limit']) {
        unset($_SESSION['applied_coupon']);
        echo json_encode(['success' => false, 'message' => 'Coupon usage limit exceeded']);
        exit;
    }

    $db->query("SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = :coupon_id AND user_id = :user_id");
    $db->bind(':coupon_id', $coupon['id']);
    $db->bind(':user_id', $user_id);
    $usage_result = $db->fetch();

    if ($usage_result['count'] >= $coupon['usage_limit_per_user']) {
        unset($_SESSION['applied_coupon']);
        echo json_encode(['success' => false, 'message' => 'You have already used this coupon']);
        exit;
    }

    if ($cart_total < $coupon['min_order_amount']) {
        unset($_SESSION['applied_coupon']);
        echo json_encode(['success' => false, 'message' => 'Minimum order amount for this coupon is ' . formatPrice($coupon['min_order_amount'])]);
        exit;
    }

    if ($coupon['discount_type'] === 'percentage') {
        $coupon_discount = ($cart_total * $coupon['discount_value']) / 100;
    } else {
        $coupon_discount = $coupon['discount_value'];
    }

    if ($coupon['max_discount_amount'] && $coupon_discount > $coupon['max_discount_amount']) {
        $coupon_discount = $coupon['max_discount_amount'];
    }

    if ($coupon_discount > $cart_total) {
        $coupon_discount = $cart_total;
    }

    $coupon_id = $coupon['id'];
    $cart_total -= $coupon_discount;
    $discount += $coupon_discount;
}

$free_shipping_threshold = getSetting('free_shipping_threshold', 5000);
$shipping_amount = $cart_total >= $free_shipping_threshold ? 0 : getSetting('shipping_cost', 200);
$final_amount = $cart_total + $shipping_amount;

// Collect address details
$address_data = [];
if ($address_id > 0) {
    $db->query('SELECT * FROM addresses WHERE id = :id AND user_id = :user_id');
    $db->bind(':id', $address_id);
    $db->bind(':user_id', $user_id);
    $address_data = $db->fetch();
    if (!$address_data) {
        echo json_encode(['success' => false, 'message' => 'Selected address not found']);
        exit;
    }
} else {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $province = sanitize($_POST['province'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $postal_code = sanitize($_POST['postal_code'] ?? '');
    $landmark = sanitize($_POST['landmark'] ?? '');
    $is_islamabad = isset($_POST['islamabad']);

    if (!$full_name || !$phone || !$email || !$address || (!$is_islamabad && (!$province || !$city))) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required address fields']);
        exit;
    }

    if (!isValidEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }

    if (!isValidPhone($phone)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
        exit;
    }

    $address_data = [
        'full_name' => $full_name,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'province' => $is_islamabad ? 'Islamabad' : $province,
        'city' => $is_islamabad ? 'Islamabad' : $city,
        'postal_code' => $postal_code,
        'landmark' => $landmark,
    ];

    if ($save_address) {
        $db->query('INSERT INTO addresses (user_id, full_name, phone, province, city, address, postal_code, landmark, is_default, created_at, updated_at)
                    VALUES (:user_id, :full_name, :phone, :province, :city, :address, :postal_code, :landmark, 0, NOW(), NOW())');
        $db->bind(':user_id', $user_id);
        $db->bind(':full_name', $full_name);
        $db->bind(':phone', $phone);
        $db->bind(':province', $address_data['province']);
        $db->bind(':city', $address_data['city']);
        $db->bind(':address', $address);
        $db->bind(':postal_code', $postal_code);
        $db->bind(':landmark', $landmark);
        $db->execute();
    }
}

// Payment validation
$payment_details = [];
if ($payment_method === 'card') {
    $card_holder_name = sanitize($_POST['card_holder_name'] ?? '');
    $card_number = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
    $expiry_month = sanitize($_POST['expiry_month'] ?? '');
    $expiry_year = sanitize($_POST['expiry_year'] ?? '');
    $cvv = preg_replace('/\D+/', '', $_POST['cvv'] ?? '');

    if (!$card_holder_name || strlen($card_number) !== 16 || !preg_match('/^(0[1-9]|1[0-2])$/', $expiry_month) || !preg_match('/^[0-9]{2}$/', $expiry_year) || strlen($cvv) < 3) {
        echo json_encode(['success' => false, 'message' => 'Please enter valid card details']);
        exit;
    }

    $expiry_date = DateTime::createFromFormat('my', $expiry_month . $expiry_year);
    $now = new DateTime('first day of this month');
    if (!$expiry_date || $expiry_date < $now) {
        echo json_encode(['success' => false, 'message' => 'Card has expired']);
        exit;
    }

    $payment_details = [
        'card_holder_name' => $card_holder_name,
        'card_number' => substr($card_number, 0, 4) . '********' . substr($card_number, -4),
        'expiry_month' => $expiry_month,
        'expiry_year' => $expiry_year,
    ];
} elseif ($payment_method === 'jazzcash' || $payment_method === 'easypaisa') {
    $mobile = sanitize($_POST["{$payment_method}_mobile"] ?? '');
    $txn_id = sanitize($_POST["{$payment_method}_txn_id"] ?? '');

    if (!$mobile || !$txn_id || !isValidPhone($mobile)) {
        echo json_encode(['success' => false, 'message' => 'Please enter valid mobile and transaction details']);
        exit;
    }

    $payment_details = [
        'mobile' => $mobile,
        'transaction_id' => $txn_id,
    ];
}

// Create order
try {
    $db->beginTransaction();

    $shipping_address_text = htmlspecialchars($address_data['full_name'] . "\n" . $address_data['phone'] . "\n" . $address_data['address'] . ', ' . $address_data['city'] . ', ' . $address_data['province'] . ($address_data['postal_code'] ? ', ' . $address_data['postal_code'] : '') . ($address_data['landmark'] ? "\nLandmark: " . $address_data['landmark'] : ''));
    $billing_address_text = $shipping_address_text;

    $db->query('INSERT INTO orders (order_number, user_id, total_amount, discount_amount, shipping_amount, final_amount, payment_method, payment_status, order_status, coupon_id, shipping_address, billing_address, created_at, updated_at)
                VALUES (:order_number, :user_id, :total_amount, :discount_amount, :shipping_amount, :final_amount, :payment_method, :payment_status, :order_status, :coupon_id, :shipping_address, :billing_address, NOW(), NOW())');
    $db->bind(':order_number', generateOrderNumber());
    $db->bind(':user_id', $user_id);
    $db->bind(':total_amount', $subtotal);
    $db->bind(':discount_amount', $discount);
    $db->bind(':shipping_amount', $shipping_amount);
    $db->bind(':final_amount', $final_amount);
    $db->bind(':payment_method', $payment_method);
    $db->bind(':payment_status', $payment_method === 'cod' ? 'pending' : 'paid');
    $db->bind(':order_status', 'pending');
    $db->bind(':coupon_id', $coupon_id);
    $db->bind(':shipping_address', $shipping_address_text);
    $db->bind(':billing_address', $billing_address_text);
    $db->execute();

    $order_id = $db->lastInsertId();

    if ($coupon_id) {
        $db->query('INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, used_at) VALUES (:coupon_id, :user_id, :order_id, :discount_amount, NOW())');
        $db->bind(':coupon_id', $coupon_id);
        $db->bind(':user_id', $user_id);
        $db->bind(':order_id', $order_id);
        $db->bind(':discount_amount', $coupon_discount);
        $db->execute();

        $db->query('UPDATE coupons SET used_count = used_count + 1 WHERE id = :coupon_id');
        $db->bind(':coupon_id', $coupon_id);
        $db->execute();
    }

    foreach ($cart_items as $item) {
        $item_total = $item['final_price'] * $item['quantity'];
        $db->query('INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, price, discount_price, total, created_at) VALUES (:order_id, :product_id, :product_name, :product_sku, :quantity, :price, :discount_price, :total, NOW())');
        $db->bind(':order_id', $order_id);
        $db->bind(':product_id', $item['id']);
        $db->bind(':product_name', $item['name']);
        $db->bind(':product_sku', $item['sku']);
        $db->bind(':quantity', $item['quantity']);
        $db->bind(':price', $item['final_price']);
        $db->bind(':discount_price', $item['discount_price'] ?? 0);
        $db->bind(':total', $item_total);
        $db->execute();
    }

    $db->query('INSERT INTO payments (order_id, transaction_id, payment_method, amount, status, payment_details, created_at) VALUES (:order_id, :transaction_id, :payment_method, :amount, :status, :payment_details, NOW())');
    $db->bind(':order_id', $order_id);
    $db->bind(':transaction_id', $payment_details['transaction_id'] ?? null);
    $db->bind(':payment_method', $payment_method);
    $db->bind(':amount', $final_amount);
    $db->bind(':status', $payment_method === 'cod' ? 'pending' : 'paid');
    $db->bind(':payment_details', json_encode($payment_details));
    $db->execute();

    $db->query('DELETE FROM cart WHERE user_id = :user_id');
    $db->bind(':user_id', $user_id);
    $db->execute();

    if (isset($_SESSION['applied_coupon'])) {
        unset($_SESSION['applied_coupon']);
    }

    $db->commit();

    echo json_encode(['success' => true, 'order_id' => $order_id]);
    exit;
} catch (Exception $e) {
    $db->rollback();
    error_log('Checkout error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to place order at this time']);
    exit;
}
