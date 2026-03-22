<?php
require_once __DIR__ . '/_init.php';
requireMethod('GET');

// Admin-only endpoint
ensureSessionStarted();
if (!isset($_SESSION['admin_logged_in'])) {
    jsonResponse(['success' => false, 'error' => 'Admin access required'], 403);
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    // Count total
    $countStmt = $pdo->query('SELECT COUNT(*) as total FROM product_reviews WHERE is_active = 1');
    $total = (int)$countStmt->fetch()['total'];
    
    // Fetch paginated reviews
    $stmt = $pdo->prepare('
        SELECT 
            r.review_id, r.rating, r.review_text, r.created_at, r.is_active,
            oi.order_item_id, oi.product_id, oi.price_at_time,
            u.full_name as reviewer, u.email,
            p.name as product_name
        FROM product_reviews r 
        JOIN order_items oi ON oi.order_item_id = r.order_item_id
        JOIN orders o ON o.order_id = oi.order_id
        JOIN user_master u ON u.user_id = o.user_id
        JOIN product_master p ON p.product_id = oi.product_id
        WHERE r.is_active = 1
        ORDER BY r.created_at DESC 
        LIMIT ? OFFSET ?
    ');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll();
    
    foreach ($reviews as &$review) {
        $review['created_at'] = date('M d, Y H:i', strtotime($review['created_at']));
    }
    
    jsonResponse([
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
    
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to fetch admin reviews'], 500);
}
?>
