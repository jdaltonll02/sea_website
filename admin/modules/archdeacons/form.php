<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('archdeacons');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$archdeacon = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM archdeacons WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $archdeacon = $stmt->fetch();
    if (!$archdeacon) {
        flash('error', 'Archdeacon not found.');
        redirect('/admin/modules/archdeacons/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $termStart = trim($_POST['term_start'] ?? '') ?: null;
    $termEnd = trim($_POST['term_end'] ?? '') ?: null;
    $bio = trim($_POST['bio'] ?? '');
    $achievements = trim($_POST['achievements'] ?? '');
    $orderNumber = trim($_POST['order_number'] ?? '') !== '' ? (int) $_POST['order_number'] : null;

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($termStart === null) {
        $errors[] = 'Term start date is required.';
    }

    if (empty($errors)) {
        $photoPath = handle_file_upload('photo', 'archdeacons', ['jpg', 'jpeg', 'png', 'webp']);

        if ($archdeacon) {
            $sql = 'UPDATE archdeacons SET name = :name, term_start = :term_start, term_end = :term_end,
                    bio = :bio, achievements = :achievements, order_number = :order_number';
            if ($photoPath) {
                $sql .= ', photo = :photo';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO archdeacons (name, term_start, term_end, bio, achievements, order_number'
                    . ($photoPath ? ', photo' : '') . ')
                    VALUES (:name, :term_start, :term_end, :bio, :achievements, :order_number'
                    . ($photoPath ? ', :photo' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'name' => $name, 'term_start' => $termStart, 'term_end' => $termEnd,
            'bio' => $bio, 'achievements' => $achievements, 'order_number' => $orderNumber,
        ];
        if ($archdeacon) {
            $bind['id'] = $id;
        }
        if ($photoPath) {
            $bind['photo'] = $photoPath;
        }
        $stmt->execute($bind);

        $newId = $archdeacon ? $id : (int) $pdo->lastInsertId();
        log_activity($archdeacon ? 'update' : 'create', 'archdeacon', $newId);
        flash('success', 'Archdeacon saved successfully.');
        redirect('/admin/modules/archdeacons/index.php');
    }
}

$pageTitle = $archdeacon ? 'Edit Archdeacon' : 'Add Archdeacon';
$activeModule = 'archdeacons';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($archdeacon): ?><input type="hidden" name="id" value="<?= (int) $archdeacon['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($archdeacon['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Order Number</label>
        <input type="number" name="order_number" class="form-control" value="<?= e((string) ($archdeacon['order_number'] ?? '')) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Term Start</label>
        <input type="date" name="term_start" class="form-control" value="<?= e($archdeacon['term_start'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Term End</label>
        <input type="date" name="term_end" class="form-control" value="<?= e($archdeacon['term_end'] ?? '') ?>">
        <div class="form-text">Leave blank if this is the current archdeacon.</div>
      </div>

      <div class="col-12">
        <label class="form-label">Bio</label>
        <textarea name="bio" class="form-control" rows="4"><?= e($archdeacon['bio'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Achievements</label>
        <textarea name="achievements" class="form-control" rows="4"><?= e($archdeacon['achievements'] ?? '') ?></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label">Photo</label>
        <input type="file" name="photo" class="form-control" accept="image/*">
        <?php if (!empty($archdeacon['photo'])): ?>
          <img src="<?= e(upload_url($archdeacon['photo'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Archdeacon</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
