<?php
/**
 * Naeem Electronic - Search API
 * Handles product search with autocomplete
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$db = new Database();
$query = sanitize($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Search query too short']);
    exit;
}

// Search products
$search_term = "%$query%";
$db->query("SELECT p.id, p.name, p.slug, p.price, p.discount_price, pi.image_path, c.name as category_name
           FROM products p
           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
           LEFT JOIN categories c ON p.category_id = c.id
           WHERE p.is_active = 1 
           AND (p.name LIKE :query OR p.short_description LIKE :query OR p.sku LIKE :query)
           ORDER BY p.created_at DESC
           LIMIT 10");
$db->bind(':query', $search_term);
$products = $db->fetchAll();

$results = [];
foreach ($products as $product) {
    $price = $product['discount_price'] && $product['discount_price'] < $product['price'] 
        ? $product['discount_price'] 
        : $product['price'];
    
    $results[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'slug' => $product['slug'],
        'price' => formatPrice($price),
        'image' => UPLOADS_PATH . '/' . ($product['image_path'] ?? ''),
        'category' => $product['category_name'] ?? ''
    ];
}

echo json_encode(['success' => true, 'results' => $results]);
