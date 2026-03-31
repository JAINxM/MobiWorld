<?php

// Ensure APIs always return valid JSON even if PHP warnings/notices happen.
// Captures accidental output and converts fatal errors to JSON responses.
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
error_reporting(E_ALL);

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

if (ob_get_level() === 0) {
    ob_start();
}

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if (!$err) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array((int) ($err['type'] ?? 0), $fatalTypes, true)) {
        return;
    }

    if (ob_get_level() > 0) {
        ob_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    $msg = (defined('APP_DEBUG') && APP_DEBUG)
        ? ('Fatal error: ' . (string) ($err['message'] ?? 'Unknown') . ' in ' . (string) ($err['file'] ?? '') . ':' . (string) ($err['line'] ?? ''))
        : 'Server error';

    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
});

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once dirname(__DIR__) . '/includes/config/db.php';

function jsonResponse(array $data, int $status = 200): void {
    if (ob_get_level() > 0) {
        ob_clean();
    }
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
