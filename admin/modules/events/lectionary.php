<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('events');
$pdo = db();

$from = trim($_GET['from'] ?? '') ?: date('Y-m-d');
$to = trim($_GET['to'] ?? '') ?: date('Y-m-d', strtotime($from . ' +60 days'));

$stmt = $pdo->prepare('SELECT * FROM lectionary_days WHERE `date` BETWEEN :from AND :to ORDER BY `date` ASC');
$stmt->execute(['from' => $from, 'to' => $to]);
$days = $stmt->fetchAll();

$pageTitle = 'Lectionary Days';
$activeModule = 'events';
include __DIR__ . '/../../includes/admin-header.php';
?>

<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link" href="index.php">Events</a></li>
  <li class="nav-item"><a class="nav-link active" href="lectionary.php">Lectionary Days</a></li>
</ul>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex gap-2 align-items-end flex-wrap">
    <div>
      <label class="form-label mb-0 small">From</label>
      <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
    </div>
    <div>
      <label class="form-label mb-0 small">To</label>
      <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
    </div>
    <button class="btn btn-outline-secondary" type="submit">Filter</button>
  </form>
  <a href="lectionary-form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Lectionary Day</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Date</th><th>Season</th><th>Color</th><th>Saint of the Day</th><th>Readings</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($days)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No lectionary days found in this range.</td></tr>
        <?php else: foreach ($days as $day): ?>
          <?php $readings = json_decode($day['readings_json'] ?? '[]', true) ?: []; ?>
          <tr>
            <td><?= e(format_date($day['date'], 'M j, Y')) ?></td>
            <td><?= e($day['season'] ?? '') ?></td>
            <td>
              <?php if (!empty($day['color'])): ?>
                <span class="liturgical-color-swatch d-inline-block me-1" style="width:12px;height:12px;border-radius:50%;background-color: <?= e($day['color']) ?>;"></span>
              <?php endif; ?>
              <?= e($day['color'] ?? '') ?>
            </td>
            <td><?= e($day['saint_of_day'] ?? '') ?></td>
            <td class="small text-muted"><?= e(implode('; ', $readings)) ?></td>
            <td class="text-end">
              <a href="lectionary-form.php?id=<?= (int) $day['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="lectionary-delete.php" method="post" class="d-inline" data-confirm="Delete this lectionary day? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $day['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
