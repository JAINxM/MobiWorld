<?php
require_once 'includes/config/db.php';

$input = [
    'full_name' => 'om',
    'email' => 'om@gmail.com',
    'password' => '123456' // 6+ chars
];

$fullName = trim($input['full_name']);
$emailRaw = $input['email'];
$password = $input['password'];

$email = filter_var(trim($emailRaw), FILTER_VALIDATE_EMAIL);

echo "Full Name: $fullName\n";
echo "Email: $email\n";
echo "Password length: " . strlen($password) . "\n";

try {
    $stmt = $pdo->prepare('SELECT user_id FROM user_master WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $exists = $stmt->fetch() ? 'EXISTS' : 'NOT EXISTS';
    echo "Email $exists in DB\n";
    
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "Hash generated: " . substr($hash, 0, 20) . "...\n";

    $stmt = $pdo->prepare('INSERT INTO user_master (full_name, email, password_hash, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())');
    $stmt->execute([$fullName, $email, $hash]);
    $userId = $pdo->lastInsertId();
    echo "INSERT SUCCESS! User ID: $userId\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
}
?>

