<?php

require_once __DIR__ . '/_init.php';
requireMethod('POST');

$input = readJsonBody();
$emailRaw = $input['email'] ?? null;
$password = $input['password'] ?? null;

if (!is_string($emailRaw) || !is_string($password)) {
    jsonResponse(['success' => false, 'error' => 'Missing email/password'], 400);
}

$email = filter_var(trim($emailRaw), FILTER_VALIDATE_EMAIL);
if ($email === false) {
    jsonResponse(['success' => false, 'error' => 'Invalid email'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, password_hash, is_active FROM user_master WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['is_active'] !== 1 || !password_verify($password, (string)$user['password_hash'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
    }

    $pdo->prepare('UPDATE user_master SET last_login = NOW() WHERE user_id = ?')->execute([(int)$user['user_id']]);

    ensureSessionStarted();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['user_id'];

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => (int)$user['user_id'],
            'full_name' => (string)$user['full_name'],
            'email' => (string)$user['email'],
        ],
    ]);
} catch (Throwable $e) {
if (defined('APP_DEBUG') && APP_DEBUG) {
    jsonResponse(['success' => false, 'error' => 'Login failed: ' . $e->getMessage()], 500);
} else {
    jsonResponse(['success' => false, 'error' => 'Login failed'], 500);
}
}