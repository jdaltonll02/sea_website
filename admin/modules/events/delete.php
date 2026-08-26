<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('events');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/events/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM events WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'event', $id);
    flash('success', 'Event deleted.');
}

redirect('/admin/modules/events/index.php');
