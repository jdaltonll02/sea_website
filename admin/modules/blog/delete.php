<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('blog');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/modules/blog/index.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('DELETE FROM blog_posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    log_activity('delete', 'blog_post', $id);
    flash('success', 'Blog post deleted.');
}

redirect('/admin/modules/blog/index.php');
