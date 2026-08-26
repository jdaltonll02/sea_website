<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('testimonials');
$pdo = db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $churchId = (int) ($_POST['church_id'] ?? 0) ?: null;
    $message = trim($_POST['message'] ?? '');
    $approved = isset($_POST['approved']) ? 1 : 0;

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($message === '') {
        $errors[] = 'Testimony message is required.';
    }

    if (empty($errors)) {
        $photoPath = handle_file_upload('photo', 'testimonials', ['jpg', 'jpeg', 'png', 'webp']);

        $stmt = $pdo->prepare(
            'INSERT INTO testimonies (name, church_id, message, photo, approved)
             VALUES (:name, :church_id, :message, :photo, :approved)'
        );
        $stmt->execute([
            'name' => $name,
            'church_id' => $churchId,
            'message' => $message,
            'photo' => $photoPath,
            'approved' => $approved,
        ]);

        $newId = (int) $pdo->lastInsertId();
        log_activity('create', 'testimony', $newId);
        flash('success', 'Testimony added successfully.');
        redirect('/admin/modules/testimonials/index.php');
    }
}

$churches = $pdo->query('SELECT id, name FROM churches ORDER BY name')->fetchAll();

$pageTitle = 'Add Testimony Manually';
$activeModule = 'testimonials';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Church (optional)</label>
        <select name="church_id" class="form-select">
          <option value="">— None —</option>
          <?php foreach ($churches as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) ($_POST['church_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label">Testimony Message</label>
        <textarea name="message" class="form-control" rows="5" required><?= e($_POST['message'] ?? '') ?></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label">Photo (optional)</label>
        <input type="file" name="photo" class="form-control" accept="image/*">
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <div class="form-check">
          <input type="checkbox" name="approved" id="approvedCheck" class="form-check-input" value="1" checked>
          <label class="form-check-label" for="approvedCheck">Publish immediately (staff-entered testimonies are pre-vetted)</label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Testimony</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
