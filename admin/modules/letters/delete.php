<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('letters');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/letters/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT file_path FROM administrative_letters WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $letter = $stmt->fetch();

    if ($letter) {
        if (!empty($letter['file_path'])) {
            $fullPath = UPLOADS_PATH . '/' . $letter['file_path'];
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
        }

        $del = $pdo->prepare('DELETE FROM administrative_letters WHERE id = :id');
        $del->execute(['id' => $id]);
        log_activity('delete', 'administrative_letter', $id);
        flash('success', 'Letter deleted.');
    }
}

redirect('/admin/modules/letters/index.php');
