<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('letters');
$pdo = db();

$categoryLabels = [
    'pastoral_letter' => 'Pastoral Letter',
    'circular' => 'Circular',
    'policy' => 'Policy',
    'minutes' => 'Minutes',
];
$visibilityLabels = [
    'public' => 'Public',
    'clergy_only' => 'Clergy Only',
    'staff_only' => 'Staff Only',
];

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$visibility = trim($_GET['visibility'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = 'title LIKE :q';
    $params['q'] = '%' . $q . '%';
}
if ($category !== '' && isset($categoryLabels[$category])) {
    $conditions[] = 'category = :category';
    $params['category'] = $category;
}
if ($visibility !== '' && isset($visibilityLabels[$visibility])) {
    $conditions[] = 'visibility = :visibility';
    $params['visibility'] = $visibility;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM administrative_letters $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT * FROM administrative_letters $where ORDER BY published_at DESC, created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$letters = $stmt->fetchAll();

$pageTitle = 'Letters & Documents';
$activeModule = 'letters';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex flex-wrap gap-2">
    <input type="text" name="q" class="form-control" placeholder="Search by title..." value="<?= e($q) ?>">
    <select name="category" class="form-select">
      <option value="">All Categories</option>
      <?php foreach ($categoryLabels as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $category === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="visibility" class="form-select">
      <option value="">All Visibility</option>
      <?php foreach ($visibilityLabels as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $visibility === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit">Filter</button>
  </form>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Letter</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Title</th><th>Category</th><th>Visibility</th><th>Published</th><th></th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($letters)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No letters found.</td></tr>
        <?php else: foreach ($letters as $letter): ?>
          <tr>
            <td><?= e($letter['title']) ?></td>
            <td><span class="badge bg-info text-dark"><?= e($categoryLabels[$letter['category']] ?? $letter['category']) ?></span></td>
            <td><span class="badge bg-secondary"><?= e($visibilityLabels[$letter['visibility']] ?? $letter['visibility']) ?></span></td>
            <td><?= e(format_date($letter['published_at'])) ?></td>
            <td><a href="<?= e(base_url('pages/letter-download.php?id=' . $letter['id'])) ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-download"></i></a></td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $letter['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this letter? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $letter['id'] ?>">
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
