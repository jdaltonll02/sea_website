<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('users');
$pdo = db();

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE users.name LIKE :q OR users.email LIKE :q';
    $params['q'] = '%' . $q . '%';
}

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT users.*, roles.name AS role_name
     FROM users JOIN roles ON roles.id = users.role_id
     $where ORDER BY users.name LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roles = $pdo->query('SELECT * FROM roles ORDER BY name')->fetchAll();

$currentUserId = (int) (current_user()['id'] ?? 0);

$pageTitle = 'Users & Roles';
$activeModule = 'users';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search users..." value="<?= e($q) ?>">
    <button class="btn btn-outline-secondary" type="submit">Search</button>
  </form>
  <div class="d-flex gap-2">
    <a href="roles.php" class="btn btn-outline-secondary"><i class="bi bi-shield-lock me-1"></i>Manage Roles</a>
    <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add User</a>
  </div>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
        <?php else: foreach ($users as $user): ?>
          <tr>
            <td><?= e($user['name']) ?></td>
            <td><?= e($user['email']) ?></td>
            <td><?= e($user['role_name']) ?></td>
            <td>
              <?php if ($user['is_active']): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactive</span>
              <?php endif; ?>
            </td>
            <td><?= $user['last_login'] ? e(format_date($user['last_login'], 'M j, Y g:i A')) : '—' ?></td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $user['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <?php if ((int) $user['id'] === $currentUserId): ?>
                <button type="button" class="btn btn-sm btn-outline-danger" disabled title="You cannot delete your own account.">Delete</button>
              <?php else: ?>
                <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this user account? This cannot be undone.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
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

<h5 class="mt-4 mb-3">Roles</h5>
<p class="text-muted small">
  <?= count($roles) ?> role(s) configured — <a href="roles.php">manage roles and their module permissions</a>.
</p>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
