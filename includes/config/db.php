<?php
// Database configuration - EDIT THESE FOR YOUR SETUP
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'mobiworld');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('APP_DEBUG', true);

try {
    $pdo = new PDO(
    'mysql:host=' . DB_HOST . ';port=3307;dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $msg = APP_DEBUG ? ('DB Connection failed: ' . $e->getMessage()) : 'DB Connection failed';
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function ensureSessionStarted(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    ensureSessionStarted();
    return isset($_SESSION['user_id']);
}

function getCurrentUserId(): ?int {
    ensureSessionStarted();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
}

function appBasePath(): string {
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $appRoot = realpath(dirname(__DIR__, 2));
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($appRoot && $documentRoot) {
        $normalizedAppRoot = str_replace('\\', '/', $appRoot);
        $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');

        if (strpos($normalizedAppRoot, $normalizedDocumentRoot) === 0) {
            $relativePath = trim(substr($normalizedAppRoot, strlen($normalizedDocumentRoot)), '/');
            $basePath = $relativePath === '' ? '' : '/' . $relativePath;
            return $basePath;
        }
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = rtrim(dirname($scriptName), '/.');
    return $basePath === '/' ? '' : $basePath;
}

function appUrl(string $path = ''): string {
    $basePath = rtrim(appBasePath(), '/');
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $basePath === '' ? '/' : $basePath;
    }

    return ($basePath === '' ? '' : $basePath) . '/' . $trimmedPath;
}
