<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('churches');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/churches/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$churchId = (int) ($_POST['church_id'] ?? 0);

if ($id) {
    $stmt = db()->prepare('DELETE FROM service_schedules WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'service_schedule', $id);
    flash('success', 'Schedule entry deleted.');
}

redirect('/admin/modules/churches/schedule.php?church_id=' . $churchId);
