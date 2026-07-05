<?php
/**
 * Naeem Electronic - Product API
 * Returns product details for quick view and other AJAX calls
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action !== 'quick_view' || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$db = new Database();
$db->query("SELECT p.*, pi.image_path FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.id = :id AND p.is_active = 1");
$db->bind(':id', $id);
$product = $db->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$image = $product['image_path'] ? UPLOADS_PATH . '/' . $product['image_path'] : 'https://via.placeholder.com/500x500?text=No+Image';
$price = $product['discount_price'] && $product['discount_price'] < $product['price'] ? $product['discount_price'] : $product['price'];

$productData = [
    'id' => $product['id'],
    'name' => $product['name'],
    'sku' => $product['sku'],
    'description' => trim($product['short_description'] ?: $product['description'] ?: 'No description available.'),
    'price' => formatPrice($price),
    'stock_status' => ucfirst(str_replace('_', ' ', $product['stock_status'])),
    'image' => $image,
];

echo json_encode(['success' => true, 'product' => $productData]);
exit;
