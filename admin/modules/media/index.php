<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('media');
$pdo = db();

$relatedTypeOptions = ['church', 'organization', 'event', 'blog_post', 'general'];
$typeOptions = ['image', 'video'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $relatedType = in_array($_POST['related_type'] ?? '', $relatedTypeOptions, true) ? $_POST['related_type'] : 'general';
    $relatedId = trim($_POST['related_id'] ?? '') !== '' ? (int) $_POST['related_id'] : null;
    $altText = trim($_POST['alt_text'] ?? '');

    $filePath = handle_file_upload('media_file', 'gallery', ['jpg', 'jpeg', 'png', 'webp', 'mp4']);

    if (!$filePath) {
        $errors[] = 'Please choose a file to upload.';
    }

    if (empty($errors)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $type = $ext === 'mp4' ? 'video' : 'image';

        $stmt = $pdo->prepare(
            'INSERT INTO media_gallery (title, file_path, type, related_type, related_id, uploaded_by, alt_text)
             VALUES (:title, :file_path, :type, :related_type, :related_id, :uploaded_by, :alt_text)'
        );
        $stmt->execute([
            'title' => $title ?: null,
            'file_path' => $filePath,
            'type' => $type,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'uploaded_by' => current_user()['id'],
            'alt_text' => $altText ?: null,
        ]);

        $newId = (int) $pdo->lastInsertId();
        log_activity('create', 'media_gallery', $newId);
        flash('success', 'Media uploaded successfully.');
        redirect('/admin/modules/media/index.php');
    }
}

$filterRelatedType = in_array($_GET['related_type'] ?? '', $relatedTypeOptions, true) ? $_GET['related_type'] : '';
$filterType = in_array($_GET['type'] ?? '', $typeOptions, true) ? $_GET['type'] : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($filterRelatedType !== '') {
    $where[] = 'related_type = :related_type';
    $params['related_type'] = $filterRelatedType;
}
if ($filterType !== '') {
    $where[] = 'type = :type';
    $params['type'] = $filterType;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) (function () use ($pdo, $whereSql, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM media_gallery $whereSql");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM media_gallery $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$media = $stmt->fetchAll();

$pageTitle = 'Media Library';
$activeModule = 'media';
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card saa-card mb-4">
  <div class="card-body">
    <h6 class="mb-3">Upload New Media</h6>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">File</label>
          <input type="file" name="media_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.mp4" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Title (optional)</label>
          <input type="text" name="title" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Related To</label>
          <select name="related_type" class="form-select">
            <?php foreach ($relatedTypeOptions as $opt): ?>
              <option value="<?= e($opt) ?>" <?= $opt === 'general' ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $opt))) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Related ID (optional)</label>
          <input type="number" name="related_id" class="form-control" min="1">
        </div>
        <div class="col-md-2">
          <label class="form-label">Alt Text</label>
          <input type="text" name="alt_text" class="form-control">
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn saa-btn-accent"><i class="bi bi-upload me-1"></i>Upload</button>
      </div>
    </form>
  </div>
</div>

<form method="get" class="d-flex flex-wrap gap-2 mb-3">
  <select name="related_type" class="form-select w-auto" onchange="this.form.submit()">
    <option value="">All Related Types</option>
    <?php foreach ($relatedTypeOptions as $opt): ?>
      <option value="<?= e($opt) ?>" <?= $filterRelatedType === $opt ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $opt))) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="type" class="form-select w-auto" onchange="this.form.submit()">
    <option value="">All Types</option>
    <?php foreach ($typeOptions as $opt): ?>
      <option value="<?= e($opt) ?>" <?= $filterType === $opt ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button type="submit" class="btn btn-outline-secondary">Filter</button></noscript>
</form>

<?php if (empty($media)): ?>
  <div class="card saa-card"><div class="card-body text-center text-muted py-4">No media found.</div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($media as $item): ?>
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <div class="card saa-card h-100">
          <?php if ($item['type'] === 'video'): ?>
            <video src="<?= e(upload_url($item['file_path'])) ?>" class="card-img-top" style="height:150px; object-fit:cover; background:#000;" muted controls></video>
          <?php else: ?>
            <img src="<?= e(upload_url($item['file_path'])) ?>" alt="<?= e($item['alt_text'] ?? $item['title'] ?? '') ?>" class="card-img-top" style="height:150px; object-fit:cover;">
          <?php endif; ?>
          <div class="card-body p-2">
            <p class="small mb-1 text-truncate" title="<?= e($item['title'] ?? '') ?>"><?= e($item['title'] ?: '(untitled)') ?></p>
            <p class="small text-muted mb-2"><?= e(ucwords(str_replace('_', ' ', $item['related_type']))) ?><?= $item['related_id'] ? ' #' . (int) $item['related_id'] : '' ?></p>
            <form action="delete.php" method="post" data-confirm="Delete this media file? This cannot be undone.">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
