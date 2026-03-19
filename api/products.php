<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/_init.php';
requireMethod('GET');

try {
    $stmt = $pdo->query(
        "SELECT 
            product_id,
            name,
            brand,
            regular_price,
            discounted_price,
            description,
            image_url,
            stock_quantity,
            created_at
         FROM product_master 
         WHERE is_active = 1
         ORDER BY created_at DESC"
    );

    $rows = $stmt->fetchAll();
    $products = [];
    foreach ($rows as $r) {
        $price = (float)$r['regular_price'];
        if ($r['discounted_price'] !== null && (float)$r['discounted_price'] > 0) {
            $price = (float)$r['discounted_price'];
        }
        $products[] = [
            'id' => (int)$r['product_id'],
            'name' => (string)($r['name'] ?? 'Unknown Product'),
            'brand' => (string)($r['brand'] ?? 'Unknown Brand'),
            'price' => $price,
            'description' => $r['description'] ?? 'No description available',
            'image' => $r['image_url'] ?? 'https://via.placeholder.com/400x400?text=No+Image',
            'stock' => (int)($r['stock_quantity'] ?? 0),
            'created_at' => (string)$r['created_at'],
        ];
    }

    jsonResponse(['success' => true, 'products' => $products]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Products failed: ' . $e->getMessage()], 500);
}
?>

