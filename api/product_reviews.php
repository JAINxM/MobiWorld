<?php
require_once __DIR__ . '/_init.php';
requireMethod('GET');

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    jsonResponse(['success' => false, 'error' => 'Missing product id'], 400);
}

try {
    // Get average rating and count
    $avgStmt = $pdo->prepare('
        SELECT 
            AVG(r.rating) as avg_rating,
            COUNT(r.review_id) as total_reviews
        FROM product_reviews r 
        JOIN order_items oi ON oi.order_item_id = r.order_item_id
        WHERE oi.product_id = ? AND r.is_active = 1
    ');
    $avgStmt->execute([$productId]);
    $stats = $avgStmt->fetch();
    
    $avgRating = $stats ? round((float)$stats['avg_rating'], 1) : 0;
    $totalReviews = (int)($stats['total_reviews'] ?? 0);
    
    // Get individual reviews (recent first, limit 10)
    $reviewsStmt = $pdo->prepare('
        SELECT 
            r.review_id, r.rating, r.review_text, r.created_at,
            u.full_name as reviewer_name,
            p.name as product_name
        FROM product_reviews r 
        JOIN order_items oi ON oi.order_item_id = r.order_item_id
        JOIN orders o ON o.order_id = oi.order_id
        JOIN user_master u ON u.user_id = o.user_id
        JOIN product_master p ON p.product_id = oi.product_id
        WHERE oi.product_id = ? AND r.is_active = 1
        ORDER BY r.created_at DESC 
        LIMIT 10
    ');
    $reviewsStmt->execute([$productId]);
    $reviews = $reviewsStmt->fetchAll();
    
    // Format dates
    foreach ($reviews as &$review) {
        $review['created_at'] = date('M d, Y', strtotime($review['created_at']));
        $review['rating'] = (int)$review['rating'];
    }
    
    jsonResponse([
        'success' => true,
        'avg_rating' => $avgRating,
        'total_reviews' => $totalReviews,
        'reviews' => $reviews
    ]);
    
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch reviews'], 500);
}
?>
