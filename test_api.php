<?php
header('Content-Type: application/json');
require_once 'includes/config/db.php';
require_once 'api/_init.php';

echo "APP_DEBUG: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'TRUE' : 'FALSE') : 'NOT DEFINED') . "\n";

try {
    $stmt = $pdo->query("SELECT * FROM product_master LIMIT 1");
    $row = $stmt->fetch();
    echo "DB OK - Product: " . json_encode($row) . "\n";
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}

echo "PDO: " . (isset($pdo) ? 'OK' : 'NOT SET') . "\n";
?>

