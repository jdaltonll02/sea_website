<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';

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

$name = trim($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || !$email || $message === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in your name, a valid email, and a message.']);
    exit;
}

$stmt = db()->prepare(
    'INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (:name, :email, :phone, :subject, :message)'
);
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'phone' => $phone ?: null,
    'subject' => $subject ?: null,
    'message' => $message,
]);

$notifyTo = setting('contact_email');
if ($notifyTo) {
    send_mail(
        $notifyTo,
        'Archdeaconry Office',
        'New Contact Form Message' . ($subject ? ": $subject" : ''),
        nl2br(e($message)) . '<p>From: ' . e($name) . ' (' . e($email) . ')</p>',
        $email,
        $name
    );
}

echo json_encode(['success' => true, 'message' => 'Thank you for reaching out. We will respond as soon as possible.']);
