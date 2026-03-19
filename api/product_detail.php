<?php

require_once __DIR__ . '/_init.php';
requireMethod('GET');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(['success' => false, 'error' => 'Missing product id'], 400);
}

try {
    $stmt = $pdo->prepare(
        "SELECT 
            pm.*, 
            bm.brand_name,
            cm.category_name
         FROM product_master pm
         INNER JOIN brand_master bm ON bm.brand_id = pm.brand_id
         INNER JOIN categorie_master cm ON cm.category_id = pm.category_id
         WHERE pm.product_id = ? AND pm.is_active = 1
         LIMIT 1"
    );
    $stmt->execute([$id]);
    $r = $stmt->fetch();

    if (!$r) {
        jsonResponse(['success' => false, 'error' => 'Product not found'], 404);
    }

    $price = (float)$r['regular_price'];
    if ($r['discounted_price'] !== null && (float)$r['discounted_price'] > 0) {
        $price = (float)$r['discounted_price'];
    }

    $product = [
        'id' => (int)$r['product_id'],
        'name' => (string)$r['product_name'],
        'brand' => (string)$r['brand_name'],
        'category' => (string)$r['category_name'],
        'price' => $price,
        'regular_price' => (float)$r['regular_price'],
        'discounted_price' => $r['discounted_price'] !== null ? (float)$r['discounted_price'] : null,
        'description' => $r['product_description'],
        'image' => $r['image_url'],
        'stock' => (int)($r['stock_quantity'] ?? 0),
        'created_at' => (string)$r['created_at'],
        'updated_at' => (string)$r['updated_at'],
    ];

    jsonResponse(['success' => true, 'product' => $product]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch product'], 500);
}