<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}
$group = trim($_POST['group_name'] ?? '') ?: null;

$pdo = db();
$existing = $pdo->prepare('SELECT id FROM subscribers WHERE email = :email');
$existing->execute(['email' => $email]);

if ($existing->fetch()) {
    echo json_encode(['success' => true, 'message' => 'You are already subscribed. Thank you!']);
    exit;
}

$token = bin2hex(random_bytes(24));
$stmt = $pdo->prepare(
    'INSERT INTO subscribers (email, group_name, unsubscribe_token) VALUES (:email, :group_name, :token)'
);
$stmt->execute(['email' => $email, 'group_name' => $group, 'token' => $token]);

// PHPMailer confirmation send would go here once SMTP credentials are configured
// (see /config/env.php + SMTP_* variables in .env).

echo json_encode(['success' => true, 'message' => 'Thank you for subscribing! You will receive our next newsletter.']);
