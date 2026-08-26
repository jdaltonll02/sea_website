<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('pages-content');
$pdo = db();

$pageKey = trim($_GET['page_key'] ?? $_POST['page_key'] ?? '');
if ($pageKey === '') {
    flash('error', 'No content block specified.');
    redirect('/admin/modules/pages-content/index.php');
}

$stmt = $pdo->prepare('SELECT * FROM pages_content WHERE page_key = :page_key');
$stmt->execute(['page_key' => $pageKey]);
$block = $stmt->fetch();
if (!$block) {
    flash('error', 'Content block not found.');
    redirect('/admin/modules/pages-content/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $body = $_POST['body'] ?? '';

    if (empty($errors)) {
        $upd = $pdo->prepare(
            'UPDATE pages_content SET title = :title, body = :body, updated_by = :updated_by WHERE page_key = :page_key'
        );
        $upd->execute([
            'title' => $title,
            'body' => $body,
            'updated_by' => current_user()['id'] ?? null,
            'page_key' => $pageKey,
        ]);

        log_activity('update', 'pages_content', (int) $block['id']);
        flash('success', 'Content block saved successfully.');
        redirect('/admin/modules/pages-content/index.php');
    }

    $block['title'] = $title;
    $block['body'] = $body;
}

$needsRichText = true;
$pageTitle = 'Edit Content Block';
$activeModule = 'pages-content';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="card admin-stat-card" id="pageContentForm">
  <div class="card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="page_key" value="<?= e($pageKey) ?>">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Page Key</label>
        <input type="text" class="form-control" value="<?= e($pageKey) ?>" readonly disabled>
      </div>
      <div class="col-md-6">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="<?= e($block['title'] ?? '') ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Body</label>
        <div id="bodyEditor" style="min-height:250px; background:#fff;"><?= $block['body'] ?? '' ?></div>
        <textarea name="body" id="bodyInput" class="d-none"><?= e($block['body'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Content Block</button>
  </div>
</form>

<script>
  var quill = new Quill('#bodyEditor', { theme: 'snow' });
  document.getElementById('pageContentForm').addEventListener('submit', function () {
    document.getElementById('bodyInput').value = quill.root.innerHTML;
  });
</script>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
