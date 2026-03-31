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
$shippingAddress = isset($input['shipping_address']) && is_string($input['shipping_address']) ? trim($input['shipping_address']) : '';
$recipientName = isset($input['recipient_name']) && is_string($input['recipient_name']) ? trim($input['recipient_name']) : '';

if ($shippingAddress === '') {
    jsonResponse(['success' => false, 'error' => 'shipping_address is required'], 400);
}

try {
    if ($recipientName === '') {
        $u = $pdo->prepare('SELECT full_name, email, mobile FROM user_master WHERE user_id = ? LIMIT 1');
        $u->execute([$userId]);
        $ur = $u->fetch();
        $recipientName = $ur ? (string) $ur['full_name'] : 'Customer';
    } else {
        $u = $pdo->prepare('SELECT full_name, email, mobile FROM user_master WHERE user_id = ? LIMIT 1');
        $u->execute([$userId]);
        $ur = $u->fetch();
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
        $subtotal += ((float) $it['price_at_time']) * ((int) $it['quantity']);
    }

    $shippingCost = 0.0;
    $taxAmount = 0.0;
    $totalAmount = $subtotal + $shippingCost + $taxAmount;

    $amountPaise = (int) round($totalAmount * 100);
    if ($amountPaise <= 0) {
        jsonResponse(['success' => false, 'error' => 'Invalid order amount'], 400);
    }

    $receipt = 'cart_' . $cartId . '_u_' . $userId . '_' . time();
    $notes = [
        'user_id' => (string) $userId,
        'cart_id' => (string) $cartId,
    ];

    $rp = razorpayCreateOrder($amountPaise, RAZORPAY_CURRENCY, $receipt, $notes);
    if (!$rp['ok']) {
        $msg = (string) ($rp['error'] ?? 'Razorpay order create failed');
        jsonResponse(['success' => false, 'error' => $msg], 500);
    }

    $rpData = $rp['data'];
    $rpOrderId = isset($rpData['id']) ? (string) $rpData['id'] : '';
    if ($rpOrderId === '') {
        jsonResponse(['success' => false, 'error' => 'Invalid Razorpay order response'], 500);
    }

    ensureSessionStarted();
    $_SESSION['razorpay_pending'] = [
        'user_id' => (int) $userId,
        'cart_id' => (int) $cartId,
        'razorpay_order_id' => $rpOrderId,
        'amount' => (int) $amountPaise,
        'currency' => (string) RAZORPAY_CURRENCY,
        'recipient_name' => (string) $recipientName,
        'shipping_address' => (string) $shippingAddress,
        'created_at' => time(),
    ];

    $prefill = [
        'name' => $recipientName,
        'email' => $ur ? (string) ($ur['email'] ?? '') : '',
        'contact' => $ur ? (string) ($ur['mobile'] ?? '') : '',
    ];

    jsonResponse([
        'success' => true,
        'razorpay' => [
            'key_id' => RAZORPAY_KEY_ID,
            'order_id' => $rpOrderId,
            'amount' => $amountPaise,
            'currency' => RAZORPAY_CURRENCY,
            'name' => RAZORPAY_COMPANY_NAME,
            'description' => 'Order payment for MobiWorld',
            'prefill' => $prefill,
            'notes' => $notes,
        ],
    ]);
} catch (Throwable $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        jsonResponse(['success' => false, 'error' => 'Razorpay create order failed: ' . $e->getMessage()], 500);
    }
    jsonResponse(['success' => false, 'error' => 'Razorpay create order failed'], 500);
}

