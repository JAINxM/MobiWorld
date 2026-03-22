<?php
require_once __DIR__ . '/_init.php';
requireMethod('POST');

// Admin-only
ensureSessionStarted();
if (!isset($_SESSION['admin_logged_in'])) {
    jsonResponse(['success' => false, 'error' => 'Admin access required'], 403);
}

$input = readJsonBody();
$reviewId = isset($input['review_id']) ? (int)$input['review_id'] : 0;

if ($reviewId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Invalid review ID'], 400);
}

try {
    $stmt = $pdo->prepare('UPDATE product_reviews SET is_active = 0 WHERE review_id = ?');
    $result = $stmt->execute([$reviewId]);
    
    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'error' => 'Review not found'], 404);
    }
    
    jsonResponse(['success' => true, 'message' => 'Review deleted successfully']);
    
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to delete review'], 500);
}
?>
