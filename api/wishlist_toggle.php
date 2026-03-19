<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/_init.php';
requireMethod(['POST', 'DELETE']);

try {
    $payload = readJsonBody();
    $product_id = (int)($payload['product_id'] ?? $_POST['product_id'] ?? 0);
    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    $user_id = getUserId();
    if (!$user_id) {
        throw new Exception('Login required');
    }

    // Check if exists
    $checkStmt = $pdo->prepare('SELECT wishlist_id FROM wishlist_items WHERE user_id = ? AND product_id = ?');
    $checkStmt->execute([$user_id, $product_id]);
    
    if ($checkStmt->fetch()) {
        // Remove
        $stmt = $pdo->prepare('DELETE FROM wishlist_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user_id, $product_id]);
        $action = 'removed';
        $in_wishlist = false;
    } else {
        // Add (check product exists)
        $prodStmt = $pdo->prepare('SELECT product_id FROM product_master WHERE product_id = ? AND is_active = 1');
        $prodStmt->execute([$product_id]);
        if (!$prodStmt->fetch()) {
            throw new Exception('Product not found');
        }
        
        $stmt = $pdo->prepare('INSERT INTO wishlist_items (user_id, product_id) VALUES (?, ?)');
        $stmt->execute([$user_id, $product_id]);
        $action = 'added';
        $in_wishlist = true;
    }

    jsonResponse([
        'success' => true, 
        'message' => "Product $action to wishlist!",
        'in_wishlist' => $in_wishlist,
        'product_id' => $product_id
    ]);

} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
}
?>

