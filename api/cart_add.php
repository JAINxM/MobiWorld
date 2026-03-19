<?php

require_once __DIR__ . '/_init.php';
requireMethod('POST');

// requireLogin(); // Disabled for guest cart testing
$userId = getCurrentUserId();
requireLogin();
if ($userId === null) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$input = readJsonBody();
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 0;

if ($productId <= 0 || $quantity <= 0) {
    jsonResponse(['success' => false, 'error' => 'Missing product_id/quantity'], 400);
}

try {
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

    $cartId = getOrCreateActiveCartId($pdo, $userId);
    $price = currentProductPrice($product);

    $pdo->beginTransaction();

    $check = $pdo->prepare('SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1');
    $check->execute([$cartId, $productId]);
    $existing = $check->fetch();

    if ($existing) {
        $newQty = (int)$existing['quantity'] + $quantity;
        if ($newQty > $stock) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'error' => 'Not enough stock'], 409);
        }
        $upd = $pdo->prepare('UPDATE cart_items SET quantity = ?, price_at_time = ? WHERE cart_item_id = ?');
        $upd->execute([$newQty, $price, (int)$existing['cart_item_id']]);
    } else {
        $ins = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity, price_at_time, added_at) VALUES (?, ?, ?, ?, NOW())');
        $ins->execute([$cartId, $productId, $quantity, $price]);
    }

    $pdo->commit();
    jsonResponse(['success' => true, 'message' => 'Added to cart']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
if (defined('APP_DEBUG') && APP_DEBUG) {
    jsonResponse(['success' => false, 'error' => 'Add to cart failed: ' . $e->getMessage()], 500);
} else {
    jsonResponse(['success' => false, 'error' => 'Add to cart failed'], 500);
}
}