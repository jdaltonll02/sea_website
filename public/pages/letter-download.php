<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM administrative_letters WHERE id = :id');
$stmt->execute(['id' => $id]);
$letter = $stmt->fetch();

if (!$letter) {
    http_response_code(404);
    exit('Document not found.');
}

if ($letter['visibility'] !== 'public' && !is_logged_in()) {
    http_response_code(403);
    exit('Please log in to view this document.');
}

$fullPath = UPLOADS_PATH . '/' . ltrim($letter['file_path'], '/');
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File not found.');
}

$safeName = preg_replace('/[^A-Za-z0-9 _-]/', '', $letter['title'] ?: 'document');

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $safeName . '.pdf"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
