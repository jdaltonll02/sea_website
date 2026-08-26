<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('archdeacons');
$pdo = db();

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE name LIKE :q';
    $params['q'] = '%' . $q . '%';
}

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM archdeacons $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT * FROM archdeacons $where ORDER BY term_start ASC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$archdeacons = $stmt->fetchAll();

$pageTitle = 'Archdeacons';
$activeModule = 'archdeacons';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search archdeacons..." value="<?= e($q) ?>">
    <button class="btn btn-outline-secondary" type="submit">Search</button>
  </form>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Archdeacon</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Photo</th><th>Name</th><th>Term Start</th><th>Term End</th><th>Order</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($archdeacons)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No archdeacons found.</td></tr>
        <?php else: foreach ($archdeacons as $archdeacon): ?>
          <tr>
            <td><img src="<?= e(upload_url($archdeacon['photo'])) ?>" class="rounded" style="width:44px;height:44px;object-fit:cover;"></td>
            <td><?= e($archdeacon['name']) ?></td>
            <td><?= e(format_date($archdeacon['term_start'])) ?></td>
            <td>
              <?php if ($archdeacon['term_end'] === null): ?>
                <span class="badge bg-success">Current</span>
              <?php else: ?>
                <?= e(format_date($archdeacon['term_end'])) ?>
              <?php endif; ?>
            </td>
            <td><?= e((string) ($archdeacon['order_number'] ?? '')) ?></td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $archdeacon['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this archdeacon? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $archdeacon['id'] ?>">
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
