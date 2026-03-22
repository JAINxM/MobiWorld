<?php
require_once __DIR__ . '/_init.php';
requireMethod('POST');

requireLogin();
$userId = getCurrentUserId();
if ($userId === null) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$input = readJsonBody();
$orderItemId = isset($input['order_item_id']) ? (int)$input['order_item_id'] : 0;
$rating = isset($input['rating']) ? (int)$input['rating'] : 0;
$reviewText = isset($input['review_text']) && is_string($input['review_text']) ? trim($input['review_text']) : null;

if ($orderItemId <= 0 || $rating < 1 || $rating > 5) {
    jsonResponse(['success' => false, 'error' => 'Invalid order_item_id or rating (1-5 required)'], 400);
}

// Verify user owns this order item
$stmt = $pdo->prepare('
    SELECT oi.order_item_id 
    FROM order_items oi 
    JOIN orders o ON o.order_id = oi.order_id 
    WHERE oi.order_item_id = ? AND o.user_id = ? AND o.order_status = "delivered"
');
$stmt->execute([$orderItemId, $userId]);
$item = $stmt->fetch();

if (!$item) {
    jsonResponse(['success' => false, 'error' => 'Invalid order item or not delivered'], 403);
}

// Check if already reviewed
$checkStmt = $pdo->prepare('SELECT review_id FROM product_reviews WHERE order_item_id = ? AND is_active = 1');
$checkStmt->execute([$orderItemId]);
if ($checkStmt->fetch()) {
    jsonResponse(['success' => false, 'error' => 'Already reviewed this item'], 400);
}

try {
    $pdo->beginTransaction();
    
    $insertStmt = $pdo->prepare('
        INSERT INTO product_reviews (order_item_id, rating, review_text) 
        VALUES (?, ?, ?)
    ');
    $insertStmt->execute([$orderItemId, $rating, $reviewText]);
    
    $pdo->commit();
    
    jsonResponse([
        'success' => true, 
        'message' => 'Review submitted successfully!',
        'review_id' => (int)$pdo->lastInsertId()
    ]);
    
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollback();
    jsonResponse(['success' => false, 'error' => 'Failed to submit review'], 500);
}
?>
