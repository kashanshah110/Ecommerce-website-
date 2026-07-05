<?php
/**
 * Naeem Electronic - Wishlist API
 * Handles wishlist operations via AJAX
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
    case 'toggle':
        // Add/remove item from wishlist
        $product_id = (int)($_POST['product_id'] ?? 0);
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        if ($product_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            exit;
        }
        
        // Check if product exists
        $db->query("SELECT id FROM products WHERE id = :id AND is_active = 1");
        $db->bind(':id', $product_id);
        if (!$db->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        // Check if already in wishlist
        $db->query("SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
        $db->bind(':user_id', $user_id);
        $db->bind(':product_id', $product_id);
        $existing = $db->fetch();
        
        if ($existing) {
            // Remove from wishlist
            $db->query("DELETE FROM wishlist WHERE id = :id");
            $db->bind(':id', $existing['id']);
            $db->execute();
            
            $wishlist_count = getWishlistCount($db, $user_id);
            echo json_encode(['success' => true, 'added' => false, 'wishlist_count' => $wishlist_count]);
        } else {
            // Add to wishlist
            $db->query("INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)");
            $db->bind(':user_id', $user_id);
            $db->bind(':product_id', $product_id);
            $db->execute();
            
            $wishlist_count = getWishlistCount($db, $user_id);
            echo json_encode(['success' => true, 'added' => true, 'wishlist_count' => $wishlist_count]);
        }
        break;
        
    case 'remove':
        // Remove item from wishlist
        $wishlist_id = (int)($_POST['wishlist_id'] ?? 0);
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        if ($wishlist_id === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid wishlist item']);
            exit;
        }
        
        $db->query("DELETE FROM wishlist WHERE id = :id AND user_id = :user_id");
        $db->bind(':id', $wishlist_id);
        $db->bind(':user_id', $user_id);
        $db->execute();
        
        $wishlist_count = getWishlistCount($db, $user_id);
        echo json_encode(['success' => true, 'wishlist_count' => $wishlist_count]);
        break;
        
    case 'move_to_cart':
        // Move item from wishlist to cart
        $wishlist_id = (int)($_POST['wishlist_id'] ?? 0);
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        // Get wishlist item
        $db->query("SELECT product_id FROM wishlist WHERE id = :id AND user_id = :user_id");
        $db->bind(':id', $wishlist_id);
        $db->bind(':user_id', $user_id);
        $wishlist_item = $db->fetch();
        
        if (!$wishlist_item) {
            echo json_encode(['success' => false, 'message' => 'Wishlist item not found']);
            exit;
        }
        
        $product_id = $wishlist_item['product_id'];
        
        // Check if product exists and is in stock
        $db->query("SELECT id, stock_quantity, stock_status FROM products WHERE id = :id AND is_active = 1");
        $db->bind(':id', $product_id);
        $product = $db->fetch();
        
        if (!$product || $product['stock_status'] === 'out_of_stock') {
            echo json_encode(['success' => false, 'message' => 'Product not available']);
            exit;
        }
        
        // Check if already in cart
        $db->query("SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $db->bind(':user_id', $user_id);
        $db->bind(':product_id', $product_id);
        $cart_item = $db->fetch();
        
        if ($cart_item) {
            // Update quantity
            $new_quantity = $cart_item['quantity'] + 1;
            if ($new_quantity > $product['stock_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock']);
                exit;
            }
            
            $db->query("UPDATE cart SET quantity = :quantity WHERE id = :id");
            $db->bind(':quantity', $new_quantity);
            $db->bind(':id', $cart_item['id']);
            $db->execute();
        } else {
            // Add to cart
            $db->query("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)");
            $db->bind(':user_id', $user_id);
            $db->bind(':product_id', $product_id);
            $db->execute();
        }
        
        // Remove from wishlist
        $db->query("DELETE FROM wishlist WHERE id = :id");
        $db->bind(':id', $wishlist_id);
        $db->execute();
        
        $wishlist_count = getWishlistCount($db, $user_id);
        
        // Get cart count
        $db->query("SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id");
        $db->bind(':user_id', $user_id);
        $cart_count_result = $db->fetch();
        
        echo json_encode(['success' => true, 'wishlist_count' => $wishlist_count, 'cart_count' => $cart_count_result['count']]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Helper function to get wishlist count
function getWishlistCount($db, $user_id) {
    $db->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id");
    $db->bind(':user_id', $user_id);
    $result = $db->fetch();
    return $result['count'] ?? 0;
}
