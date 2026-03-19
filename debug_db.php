<?php
require_once 'includes/config/db.php';

echo "<h1>DB Debug</h1>";
echo "<p>Connected: " . (isset($pdo) ? 'YES' : 'NO') . "</p>";

try {
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $row = $stmt->fetch();
    echo "<p>Current DB: " . $row['db'] . "</p>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_master'");
    $tables = $stmt->fetchAll();
    echo "<p>user_master table exists: " . (count($tables) ? 'YES' : 'NO') . "</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_master");
    $count = $stmt->fetch()['count'];
    echo "<p>user_master rows: $count</p>";
    
    $stmt = $pdo->query("SELECT * FROM user_master ORDER BY user_id DESC LIMIT 5");
    echo "<h2>Recent Users:</h2><pre>" . print_r($stmt->fetchAll(), true) . "</pre>";
    
    echo "<h2>Test INSERT:</h2>";
    $stmt = $pdo->prepare("INSERT INTO user_master (full_name, email, password_hash, is_active) VALUES (?, ?, ?, 1)");
    $result = $stmt->execute(['Debug User', 'debug@test.com', password_hash('debug', PASSWORD_BCRYPT)]);
    echo "Test INSERT: " . ($result ? 'SUCCESS ID: ' . $pdo->lastInsertId() : 'FAILED') . "</p>";
    
} catch (Exception $e) {
    echo "<p>ERROR: " . $e->getMessage() . "</p>";
}
?>

