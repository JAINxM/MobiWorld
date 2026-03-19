<?php

require_once __DIR__ . '/_init.php';
requireMethod('GET');

requireLogin();
$userId = getCurrentUserId();
if ($userId === null) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

try {
$stmt = $pdo->prepare('SELECT order_id, total_amount, order_status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$o) {
        $c = $pdo->prepare('SELECT COUNT(*) AS items FROM order_items WHERE order_id = ?');
        $c->execute([(int)$o['order_id']]);
        $o['items_count'] = (int)($c->fetch()['items'] ?? 0);
        $o['order_id'] = (int)$o['order_id'];
        $o['total_amount'] = (float)$o['total_amount'];
    }

    jsonResponse(['success' => true, 'orders' => $orders]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch orders'], 500);
}