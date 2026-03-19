<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/_init.php';
global $pdo;
requireMethod('GET');

try {
    $user_id = getUserId();
    if (!$user_id) {
        jsonResponse(['success' => true, 'wishlist' => [], 'count' => 0]);
        return;
    }

    $stmt = $GLOBALS['pdo']->prepare("
        SELECT 
            p.product_id,
            p.name,
            p.brand,
            p.regular_price,
            p.discounted_price,
            p.description,
            p.image_url,
            p.stock_quantity,
            p.created_at,
            wi.added_at
         FROM wishlist_items wi
         JOIN product_master p ON wi.product_id = p.product_id
         WHERE wi.user_id = ? AND p.is_active = 1
         ORDER BY wi.added_at DESC
    ");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();

    $wishlist = [];
    $count = 0;
    foreach ($rows as $r) {
        $price = (float)$r['regular_price'];
        if ($r['discounted_price'] !== null && (float)$r['discounted_price'] > 0) {
            $price = (float)$r['discounted_price'];
        }
        $wishlist[] = [
            'id' => (int)$r['product_id'],
            'name' => (string)($r['name'] ?? 'Unknown Product'),
            'brand' => (string)($r['brand'] ?? 'Unknown Brand'),
            'price' => $price,
            'description' => $r['description'] ?? 'No description available',
            'image' => $r['image_url'] ?? 'https://via.placeholder.com/400x400?text=No+Image',
            'stock' => (int)$r['stock_quantity'] ?? 0,
            'created_at' => (string)$r['created_at'],
            'wishlisted_at' => (string)$r['added_at']
        ];
        $count++;
    }

    jsonResponse([
        'success' => true, 
        'wishlist' => $wishlist, 
        'count' => $count
    ]);

} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Wishlist fetch failed: ' . $e->getMessage()], 500);
}
?>

