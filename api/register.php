<?php

require_once __DIR__ . '/_init.php';
requireMethod('POST');

$input = readJsonBody();
$fullName = isset($input['full_name']) && is_string($input['full_name']) ? trim($input['full_name']) : '';
$emailRaw = $input['email'] ?? null;
$password = $input['password'] ?? null;

if ($fullName === '' || !is_string($emailRaw) || !is_string($password)) {
    jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
}

$email = filter_var(trim($emailRaw), FILTER_VALIDATE_EMAIL);
if ($email === false) {
    jsonResponse(['success' => false, 'error' => 'Invalid email'], 400);
}
if (strlen($password) < 6) {
    jsonResponse(['success' => false, 'error' => 'Password must be at least 6 characters'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT user_id FROM user_master WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Email already registered'], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    if ($hash === false) {
        jsonResponse(['success' => false, 'error' => 'Failed to hash password'], 500);
    }

    $stmt = $pdo->prepare('INSERT INTO user_master (full_name, email, password_hash, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())');
    $stmt->execute([$fullName, $email, $hash]);
    $userId = (int)$pdo->lastInsertId();

    jsonResponse([
        'success' => true,
        'message' => 'User registered',
        'user' => [
            'id' => $userId,
            'full_name' => $fullName,
            'email' => $email,
        ],
    ], 201);
} catch (Throwable $e) {
if (defined('APP_DEBUG') && APP_DEBUG) {
    jsonResponse(['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()], 500);
} else {
    jsonResponse(['success' => false, 'error' => 'Registration failed'], 500);
}
}