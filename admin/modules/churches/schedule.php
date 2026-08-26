<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('churches');
$pdo = db();

$churchId = (int) ($_GET['church_id'] ?? $_POST['church_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM churches WHERE id = :id');
$stmt->execute(['id' => $churchId]);
$church = $stmt->fetch();

if (!$church) {
    flash('error', 'Church not found.');
    redirect('/admin/modules/churches/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $activity = trim($_POST['activity'] ?? '');
    $dayLabel = trim($_POST['day_label'] ?? '');
    $timeLabel = trim($_POST['time_label'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    if ($activity === '' || $dayLabel === '' || $timeLabel === '') {
        $errors[] = 'Activity, day, and time are all required.';
    }

    if (empty($errors)) {
        $ins = $pdo->prepare(
            'INSERT INTO service_schedules (church_id, activity, day_label, time_label, sort_order)
             VALUES (:church_id, :activity, :day_label, :time_label, :sort_order)'
        );
        $ins->execute([
            'church_id' => $churchId,
            'activity' => $activity,
            'day_label' => $dayLabel,
            'time_label' => $timeLabel,
            'sort_order' => $sortOrder,
        ]);
        log_activity('create', 'service_schedule', (int) $pdo->lastInsertId());
        flash('success', 'Schedule entry added.');
        redirect('/admin/modules/churches/schedule.php?church_id=' . $churchId);
    }
}

$scheduleStmt = $pdo->prepare('SELECT * FROM service_schedules WHERE church_id = :church_id ORDER BY sort_order ASC, id ASC');
$scheduleStmt->execute(['church_id' => $churchId]);
$scheduleRows = $scheduleStmt->fetchAll();

$pageTitle = 'Schedule — ' . $church['name'];
$activeModule = 'churches';
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Schedule — <?= e($church['name']) ?></h5>
  <a href="index.php" class="btn btn-outline-secondary">Back to Churches</a>
</div>

<div class="card admin-stat-card mb-4">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Activity</th><th>Day</th><th>Time</th><th>Order</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($scheduleRows)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No schedule entries yet.</td></tr>
        <?php else: foreach ($scheduleRows as $row): ?>
          <tr>
            <td><?= e($row['activity']) ?></td>
            <td><?= e($row['day_label']) ?></td>
            <td><?= e($row['time_label']) ?></td>
            <td><?= (int) $row['sort_order'] ?></td>
            <td class="text-end">
              <form action="schedule-delete.php" method="post" class="d-inline" data-confirm="Delete this schedule entry?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <input type="hidden" name="church_id" value="<?= (int) $churchId ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card admin-stat-card">
  <div class="card-body">
    <h6 class="mb-3">Add Schedule Entry</h6>
    <form method="post" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="church_id" value="<?= (int) $churchId ?>">
      <div class="col-md-4">
        <label class="form-label">Activity</label>
        <input type="text" name="activity" class="form-control" placeholder="e.g. Bible Study" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Day</label>
        <input type="text" name="day_label" class="form-control" placeholder="e.g. Sunday, Daily" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Time</label>
        <input type="text" name="time_label" class="form-control" placeholder="e.g. 9:00 AM" required>
      </div>
      <div class="col-md-1">
        <label class="form-label">Order</label>
        <input type="number" name="sort_order" class="form-control" value="0">
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn saa-btn-accent w-100">Add</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
