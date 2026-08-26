<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('activity-log');
$pdo = db();

$userId = trim($_GET['user_id'] ?? '');
$action = trim($_GET['action'] ?? '');
$targetType = trim($_GET['target_type'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$conditions = [];
$params = [];
if ($userId !== '') {
    $conditions[] = 'activity_log.user_id = :user_id';
    $params['user_id'] = (int) $userId;
}
if ($action !== '') {
    $conditions[] = 'activity_log.action LIKE :action';
    $params['action'] = '%' . $action . '%';
}
if ($targetType !== '') {
    $conditions[] = 'activity_log.target_type = :target_type';
    $params['target_type'] = $targetType;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT activity_log.*, users.name AS user_name
     FROM activity_log LEFT JOIN users ON users.id = activity_log.user_id
     $where ORDER BY activity_log.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$logEntries = $stmt->fetchAll();

$userOptions = $pdo->query('SELECT DISTINCT users.id, users.name FROM activity_log JOIN users ON users.id = activity_log.user_id ORDER BY users.name')->fetchAll();
$targetTypeOptions = $pdo->query('SELECT DISTINCT target_type FROM activity_log WHERE target_type IS NOT NULL ORDER BY target_type')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Activity Log';
$activeModule = 'activity-log';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex gap-2 flex-wrap">
    <select name="user_id" class="form-select">
      <option value="">All Users</option>
      <?php foreach ($userOptions as $userOption): ?>
        <option value="<?= (int) $userOption['id'] ?>" <?= $userId === (string) $userOption['id'] ? 'selected' : '' ?>><?= e($userOption['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="action" class="form-control" placeholder="Search action..." value="<?= e($action) ?>">
    <select name="target_type" class="form-select">
      <option value="">All Target Types</option>
      <?php foreach ($targetTypeOptions as $targetTypeOption): ?>
        <option value="<?= e($targetTypeOption) ?>" <?= $targetType === $targetTypeOption ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $targetTypeOption))) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit">Filter</button>
  </form>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Timestamp</th><th>User</th><th>Action</th><th>Target</th></tr>
      </thead>
      <tbody>
        <?php if (empty($logEntries)): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">No activity found.</td></tr>
        <?php else: foreach ($logEntries as $logEntry): ?>
          <tr>
            <td><?= e(format_date($logEntry['created_at'], 'M j, Y g:i A')) ?></td>
            <td><?= e($logEntry['user_name'] ?? 'System') ?></td>
            <td><?= e($logEntry['action']) ?></td>
            <td>
              <?php if ($logEntry['target_type']): ?>
                <?= e(ucwords(str_replace('_', ' ', $logEntry['target_type']))) ?><?= $logEntry['target_id'] ? ' #' . (int) $logEntry['target_id'] : '' ?>
              <?php else: ?>
                —
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

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
