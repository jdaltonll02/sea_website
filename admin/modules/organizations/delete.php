<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('organizations');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/organizations/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM organizations WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'organization', $id);
    flash('success', 'Organization deleted.');
}

redirect('/admin/modules/organizations/index.php');
