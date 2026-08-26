<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('letters');
$pdo = db();

$categoryLabels = [
    'pastoral_letter' => 'Pastoral Letter',
    'circular' => 'Circular',
    'policy' => 'Policy',
    'minutes' => 'Minutes',
];
$visibilityLabels = [
    'public' => 'Public',
    'clergy_only' => 'Clergy Only',
    'staff_only' => 'Staff Only',
];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$letter = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM administrative_letters WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $letter = $stmt->fetch();
    if (!$letter) {
        flash('error', 'Letter not found.');
        redirect('/admin/modules/letters/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $issuedBy = trim($_POST['issued_by'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $visibility = trim($_POST['visibility'] ?? '');
    $publishedAt = trim($_POST['published_at'] ?? '');
    $publishedAt = $publishedAt !== '' ? str_replace('T', ' ', $publishedAt) . ':00' : null;

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!isset($categoryLabels[$category])) {
        $errors[] = 'Please select a valid category.';
    }
    if (!isset($visibilityLabels[$visibility])) {
        $errors[] = 'Please select a valid visibility.';
    }
    if (!$letter && empty($_FILES['file_path']['name'])) {
        $errors[] = 'A PDF file is required.';
    }

    if (empty($errors)) {
        $filePath = handle_file_upload('file_path', 'letters', ['pdf']);

        if ($letter) {
            $sql = 'UPDATE administrative_letters SET title = :title, description = :description, issued_by = :issued_by,
                    category = :category, visibility = :visibility, published_at = :published_at';
            if ($filePath) {
                $sql .= ', file_path = :file_path';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO administrative_letters (title, description, issued_by, category, visibility, published_at, file_path)
                    VALUES (:title, :description, :issued_by, :category, :visibility, :published_at, :file_path)';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'title' => $title, 'description' => $description, 'issued_by' => $issuedBy,
            'category' => $category, 'visibility' => $visibility, 'published_at' => $publishedAt,
        ];
        if ($letter) {
            $bind['id'] = $id;
        }
        if ($filePath) {
            $bind['file_path'] = $filePath;
        }
        $stmt->execute($bind);

        $newId = $letter ? $id : (int) $pdo->lastInsertId();
        log_activity($letter ? 'update' : 'create', 'administrative_letter', $newId);
        flash('success', 'Letter saved successfully.');
        redirect('/admin/modules/letters/index.php');
    }
}

$publishedAtValue = '';
if (!empty($letter['published_at'])) {
    $ts = strtotime($letter['published_at']);
    $publishedAtValue = $ts ? date('Y-m-d\TH:i', $ts) : '';
} elseif (!empty($_POST['published_at'])) {
    $publishedAtValue = $_POST['published_at'];
}

$pageTitle = $letter ? 'Edit Letter' : 'Add Letter';
$activeModule = 'letters';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($letter): ?><input type="hidden" name="id" value="<?= (int) $letter['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="<?= e($_POST['title'] ?? $letter['title'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Issued By</label>
        <input type="text" name="issued_by" class="form-control" value="<?= e($_POST['issued_by'] ?? $letter['issued_by'] ?? '') ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? $letter['description'] ?? '') ?></textarea>
      </div>

      <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category" class="form-select" required>
          <option value="">— Select —</option>
          <?php $selCategory = $_POST['category'] ?? $letter['category'] ?? ''; ?>
          <?php foreach ($categoryLabels as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $selCategory === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Visibility</label>
        <select name="visibility" class="form-select" required>
          <?php $selVisibility = $_POST['visibility'] ?? $letter['visibility'] ?? 'public'; ?>
          <?php foreach ($visibilityLabels as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $selVisibility === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Published At</label>
        <input type="datetime-local" name="published_at" class="form-control" value="<?= e($publishedAtValue) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">PDF File<?= $letter ? '' : ' (required)' ?></label>
        <input type="file" name="file_path" class="form-control" accept="application/pdf" <?= $letter ? '' : 'required' ?>>
        <?php if (!empty($letter['file_path'])): ?>
          <div class="mt-2"><a href="<?= e(base_url('pages/letter-download.php?id=' . $letter['id'])) ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>View current file</a></div>
          <div class="form-text">Leave blank to keep the existing file.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Letter</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
