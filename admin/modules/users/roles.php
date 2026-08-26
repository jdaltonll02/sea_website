<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('users');
$pdo = db();

$roles = $pdo->query(
    'SELECT roles.*, (SELECT COUNT(*) FROM users WHERE users.role_id = roles.id) AS user_count
     FROM roles ORDER BY roles.name'
)->fetchAll();

$pageTitle = 'Roles & Permissions';
$activeModule = 'users';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Users</a>
  <a href="roles-form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Role</a>
</div>

<p class="text-muted small">
  Each role is a named set of module permissions. SuperAdmin always has unconditional access to every module,
  regardless of what's configured here. Granting the <strong>Users &amp; Roles</strong> module lets a role manage
  every account and role in the system — including assigning SuperAdmin to anyone — so grant it carefully.
</p>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Role</th><th>Permissions</th><th>Users</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($roles)): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">No roles found.</td></tr>
        <?php else: foreach ($roles as $role): ?>
          <?php
            $isSuperAdmin = $role['name'] === 'SuperAdmin';
            $granted = [];
            if (!$isSuperAdmin) {
                $decoded = $role['permissions_json'] ? json_decode($role['permissions_json'], true) : [];
                $granted = is_array($decoded) ? array_keys(array_filter($decoded)) : [];
            }
          ?>
          <tr>
            <td class="fw-semibold"><?= e($role['name']) ?></td>
            <td>
              <?php if ($isSuperAdmin): ?>
                <span class="badge bg-danger">All modules</span>
              <?php elseif (empty($granted)): ?>
                <span class="text-muted">No modules granted</span>
              <?php else: ?>
                <?php foreach ($granted as $key): ?>
                  <span class="badge bg-secondary me-1 mb-1"><?= e(AVAILABLE_MODULES[$key] ?? $key) ?></span>
                <?php endforeach; ?>
              <?php endif; ?>
            </td>
            <td><?= (int) $role['user_count'] ?></td>
            <td class="text-end">
              <a href="roles-form.php?id=<?= (int) $role['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <?php if (!$isSuperAdmin): ?>
                <form action="roles-delete.php" method="post" class="d-inline" data-confirm="Delete this role? This only works if no users are assigned to it.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $role['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
