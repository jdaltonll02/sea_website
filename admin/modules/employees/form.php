<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('employees');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$employee = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM employees WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $employee = $stmt->fetch();
    if (!$employee) {
        flash('error', 'Employee not found.');
        redirect('/admin/modules/employees/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dateJoined = trim($_POST['date_joined'] ?? '') ?: null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if (empty($errors)) {
        $photoPath = handle_file_upload('photo', 'employees', ['jpg', 'jpeg', 'png', 'webp']);

        if ($employee) {
            $sql = 'UPDATE employees SET full_name = :full_name, position = :position, department = :department,
                    bio = :bio, email = :email, phone = :phone, date_joined = :date_joined, is_active = :is_active';
            if ($photoPath) {
                $sql .= ', photo = :photo';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO employees (full_name, position, department, bio, email, phone, date_joined, is_active'
                . ($photoPath ? ', photo' : '') . ')
                    VALUES (:full_name, :position, :department, :bio, :email, :phone, :date_joined, :is_active'
                . ($photoPath ? ', :photo' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'full_name' => $fullName, 'position' => $position, 'department' => $department,
            'bio' => $bio, 'email' => $email, 'phone' => $phone, 'date_joined' => $dateJoined,
            'is_active' => $isActive,
        ];
        if ($employee) {
            $bind['id'] = $id;
        }
        if ($photoPath) {
            $bind['photo'] = $photoPath;
        }
        $stmt->execute($bind);

        $newId = $employee ? $id : (int) $pdo->lastInsertId();
        log_activity($employee ? 'update' : 'create', 'employee', $newId);
        flash('success', 'Employee saved successfully.');
        redirect('/admin/modules/employees/index.php');
    }
}

$pageTitle = $employee ? 'Edit Employee' : 'Add Employee';
$activeModule = 'employees';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($employee): ?><input type="hidden" name="id" value="<?= (int) $employee['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= e($employee['full_name'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Position</label>
        <input type="text" name="position" class="form-control" value="<?= e($employee['position'] ?? '') ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Department</label>
        <input type="text" name="department" class="form-control" value="<?= e($employee['department'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Date Joined</label>
        <input type="date" name="date_joined" class="form-control" value="<?= e($employee['date_joined'] ?? '') ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($employee['email'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= e($employee['phone'] ?? '') ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Bio</label>
        <textarea name="bio" class="form-control" rows="4"><?= e($employee['bio'] ?? '') ?></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label">Photo</label>
        <input type="file" name="photo" class="form-control" accept="image/*">
        <?php if (!empty($employee['photo'])): ?>
          <img src="<?= e(upload_url($employee['photo'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <div class="form-check">
          <input type="checkbox" name="is_active" id="isActive" class="form-check-input" value="1" <?= !isset($employee) || !empty($employee['is_active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="isActive">Active</label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Employee</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
