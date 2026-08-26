<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('employees');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/employees/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM employees WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'employee', $id);
    flash('success', 'Employee deleted.');
}

redirect('/admin/modules/employees/index.php');
