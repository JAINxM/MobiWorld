<?php

require_once __DIR__ . '/_init.php';
requireMethod('POST');

requireLogin();
$userId = getCurrentUserId();
if ($userId === null) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$input = readJsonBody();
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : -1;

if ($productId <= 0 || $quantity < 0) {
    jsonResponse(['success' => false, 'error' => 'Missing product_id/quantity'], 400);
}

try {
    $cartId = getActiveCartId($pdo, $userId);
    if ($cartId === null) {
        jsonResponse(['success' => true, 'message' => 'Cart updated']);
    }

    if ($quantity === 0) {
        $del = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?');
        $del->execute([$cartId, $productId]);
        jsonResponse(['success' => true, 'message' => 'Cart updated']);
    }

    // Validate stock
    $stmt = $pdo->prepare('SELECT product_id, regular_price, discounted_price, stock_quantity, is_active FROM product_master WHERE product_id = ? LIMIT 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || (int)$product['is_active'] !== 1) {
        jsonResponse(['success' => false, 'error' => 'Product not found'], 404);
    }
    $stock = (int)($product['stock_quantity'] ?? 0);
    if ($quantity > $stock) {
        jsonResponse(['success' => false, 'error' => 'Not enough stock'], 409);
    }

    $price = currentProductPrice($product);

    $upd = $pdo->prepare('UPDATE cart_items SET quantity = ?, price_at_time = ? WHERE cart_id = ? AND product_id = ?');
    $upd->execute([$quantity, $price, $cartId, $productId]);

    jsonResponse(['success' => true, 'message' => 'Cart updated']);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Update failed'], 500);
}