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
if ($productId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Missing product_id'], 400);
}

try {
    $cartId = getActiveCartId($pdo, $userId);
    if ($cartId !== null) {
        $del = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?');
        $del->execute([$cartId, $productId]);
    }
    jsonResponse(['success' => true, 'message' => 'Item removed']);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Remove failed'], 500);
}