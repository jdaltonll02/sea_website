<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('bishops');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/bishops/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM dioceses_bishops WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'bishop', $id);
    flash('success', 'Bishop record deleted.');
}

redirect('/admin/modules/bishops/index.php');
