<?php

require_once __DIR__ . '/_init.php';
requireMethod('POST');

requireLogin();
$userId = getCurrentUserId();
if ($userId === null) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$input = readJsonBody();
$shippingAddress = isset($input['shipping_address']) && is_string($input['shipping_address']) ? trim($input['shipping_address']) : '';
$recipientName = isset($input['recipient_name']) && is_string($input['recipient_name']) ? trim($input['recipient_name']) : '';
$paymentMethod = isset($input['payment_method']) && is_string($input['payment_method']) ? trim($input['payment_method']) : null;

if ($shippingAddress === '') {
    jsonResponse(['success' => false, 'error' => 'shipping_address is required'], 400);
}

try {
    if ($recipientName === '') {
        $u = $pdo->prepare('SELECT full_name FROM user_master WHERE user_id = ? LIMIT 1');
        $u->execute([$userId]);
        $ur = $u->fetch();
        $recipientName = $ur ? (string)$ur['full_name'] : 'Customer';
    }

    $cartId = getActiveCartId($pdo, $userId);
    if ($cartId === null) {
        jsonResponse(['success' => false, 'error' => 'Cart is empty'], 400);
    }

    $itemsStmt = $pdo->prepare('SELECT product_id, quantity, price_at_time FROM cart_items WHERE cart_id = ?');
    $itemsStmt->execute([$cartId]);
    $items = $itemsStmt->fetchAll();

    if (!$items || count($items) === 0) {
        jsonResponse(['success' => false, 'error' => 'Cart is empty'], 400);
    }

    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += ((float)$it['price_at_time']) * ((int)$it['quantity']);
    }

    $shippingCost = 0.0;
    $taxAmount = 0.0;
    $totalAmount = $subtotal + $shippingCost + $taxAmount;

    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare(
'INSERT INTO orders (user_id, total_amount, order_status, created_at)
         VALUES (?, ?, ?, NOW())'
    );
$orderStmt->execute([
        $userId,
        $totalAmount,
        'pending'
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
'INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)'
    );

    foreach ($items as $it) {
        $pid = (int)$it['product_id'];
        $qty = (int)$it['quantity'];
        $price = (float)$it['price_at_time'];
        $line = $price * $qty;
$itemStmt->execute([$orderId, $pid, $qty, $price]);
    }

    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
$pdo->prepare('UPDATE shopping_cart SET is_active = 0 WHERE cart_id = ?')->execute([$cartId]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'order' => [
            'order_id' => $orderId,
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ],
    ], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
if (defined('APP_DEBUG') && APP_DEBUG) {
    jsonResponse(['success' => false, 'error' => 'Order placement failed: ' . $e->getMessage()], 500);
} else {
    jsonResponse(['success' => false, 'error' => 'Order placement failed'], 500);
}
}