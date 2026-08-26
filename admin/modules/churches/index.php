<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('churches');
$pdo = db();

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE name LIKE :q OR town_district LIKE :q';
    $params['q'] = '%' . $q . '%';
}

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM churches $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT churches.*, clergy.full_name AS priest_name
     FROM churches LEFT JOIN clergy ON clergy.id = churches.current_priest_id
     $where ORDER BY churches.name LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$churches = $stmt->fetchAll();

$pageTitle = 'Churches';
$activeModule = 'churches';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" class="d-flex gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search churches..." value="<?= e($q) ?>">
    <button class="btn btn-outline-secondary" type="submit">Search</button>
  </form>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Church</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Name</th><th>District</th><th>Current Priest</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($churches)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No churches found.</td></tr>
        <?php else: foreach ($churches as $church): ?>
          <tr>
            <td><?= e($church['name']) ?></td>
            <td><?= e($church['town_district'] ?? '') ?></td>
            <td><?= e($church['priest_name'] ?? '—') ?></td>
            <td><span class="badge <?= $church['status'] === 'pro_cathedral' ? 'bg-danger' : 'bg-secondary' ?>"><?= e(church_status_label($church['status'])) ?></span></td>
            <td class="text-end">
              <a href="schedule.php?church_id=<?= (int) $church['id'] ?>" class="btn btn-sm btn-outline-secondary">Schedule</a>
              <a href="form.php?id=<?= (int) $church['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this church? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $church['id'] ?>">
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
