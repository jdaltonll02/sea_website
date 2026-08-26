<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('clergy');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/clergy/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM clergy WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'clergy', $id);
    flash('success', 'Clergy record deleted.');
}

redirect('/admin/modules/clergy/index.php');
