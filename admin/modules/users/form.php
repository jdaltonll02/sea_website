<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('users');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$user = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    if (!$user) {
        flash('error', 'User not found.');
        redirect('/admin/modules/users/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roleId = (int) ($_POST['role_id'] ?? 0);
    $employeeId = (int) ($_POST['employee_id'] ?? 0) ?: null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (!$roleId) {
        $errors[] = 'A role is required.';
    }
    if (!$user) {
        if (strlen($password) < 8) {
            $errors[] = 'Password is required and must be at least 8 characters.';
        }
    } elseif ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (empty($errors)) {
        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $emailCheck->execute(['email' => $email, 'id' => $id]);
        if ($emailCheck->fetch()) {
            $errors[] = 'That email address is already in use by another user.';
        }
    }

    if (empty($errors)) {
        $avatarPath = handle_file_upload('avatar', 'users', ['jpg', 'jpeg', 'png', 'webp']);
        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_BCRYPT) : null;

        if ($user) {
            $sql = 'UPDATE users SET name = :name, email = :email, role_id = :role_id,
                    employee_id = :employee_id, is_active = :is_active';
            if ($avatarPath) {
                $sql .= ', avatar = :avatar';
            }
            if ($passwordHash) {
                $sql .= ', password_hash = :password_hash';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO users (name, email, role_id, employee_id, is_active, password_hash'
                    . ($avatarPath ? ', avatar' : '') . ')
                    VALUES (:name, :email, :role_id, :employee_id, :is_active, :password_hash'
                    . ($avatarPath ? ', :avatar' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'name' => $name, 'email' => $email, 'role_id' => $roleId,
            'employee_id' => $employeeId, 'is_active' => $isActive,
        ];
        if ($user) {
            $bind['id'] = $id;
        }
        if ($avatarPath) {
            $bind['avatar'] = $avatarPath;
        }
        if ($passwordHash) {
            $bind['password_hash'] = $passwordHash;
        } elseif (!$user) {
            $bind['password_hash'] = null;
        }
        $stmt->execute($bind);

        $newId = $user ? $id : (int) $pdo->lastInsertId();
        log_activity($user ? 'update' : 'create', 'user', $newId);
        flash('success', 'User saved successfully.');
        redirect('/admin/modules/users/index.php');
    }
}

$roleOptions = $pdo->query('SELECT id, name FROM roles ORDER BY name')->fetchAll();
$employeeOptions = $pdo->query('SELECT id, full_name FROM employees ORDER BY full_name')->fetchAll();

$pageTitle = $user ? 'Edit User' : 'Add User';
$activeModule = 'users';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($user): ?><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Role</label>
        <select name="role_id" class="form-select" required>
          <option value="">— Select —</option>
          <?php foreach ($roleOptions as $role): ?>
            <option value="<?= (int) $role['id'] ?>" <?= (int) ($user['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Linked Employee</label>
        <select name="employee_id" class="form-select">
          <option value="">— None —</option>
          <?php foreach ($employeeOptions as $employee): ?>
            <option value="<?= (int) $employee['id'] ?>" <?= (int) ($user['employee_id'] ?? 0) === (int) $employee['id'] ? 'selected' : '' ?>><?= e($employee['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label"><?= $user ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
        <input type="password" name="password" class="form-control" autocomplete="new-password" <?= $user ? '' : 'required' ?>>
      </div>
      <div class="col-md-6">
        <label class="form-label">Avatar</label>
        <input type="file" name="avatar" class="form-control" accept="image/*">
        <?php if (!empty($user['avatar'])): ?>
          <img src="<?= e(upload_url($user['avatar'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>

      <div class="col-12">
        <div class="form-check">
          <input type="checkbox" name="is_active" id="isActive" class="form-check-input" value="1" <?= ($user === null || !empty($user['is_active'])) ? 'checked' : '' ?>>
          <label class="form-check-label" for="isActive">Active (can log in to the dashboard)</label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save User</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
