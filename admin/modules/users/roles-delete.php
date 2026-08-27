<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('users');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/users/roles.php');
}
require_csrf();

$pdo = db();
$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM roles WHERE id = :id');
$stmt->execute(['id' => $id]);
$role = $stmt->fetch();

if (!$role) {
    flash('error', 'Role not found.');
} else {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = :id');
    $countStmt->execute(['id' => $id]);
    $inUse = (int) $countStmt->fetchColumn();

    if ($inUse > 0) {
        flash('error', "Cannot delete this role — {$inUse} user(s) are still assigned to it. Reassign them first.");
    } else {
        $pdo->prepare('DELETE FROM roles WHERE id = :id')->execute(['id' => $id]);
        log_activity('delete', 'role', $id);
        flash('success', 'Role deleted.');
    }
}

redirect('/admin/modules/users/roles.php');
