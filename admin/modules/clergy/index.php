<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('clergy');
$pdo = db();

$q = trim($_GET['q'] ?? '');
$orderType = trim($_GET['order_type'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = 'clergy.full_name LIKE :q';
    $params['q'] = '%' . $q . '%';
}
if ($orderType !== '') {
    $conditions[] = 'clergy.order_type = :order_type';
    $params['order_type'] = $orderType;
}
if ($status !== '') {
    $conditions[] = 'clergy.status = :status';
    $params['status'] = $status;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clergy $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT clergy.*, churches.name AS home_church_name
     FROM clergy LEFT JOIN churches ON churches.id = clergy.home_church_id
     $where ORDER BY clergy.full_name LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$clergyList = $stmt->fetchAll();

$pageTitle = 'Clergy Registry';
$activeModule = 'clergy';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex gap-2 flex-wrap">
    <input type="text" name="q" class="form-control" placeholder="Search by name..." value="<?= e($q) ?>">
    <select name="order_type" class="form-select">
      <option value="">All Orders</option>
      <option value="priest" <?= $orderType === 'priest' ? 'selected' : '' ?>>Priest</option>
      <option value="deacon" <?= $orderType === 'deacon' ? 'selected' : '' ?>>Deacon</option>
      <option value="lay_reader" <?= $orderType === 'lay_reader' ? 'selected' : '' ?>>Lay Reader</option>
    </select>
    <select name="status" class="form-select">
      <option value="">All Statuses</option>
      <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="retired" <?= $status === 'retired' ? 'selected' : '' ?>>Retired</option>
      <option value="deceased" <?= $status === 'deceased' ? 'selected' : '' ?>>Deceased</option>
      <option value="transferred" <?= $status === 'transferred' ? 'selected' : '' ?>>Transferred</option>
    </select>
    <button class="btn btn-outline-secondary" type="submit">Filter</button>
  </form>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Clergy</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Name</th><th>Order</th><th>Home Church</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($clergyList)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No clergy found.</td></tr>
        <?php else: foreach ($clergyList as $clergyMember): ?>
          <tr>
            <td><?= e(trim(($clergyMember['title'] ?? '') . ' ' . $clergyMember['full_name'])) ?></td>
            <td><?= e(ucwords(str_replace('_', ' ', $clergyMember['order_type']))) ?></td>
            <td><?= e($clergyMember['home_church_name'] ?? '—') ?></td>
            <td><span class="badge bg-secondary"><?= e(ucfirst($clergyMember['status'])) ?></span></td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $clergyMember['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this clergy record? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $clergyMember['id'] ?>">
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
