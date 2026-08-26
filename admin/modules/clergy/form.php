<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('clergy');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$clergyMember = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM clergy WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $clergyMember = $stmt->fetch();
    if (!$clergyMember) {
        flash('error', 'Clergy record not found.');
        redirect('/admin/modules/clergy/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $orderType = trim($_POST['order_type'] ?? '');
    $ordinationDate = trim($_POST['ordination_date'] ?? '') ?: null;
    $homeChurchId = (int) ($_POST['home_church_id'] ?? 0) ?: null;
    $title = trim($_POST['title'] ?? '');
    $bioShort = trim($_POST['bio_short'] ?? '');
    $bioFull = trim($_POST['bio_full'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if (!in_array($orderType, ['priest', 'deacon', 'lay_reader'], true)) {
        $errors[] = 'A valid order type is required.';
    }
    if (!in_array($status, ['active', 'retired', 'deceased', 'transferred'], true)) {
        $errors[] = 'A valid status is required.';
    }

    if (empty($errors)) {
        $photoPath = handle_file_upload('photo', 'clergy', ['jpg', 'jpeg', 'png', 'webp']);

        if ($clergyMember) {
            $sql = 'UPDATE clergy SET full_name = :full_name, order_type = :order_type, ordination_date = :ordination_date,
                    home_church_id = :home_church_id, title = :title, bio_short = :bio_short, bio_full = :bio_full,
                    phone = :phone, email = :email, status = :status';
            if ($photoPath) {
                $sql .= ', photo = :photo';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO clergy (full_name, order_type, ordination_date, home_church_id, title, bio_short,
                    bio_full, phone, email, status' . ($photoPath ? ', photo' : '') . ')
                    VALUES (:full_name, :order_type, :ordination_date, :home_church_id, :title, :bio_short,
                    :bio_full, :phone, :email, :status' . ($photoPath ? ', :photo' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'full_name' => $fullName, 'order_type' => $orderType, 'ordination_date' => $ordinationDate,
            'home_church_id' => $homeChurchId, 'title' => $title, 'bio_short' => $bioShort, 'bio_full' => $bioFull,
            'phone' => $phone, 'email' => $email, 'status' => $status,
        ];
        if ($clergyMember) {
            $bind['id'] = $id;
        }
        if ($photoPath) {
            $bind['photo'] = $photoPath;
        }
        $stmt->execute($bind);

        $newId = $clergyMember ? $id : (int) $pdo->lastInsertId();
        log_activity($clergyMember ? 'update' : 'create', 'clergy', $newId);
        flash('success', 'Clergy record saved successfully.');
        redirect('/admin/modules/clergy/index.php');
    }
}

$churchOptions = $pdo->query('SELECT id, name FROM churches ORDER BY name')->fetchAll();

$pageTitle = $clergyMember ? 'Edit Clergy' : 'Add Clergy';
$activeModule = 'clergy';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($clergyMember): ?><input type="hidden" name="id" value="<?= (int) $clergyMember['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" placeholder="Rev., Ven., Canon" value="<?= e($clergyMember['title'] ?? '') ?>">
      </div>
      <div class="col-md-9">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= e($clergyMember['full_name'] ?? '') ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Order</label>
        <select name="order_type" class="form-select" required>
          <option value="">— Select —</option>
          <?php foreach (['priest' => 'Priest', 'deacon' => 'Deacon', 'lay_reader' => 'Lay Reader'] as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ($clergyMember['order_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Ordination Date</label>
        <input type="date" name="ordination_date" class="form-control" value="<?= e($clergyMember['ordination_date'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
          <?php foreach (['active' => 'Active', 'retired' => 'Retired', 'deceased' => 'Deceased', 'transferred' => 'Transferred'] as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ($clergyMember['status'] ?? 'active') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Home Church</label>
        <select name="home_church_id" class="form-select">
          <option value="">— None —</option>
          <?php foreach ($churchOptions as $church): ?>
            <option value="<?= (int) $church['id'] ?>" <?= (int) ($clergyMember['home_church_id'] ?? 0) === (int) $church['id'] ? 'selected' : '' ?>><?= e($church['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Photo</label>
        <input type="file" name="photo" class="form-control" accept="image/*">
        <?php if (!empty($clergyMember['photo'])): ?>
          <img src="<?= e(upload_url($clergyMember['photo'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= e($clergyMember['phone'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($clergyMember['email'] ?? '') ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Short Bio</label>
        <textarea name="bio_short" class="form-control" rows="2" maxlength="500"><?= e($clergyMember['bio_short'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Full Bio</label>
        <textarea name="bio_full" class="form-control" rows="5"><?= e($clergyMember['bio_full'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Clergy</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
