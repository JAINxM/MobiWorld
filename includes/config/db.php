<?php
// Database configuration - EDIT THESE FOR YOUR SETUP
define('DB_HOST', getenv('DB_HOST') ?: 'interchange.proxy.rlwy.net');
define('DB_NAME', getenv('DB_NAME') ?: 'railway');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'nCQNXUEoDMgzPDjEPuiOhssMINopWAbr');
define('DB_PORT', getenv('DB_PORT') ?: 17229);
define('DB_SOCK', getenv('DB_SOCK') ?: '/home/runner/mysql-run/mysql.sock');

define('APP_DEBUG', true);

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO(
    $dsn,
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

function ensureProductReviewsTable(PDO $pdo): void {
    static $initialized = false;
    if ($initialized) {
        return;
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS product_reviews (
                review_id INT AUTO_INCREMENT PRIMARY KEY,
                order_item_id INT NOT NULL,
                rating INT NOT NULL,
                review_text TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_active TINYINT(1) DEFAULT 1,
                UNIQUE KEY unique_review_per_item (order_item_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $columnsStmt = $pdo->query('SHOW COLUMNS FROM product_reviews');
        $existingColumns = [];
        foreach ($columnsStmt->fetchAll() as $column) {
            $existingColumns[] = (string) $column['Field'];
        }

        if (!in_array('is_active', $existingColumns, true)) {
            $pdo->exec('ALTER TABLE product_reviews ADD COLUMN is_active TINYINT(1) DEFAULT 1');
        }

        if (!in_array('created_at', $existingColumns, true)) {
            $pdo->exec('ALTER TABLE product_reviews ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        }
    } catch (Throwable $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Review table setup failed: ' . $e->getMessage());
        }
    }

    $initialized = true;
}

ensureProductReviewsTable($pdo);

function ensureOrdersExtendedColumns(PDO $pdo): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    try {
        $columnsStmt = $pdo->query('SHOW COLUMNS FROM orders');
        $existingColumns = [];
        foreach ($columnsStmt->fetchAll() as $column) {
            $existingColumns[] = (string) $column['Field'];
        }

        if (!in_array('payment_method', $existingColumns, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(32) NULL");
        }
        if (!in_array('payment_status', $existingColumns, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status VARCHAR(20) NULL");
        }
        if (!in_array('recipient_name', $existingColumns, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN recipient_name VARCHAR(255) NULL");
        }
        if (!in_array('shipping_address', $existingColumns, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_address TEXT NULL");
        }
        if (!in_array('razorpay_order_id', $existingColumns, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN razorpay_order_id VARCHAR(64) NULL");
        }
        if (!in_array('razorpay_payment_id', $existingColumns, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN razorpay_payment_id VARCHAR(64) NULL");
        }
        if (!in_array('paid_at', $existingColumns, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN paid_at TIMESTAMP NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Orders table migration failed: ' . $e->getMessage());
        }
    }

    $initialized = true;
}

ensureOrdersExtendedColumns($pdo);

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
