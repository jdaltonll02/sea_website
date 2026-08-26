<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('users');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$role = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM roles WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $role = $stmt->fetch();
    if (!$role) {
        flash('error', 'Role not found.');
        redirect('/admin/modules/users/roles.php');
    }
}

$isSuperAdmin = $role && $role['name'] === 'SuperAdmin';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if ($isSuperAdmin) {
        flash('error', 'The SuperAdmin role cannot be edited — it always has unconditional access.');
        redirect('/admin/modules/users/roles.php');
    }

    $name = trim($_POST['name'] ?? '');
    $selectedModules = array_intersect($_POST['modules'] ?? [], array_keys(AVAILABLE_MODULES));

    if ($name === '') {
        $errors[] = 'Role name is required.';
    } elseif ($name === 'SuperAdmin') {
        $errors[] = '"SuperAdmin" is reserved and cannot be used for another role.';
    }

    if (empty($errors)) {
        $nameCheck = $pdo->prepare('SELECT id FROM roles WHERE name = :name AND id != :id');
        $nameCheck->execute(['name' => $name, 'id' => $id]);
        if ($nameCheck->fetch()) {
            $errors[] = 'A role with that name already exists.';
        }
    }

    if (empty($errors)) {
        $permissionsJson = json_encode(array_fill_keys(array_values($selectedModules), true));

        if ($role) {
            $stmt = $pdo->prepare('UPDATE roles SET name = :name, permissions_json = :permissions WHERE id = :id');
            $stmt->execute(['name' => $name, 'permissions' => $permissionsJson, 'id' => $id]);
            $roleId = $id;
        } else {
            $stmt = $pdo->prepare('INSERT INTO roles (name, permissions_json) VALUES (:name, :permissions)');
            $stmt->execute(['name' => $name, 'permissions' => $permissionsJson]);
            $roleId = (int) $pdo->lastInsertId();
        }

        log_activity($role ? 'update' : 'create', 'role', $roleId);
        flash('success', 'Role saved successfully.');
        redirect('/admin/modules/users/roles.php');
    }
}

$currentModules = [];
if ($role && !$isSuperAdmin) {
    $decoded = $role['permissions_json'] ? json_decode($role['permissions_json'], true) : [];
    $currentModules = is_array($decoded) ? array_keys(array_filter($decoded)) : [];
} elseif (!$role) {
    // reflect what was just submitted if validation failed, so checkboxes don't reset
    $currentModules = $_POST['modules'] ?? [];
}

$pageTitle = $role ? 'Edit Role' : 'Add Role';
$activeModule = 'users';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($isSuperAdmin): ?>
  <div class="alert alert-info">SuperAdmin has unconditional access to every module in the code itself — there's nothing to configure here.</div>
  <a href="roles.php" class="btn btn-outline-secondary">Back to Roles</a>
<?php else: ?>
  <form method="post" class="card admin-stat-card">
    <div class="card-body">
      <?= csrf_field() ?>
      <?php if ($role): ?><input type="hidden" name="id" value="<?= (int) $role['id'] ?>"><?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Role Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($role['name'] ?? '') ?>" required>
      </div>

      <label class="form-label">Modules Granted</label>
      <p class="text-muted small">Check every admin module this role should be able to access. Granting <strong>Users &amp; Roles</strong> lets this role manage every account and role, including assigning SuperAdmin — grant it carefully.</p>
      <div class="row g-2 mb-3">
        <?php foreach (AVAILABLE_MODULES as $key => $label): ?>
          <div class="col-md-4">
            <div class="form-check">
              <input type="checkbox" name="modules[]" id="mod_<?= e($key) ?>" class="form-check-input" value="<?= e($key) ?>" <?= in_array($key, $currentModules, true) ? 'checked' : '' ?>>
              <label class="form-check-label" for="mod_<?= e($key) ?>"><?= e($label) ?></label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
      <a href="roles.php" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn saa-btn-accent">Save Role</button>
    </div>
  </form>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
