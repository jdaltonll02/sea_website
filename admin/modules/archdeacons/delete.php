<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('archdeacons');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/archdeacons/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM archdeacons WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'archdeacon', $id);
    flash('success', 'Archdeacon deleted.');
}

redirect('/admin/modules/archdeacons/index.php');
