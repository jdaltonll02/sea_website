<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('testimonials');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/testimonials/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM testimonies WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'testimony', $id);
    flash('success', 'Testimony deleted.');
}

redirect('/admin/modules/testimonials/index.php');
