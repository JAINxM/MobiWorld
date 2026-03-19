<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once dirname(__DIR__) . '/includes/config/db.php';

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonBody(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function requireMethod(string|array $method): void {
    $requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $allowedMethods = is_array($method) ? $method : [$method];
    $allowedMethods = array_map(static fn ($item) => strtoupper((string)$item), $allowedMethods);

    if (!in_array($requestMethod, $allowedMethods, true)) {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

function getActiveCartId(PDO $pdo, int $userId): ?int {
    $stmt = $pdo->prepare('SELECT cart_id FROM shopping_cart WHERE user_id = ? AND is_active = 1 ORDER BY cart_id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['cart_id'] : null;
}

function getOrCreateActiveCartId(PDO $pdo, int $userId): int {
    $cartId = getActiveCartId($pdo, $userId);
    if ($cartId !== null) return $cartId;

$stmt = $pdo->prepare('INSERT INTO shopping_cart (user_id, is_active, created_at) VALUES (?, 1, NOW())');
    $stmt->execute([$userId]);
    return (int)$pdo->lastInsertId();
}

function currentProductPrice(array $productRow): float {
    $regular = (float)$productRow['regular_price'];
    $discounted = $productRow['discounted_price'] !== null ? (float)$productRow['discounted_price'] : 0.0;
    return $discounted > 0 ? $discounted : $regular;
}

function ensureLoggedIn(): int {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User login required');
    }
    return (int)$_SESSION['user_id'];
}

function getUserId(): ?int {
    try {
        return ensureLoggedIn();
    } catch (Exception $e) {
        return null;
    }
}
