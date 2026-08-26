<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('media');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/media/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT file_path FROM media_gallery WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();

    if ($item) {
        $fullPath = UPLOADS_PATH . '/' . $item['file_path'];
        if (is_file($fullPath)) {
            unlink($fullPath);
        }

        $del = $pdo->prepare('DELETE FROM media_gallery WHERE id = :id');
        $del->execute(['id' => $id]);
        log_activity('delete', 'media_gallery', $id);
        flash('success', 'Media deleted.');
    } else {
        flash('error', 'Media not found.');
    }
}

redirect('/admin/modules/media/index.php');
