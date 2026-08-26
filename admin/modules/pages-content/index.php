<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('pages-content');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $pageKey = trim($_POST['page_key'] ?? '');
    $title = trim($_POST['title'] ?? '');

    if ($pageKey === '' || !preg_match('/^[a-z0-9_]+$/', $pageKey)) {
        flash('error', 'Page key must contain only lowercase letters, numbers, and underscores.');
        redirect('/admin/modules/pages-content/index.php');
    }

    $check = $pdo->prepare('SELECT id FROM pages_content WHERE page_key = :page_key');
    $check->execute(['page_key' => $pageKey]);
    if ($check->fetch()) {
        flash('error', 'A content block with that page key already exists.');
        redirect('/admin/modules/pages-content/index.php');
    }

    $stmt = $pdo->prepare('INSERT INTO pages_content (page_key, title, updated_by) VALUES (:page_key, :title, :updated_by)');
    $stmt->execute([
        'page_key' => $pageKey,
        'title' => $title,
        'updated_by' => current_user()['id'] ?? null,
    ]);

    $newId = (int) $pdo->lastInsertId();
    log_activity('create', 'pages_content', $newId);
    flash('success', 'Content block created. Add the body content below.');
    redirect('/admin/modules/pages-content/form.php?page_key=' . urlencode($pageKey));
}

$blocks = $pdo->query('SELECT * FROM pages_content ORDER BY page_key')->fetchAll();

$pageTitle = 'Static Pages';
$activeModule = 'pages-content';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="card admin-stat-card mb-4">
  <div class="card-body">
    <h5 class="mb-3">Add New Content Block</h5>
    <form method="post" class="row g-3 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-4">
        <label class="form-label">Page Key</label>
        <input type="text" name="page_key" class="form-control" placeholder="e.g. about_vision" pattern="[a-z0-9_]+" required>
        <div class="form-text">Lowercase letters, numbers, and underscores only.</div>
      </div>
      <div class="col-md-5">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" placeholder="e.g. Our Vision">
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn saa-btn-accent w-100"><i class="bi bi-plus-lg me-1"></i>Create Block</button>
      </div>
    </form>
  </div>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Page Key</th><th>Title</th><th>Last Updated</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($blocks)): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">No content blocks found.</td></tr>
        <?php else: foreach ($blocks as $block): ?>
          <tr>
            <td><code><?= e($block['page_key']) ?></code></td>
            <td><?= e($block['title'] ?? '') ?></td>
            <td><?= e(format_date($block['updated_at'], 'M j, Y g:i A')) ?></td>
            <td class="text-end">
              <a href="form.php?page_key=<?= e(urlencode($block['page_key'])) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
