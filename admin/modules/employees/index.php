<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('employees');
$pdo = db();

$q = trim($_GET['q'] ?? '');
$department = trim($_GET['department'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = 'full_name LIKE :q';
    $params['q'] = '%' . $q . '%';
}
if ($department !== '') {
    $where[] = 'department = :department';
    $params['department'] = $department;
}
if ($status !== '') {
    $where[] = 'is_active = :is_active';
    $params['is_active'] = $status === 'active' ? 1 : 0;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) (function () use ($pdo, $whereSql, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees $whereSql");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM employees $whereSql ORDER BY full_name LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$employees = $stmt->fetchAll();

$departments = $pdo->query(
    "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department"
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Employees';
$activeModule = 'employees';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex gap-2 flex-wrap">
    <input type="text" name="q" class="form-control" placeholder="Search employees..." value="<?= e($q) ?>">
    <select name="department" class="form-select">
      <option value="">All Departments</option>
      <?php foreach ($departments as $dept): ?>
        <option value="<?= e($dept) ?>" <?= $department === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select">
      <option value="">All Statuses</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button class="btn btn-outline-secondary" type="submit">Search</button>
  </form>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Employee</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Full Name</th><th>Position</th><th>Department</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($employees)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No employees found.</td></tr>
        <?php else: foreach ($employees as $employee): ?>
          <tr>
            <td><?= e($employee['full_name']) ?></td>
            <td><?= e($employee['position'] ?? '') ?></td>
            <td><?= e($employee['department'] ?? '') ?></td>
            <td>
              <?php if ($employee['is_active']): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $employee['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this employee record? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $employee['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
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

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
