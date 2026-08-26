<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('organizations');
$pdo = db();

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = 'organizations.name LIKE :q';
    $params['q'] = '%' . $q . '%';
}
if ($category !== '') {
    $where[] = 'organizations.category = :category';
    $params['category'] = $category;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) (function () use ($pdo, $whereSql, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM organizations $whereSql");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT organizations.*, churches.name AS church_name
     FROM organizations LEFT JOIN churches ON churches.id = organizations.church_id
     $whereSql ORDER BY organizations.name LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$organizations = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT DISTINCT category FROM organizations WHERE category IS NOT NULL AND category != '' ORDER BY category"
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Organizations';
$activeModule = 'organizations';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex gap-2 flex-wrap">
    <input type="text" name="q" class="form-control" placeholder="Search organizations..." value="<?= e($q) ?>">
    <select name="category" class="form-select">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit">Search</button>
  </form>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Organization</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Name</th><th>Category</th><th>Church</th><th>Founded</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($organizations)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No organizations found.</td></tr>
        <?php else: foreach ($organizations as $org): ?>
          <tr>
            <td><?= e($org['name']) ?></td>
            <td><?= e($org['category'] ?? '') ?></td>
            <td><?= $org['church_id'] ? e($org['church_name'] ?? '') : '<span class="badge bg-secondary">Archdeaconry-wide</span>' ?></td>
            <td><?= e(format_date($org['founding_date'])) ?></td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $org['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this organization? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $org['id'] ?>">
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
