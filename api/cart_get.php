<?php

require_once __DIR__ . '/_init.php';
requireMethod('GET');

requireLogin();
$userId = getCurrentUserId();
if ($userId === null) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

try {
    $cartId = getActiveCartId($pdo, $userId);
    if ($cartId === null) {
        jsonResponse(['success' => true, 'cart' => [], 'total' => 0]);
    }

    $stmt = $pdo->prepare(
        "SELECT 
            ci.product_id,
            ci.quantity,
            ci.price_at_time,
            pm.name,
            pm.brand,
            pm.image_url
         FROM cart_items ci
         INNER JOIN product_master pm ON pm.product_id = ci.product_id
         WHERE ci.cart_id = ?
         ORDER BY ci.added_at DESC"
    );
    $stmt->execute([$cartId]);
    $rows = $stmt->fetchAll();

    $cart = [];
    $total = 0.0;
    foreach ($rows as $r) {
        $qty = (int)$r['quantity'];
        $price = (float)$r['price_at_time'];
        $subtotal = $qty * $price;
        $total += $subtotal;
        $cart[] = [
            'product_id' => (int)$r['product_id'],
            'name' => (string)($r['name'] ?? 'Unknown Product'),
            'brand' => (string)($r['brand'] ?? 'Unknown Brand'),
            'image' => $r['image_url'] ?: 'https://via.placeholder.com/400x400?text=No+Image',
            'price' => $price,
            'quantity' => $qty,
            'subtotal' => $subtotal,
        ];
    }

    jsonResponse(['success' => true, 'cart' => $cart, 'total' => $total]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch cart'], 500);
}
