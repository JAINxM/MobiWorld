<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/_init.php';
requireLogin();
$user_id = getCurrentUserId();

$input = json_decode(file_get_contents('php://input'), true);
$full_name = isset($input['full_name']) ? trim($input['full_name']) : null;
$mobile = isset($input['mobile']) ? trim($input['mobile']) : null;

if ($full_name === null && $mobile === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Nothing to update']);
    exit;
}

$updates = [];
$params = [];
if ($full_name !== null) {
    $updates[] = 'full_name = ?';
    $params[] = $full_name;
}
if ($mobile !== null) {
    $updates[] = 'mobile = ?';
    $params[] = $mobile;
}
$params[] = $user_id;

try {
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'message' => 'Profile updated']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed']);
}
?>

