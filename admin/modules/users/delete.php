<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/users/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id && $id === (int) (current_user()['id'] ?? 0)) {
    flash('error', 'You cannot delete your own account.');
    redirect('/admin/modules/users/index.php');
}

if ($id) {
    $stmt = db()->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'user', $id);
    flash('success', 'User deleted.');
}

redirect('/admin/modules/users/index.php');
