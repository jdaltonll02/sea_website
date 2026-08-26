<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('bishops');
$pdo = db();

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE name LIKE :q OR title LIKE :q';
    $params['q'] = '%' . $q . '%';
}

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM dioceses_bishops $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT * FROM dioceses_bishops $where
     ORDER BY type ASC, order_number ASC, id ASC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$bishops = $stmt->fetchAll();

$pageTitle = 'Bishops';
$activeModule = 'bishops';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search bishops..." value="<?= e($q) ?>">
    <button class="btn btn-outline-secondary" type="submit">Search</button>
  </form>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Bishop</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Name</th><th>Title</th><th>Type</th><th>Order</th><th>Current</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($bishops)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No bishop records found.</td></tr>
        <?php else: foreach ($bishops as $bishop): ?>
          <tr>
            <td><?= e($bishop['name']) ?></td>
            <td><?= e($bishop['title']) ?></td>
            <td>
              <span class="badge <?= $bishop['type'] === 'diocesan' ? 'bg-primary' : 'bg-secondary' ?>">
                <?= e(ucfirst($bishop['type'])) ?>
              </span>
            </td>
            <td><?= $bishop['order_number'] !== null ? (int) $bishop['order_number'] : '—' ?></td>
            <td><?= $bishop['is_current'] ? '<span class="badge bg-success">Current</span>' : '<span class="text-muted">—</span>' ?></td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $bishop['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this bishop record? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $bishop['id'] ?>">
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
