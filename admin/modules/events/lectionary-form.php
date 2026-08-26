<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('events');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$day = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM lectionary_days WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $day = $stmt->fetch();
    if (!$day) {
        flash('error', 'Lectionary day not found.');
        redirect('/admin/modules/events/lectionary.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $date = trim($_POST['date'] ?? '');
    $season = trim($_POST['season'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $saintOfDay = trim($_POST['saint_of_day'] ?? '');
    $readings = array_values(array_filter(array_map('trim', $_POST['readings'] ?? []), fn($r) => $r !== ''));
    $readingsJson = json_encode($readings);

    if ($date === '') {
        $errors[] = 'Date is required.';
    }

    if (empty($errors)) {
        $dupCheck = $pdo->prepare('SELECT id FROM lectionary_days WHERE `date` = :date AND id != :id');
        $dupCheck->execute(['date' => $date, 'id' => $id]);
        if ($dupCheck->fetch()) {
            flash('error', 'A lectionary day already exists for that date.');
            redirect('/admin/modules/events/lectionary-form.php' . ($id ? '?id=' . $id : ''));
        }
    }

    if (empty($errors)) {
        if ($day) {
            $sql = 'UPDATE lectionary_days SET `date` = :date, season = :season, color = :color,
                    readings_json = :readings_json, saint_of_day = :saint_of_day WHERE id = :id';
        } else {
            $sql = 'INSERT INTO lectionary_days (`date`, season, color, readings_json, saint_of_day)
                    VALUES (:date, :season, :color, :readings_json, :saint_of_day)';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'date' => $date, 'season' => $season, 'color' => $color,
            'readings_json' => $readingsJson, 'saint_of_day' => $saintOfDay,
        ];
        if ($day) {
            $bind['id'] = $id;
        }
        $stmt->execute($bind);

        $newId = $day ? $id : (int) $pdo->lastInsertId();
        log_activity($day ? 'update' : 'create', 'lectionary_day', $newId);
        flash('success', 'Lectionary day saved successfully.');
        redirect('/admin/modules/events/lectionary.php');
    }
}

$existingReadings = [];
if ($day && !empty($day['readings_json'])) {
    $decoded = json_decode($day['readings_json'], true);
    $existingReadings = is_array($decoded) ? $decoded : [];
}
if (empty($existingReadings)) {
    $existingReadings = [''];
}

$pageTitle = $day ? 'Edit Lectionary Day' : 'Add Lectionary Day';
$activeModule = 'events';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link" href="index.php">Events</a></li>
  <li class="nav-item"><a class="nav-link active" href="lectionary.php">Lectionary Days</a></li>
</ul>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" id="lectionaryForm" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($day): ?><input type="hidden" name="id" value="<?= (int) $day['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Date</label>
        <input type="date" name="date" class="form-control" value="<?= e($day['date'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Season</label>
        <input type="text" name="season" class="form-control" value="<?= e($day['season'] ?? '') ?>" placeholder="e.g. Advent, Lent, Ordinary Time">
      </div>
      <div class="col-md-4">
        <label class="form-label">Color</label>
        <input type="text" name="color" class="form-control" value="<?= e($day['color'] ?? '') ?>" placeholder="e.g. purple or #6f2c91">
        <div class="form-text">Use a CSS-friendly color name or hex code — it is rendered as a swatch on the public site.</div>
      </div>

      <div class="col-12">
        <label class="form-label">Saint of the Day (optional)</label>
        <input type="text" name="saint_of_day" class="form-control" value="<?= e($day['saint_of_day'] ?? '') ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Readings</label>
        <div id="readingsList">
          <?php foreach ($existingReadings as $reading): ?>
            <div class="input-group mb-2 reading-row">
              <input type="text" name="readings[]" class="form-control" value="<?= e($reading) ?>" placeholder="e.g. Isaiah 9:2-7">
              <button type="button" class="btn btn-outline-danger remove-reading"><i class="bi bi-x-lg"></i></button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" id="addReadingBtn" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-plus-lg me-1"></i>Add Reading
        </button>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="lectionary.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Lectionary Day</button>
  </div>
</form>

<script>
document.getElementById('addReadingBtn').addEventListener('click', function () {
  var list = document.getElementById('readingsList');
  var row = document.createElement('div');
  row.className = 'input-group mb-2 reading-row';
  row.innerHTML = '<input type="text" name="readings[]" class="form-control" placeholder="e.g. Psalm 96">' +
    '<button type="button" class="btn btn-outline-danger remove-reading"><i class="bi bi-x-lg"></i></button>';
  list.appendChild(row);
});

document.getElementById('readingsList').addEventListener('click', function (e) {
  var btn = e.target.closest('.remove-reading');
  if (!btn) return;
  var rows = document.querySelectorAll('#readingsList .reading-row');
  if (rows.length > 1) {
    btn.closest('.reading-row').remove();
  } else {
    btn.closest('.reading-row').querySelector('input').value = '';
  }
});
</script>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
