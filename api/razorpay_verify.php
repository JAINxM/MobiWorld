<?php

require_once __DIR__ . '/_init.php';
requireMethod('POST');

requireLogin();
$userId = getCurrentUserId();
if ($userId === null) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

require_once dirname(__DIR__) . '/includes/config/razorpay.php';
require_once dirname(__DIR__) . '/includes/lib/razorpay_api.php';

$input = readJsonBody();
$razorpayOrderId = isset($input['razorpay_order_id']) && is_string($input['razorpay_order_id']) ? trim($input['razorpay_order_id']) : '';
$razorpayPaymentId = isset($input['razorpay_payment_id']) && is_string($input['razorpay_payment_id']) ? trim($input['razorpay_payment_id']) : '';
$razorpaySignature = isset($input['razorpay_signature']) && is_string($input['razorpay_signature']) ? trim($input['razorpay_signature']) : '';

if ($razorpayOrderId === '' || $razorpayPaymentId === '' || $razorpaySignature === '') {
    jsonResponse(['success' => false, 'error' => 'Missing payment verification fields'], 400);
}

ensureSessionStarted();
$pending = $_SESSION['razorpay_pending'] ?? null;
if (!is_array($pending)) {
    jsonResponse(['success' => false, 'error' => 'No pending Razorpay order found. Please try again.'], 400);
}

if ((int) ($pending['user_id'] ?? 0) !== (int) $userId) {
    jsonResponse(['success' => false, 'error' => 'Invalid pending payment session'], 400);
}

if ((string) ($pending['razorpay_order_id'] ?? '') !== $razorpayOrderId) {
    jsonResponse(['success' => false, 'error' => 'Razorpay order mismatch. Please try again.'], 400);
}

if (!razorpayVerifySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)) {
    jsonResponse(['success' => false, 'error' => 'Payment verification failed (signature mismatch)'], 400);
}

$cartId = (int) ($pending['cart_id'] ?? 0);
$shippingAddress = (string) ($pending['shipping_address'] ?? '');
$recipientName = (string) ($pending['recipient_name'] ?? '');

try {
    if ($cartId <= 0) {
        jsonResponse(['success' => false, 'error' => 'Cart not found for payment'], 400);
    }

    $itemsStmt = $pdo->prepare('SELECT product_id, quantity, price_at_time FROM cart_items WHERE cart_id = ?');
    $itemsStmt->execute([$cartId]);
    $items = $itemsStmt->fetchAll();

    if (!$items || count($items) === 0) {
        jsonResponse(['success' => false, 'error' => 'Cart is empty'], 400);
    }

    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += ((float) $it['price_at_time']) * ((int) $it['quantity']);
    }

    $shippingCost = 0.0;
    $taxAmount = 0.0;
    $totalAmount = $subtotal + $shippingCost + $taxAmount;

    $expectedPaise = (int) ($pending['amount'] ?? 0);
    $computedPaise = (int) round($totalAmount * 100);
    if ($expectedPaise > 0 && $computedPaise !== $expectedPaise) {
        jsonResponse(['success' => false, 'error' => 'Order amount changed. Please try again.'], 400);
    }

    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders (user_id, total_amount, order_status, created_at, payment_method, payment_status, recipient_name, shipping_address, razorpay_order_id, razorpay_payment_id, paid_at)
         VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, NOW())'
    );
    $orderStmt->execute([
        $userId,
        $totalAmount,
        'confirmed',
        'razorpay',
        'paid',
        $recipientName,
        $shippingAddress,
        $razorpayOrderId,
        $razorpayPaymentId,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)'
    );

    foreach ($items as $it) {
        $pid = (int) $it['product_id'];
        $qty = (int) $it['quantity'];
        $price = (float) $it['price_at_time'];
        $itemStmt->execute([$orderId, $pid, $qty, $price]);
    }

    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
    $pdo->prepare('UPDATE shopping_cart SET is_active = 0 WHERE cart_id = ?')->execute([$cartId]);

    $pdo->commit();

    unset($_SESSION['razorpay_pending']);

    jsonResponse([
        'success' => true,
        'order' => [
            'order_id' => $orderId,
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
            'status' => 'confirmed',
        ],
        'payment' => [
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
        ],
    ], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (defined('APP_DEBUG') && APP_DEBUG) {
        jsonResponse(['success' => false, 'error' => 'Payment verification failed: ' . $e->getMessage()], 500);
    }
    jsonResponse(['success' => false, 'error' => 'Payment verification failed'], 500);
}
