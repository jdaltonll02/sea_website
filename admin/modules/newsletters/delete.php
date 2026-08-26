<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('newsletters');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/newsletters/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM newsletters WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'newsletter', $id);
    flash('success', 'Newsletter deleted.');
}

redirect('/admin/modules/newsletters/index.php');
