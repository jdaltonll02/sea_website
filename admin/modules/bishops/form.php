<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('bishops');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bishop = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM dioceses_bishops WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $bishop = $stmt->fetch();
    if (!$bishop) {
        flash('error', 'Bishop record not found.');
        redirect('/admin/modules/bishops/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $consecrationDate = trim($_POST['consecration_date'] ?? '') ?: null;
    $enthronementDate = trim($_POST['enthronement_date'] ?? '') ?: null;
    $isCurrent = isset($_POST['is_current']) ? 1 : 0;
    $orderNumber = trim($_POST['order_number'] ?? '') !== '' ? (int) $_POST['order_number'] : null;
    $bioShort = trim($_POST['bio_short'] ?? '');
    $bioFull = trim($_POST['bio_full'] ?? '');
    $motto = trim($_POST['motto'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $contactOffice = trim($_POST['contact_office'] ?? '');

    $ordinationHistoryRaw = $_POST['ordination_history_json'] ?? '[]';
    $decodedHistory = json_decode($ordinationHistoryRaw, true);
    $ordinationHistoryJson = json_encode(is_array($decodedHistory) ? $decodedHistory : []);

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!in_array($type, ['diocesan', 'suffragan'], true)) {
        $errors[] = 'A valid type must be selected.';
    }

    if (empty($errors)) {
        $photoPath = handle_file_upload('photo', 'clergy', ['jpg', 'jpeg', 'png', 'webp']);

        if ($isCurrent) {
            $resetStmt = $pdo->prepare('UPDATE dioceses_bishops SET is_current = 0 WHERE type = :type');
            $resetStmt->execute(['type' => $type]);
        }

        if ($bishop) {
            $sql = 'UPDATE dioceses_bishops SET name = :name, title = :title, type = :type,
                    consecration_date = :consecration_date, enthronement_date = :enthronement_date,
                    is_current = :is_current, order_number = :order_number, bio_short = :bio_short,
                    bio_full = :bio_full, motto = :motto, education = :education,
                    ordination_history_json = :ordination_history_json, contact_office = :contact_office';
            if ($photoPath) {
                $sql .= ', photo = :photo';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO dioceses_bishops (name, title, type, consecration_date, enthronement_date,
                    is_current, order_number, bio_short, bio_full, motto, education, ordination_history_json,
                    contact_office' . ($photoPath ? ', photo' : '') . ')
                    VALUES (:name, :title, :type, :consecration_date, :enthronement_date, :is_current,
                    :order_number, :bio_short, :bio_full, :motto, :education, :ordination_history_json,
                    :contact_office' . ($photoPath ? ', :photo' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'name' => $name, 'title' => $title, 'type' => $type,
            'consecration_date' => $consecrationDate, 'enthronement_date' => $enthronementDate,
            'is_current' => $isCurrent, 'order_number' => $orderNumber, 'bio_short' => $bioShort,
            'bio_full' => $bioFull, 'motto' => $motto, 'education' => $education,
            'ordination_history_json' => $ordinationHistoryJson, 'contact_office' => $contactOffice,
        ];
        if ($bishop) {
            $bind['id'] = $id;
        }
        if ($photoPath) {
            $bind['photo'] = $photoPath;
        }
        $stmt->execute($bind);

        $newId = $bishop ? $id : (int) $pdo->lastInsertId();
        log_activity($bishop ? 'update' : 'create', 'bishop', $newId);
        flash('success', 'Bishop record saved successfully.');
        redirect('/admin/modules/bishops/index.php');
    }
}

$ordinationHistory = [];
if (!empty($bishop['ordination_history_json'])) {
    $decoded = json_decode($bishop['ordination_history_json'], true);
    $ordinationHistory = is_array($decoded) ? $decoded : [];
}

$pageTitle = $bishop ? 'Edit Bishop' : 'Add Bishop';
$activeModule = 'bishops';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card" id="bishopForm">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($bishop): ?><input type="hidden" name="id" value="<?= (int) $bishop['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($bishop['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="<?= e($bishop['title'] ?? '') ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" required>
          <option value="">— Select —</option>
          <option value="diocesan" <?= ($bishop['type'] ?? '') === 'diocesan' ? 'selected' : '' ?>>Diocesan Bishop</option>
          <option value="suffragan" <?= ($bishop['type'] ?? '') === 'suffragan' ? 'selected' : '' ?>>Suffragan Bishop</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Order Number</label>
        <input type="number" name="order_number" class="form-control" value="<?= e((string) ($bishop['order_number'] ?? '')) ?>">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
          <input type="checkbox" name="is_current" id="isCurrent" class="form-check-input" value="1" <?= !empty($bishop['is_current']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="isCurrent">This is the current holder (unchecks any other of the same type)</label>
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label">Consecration Date</label>
        <input type="date" name="consecration_date" class="form-control" value="<?= e($bishop['consecration_date'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Enthronement Date</label>
        <input type="date" name="enthronement_date" class="form-control" value="<?= e($bishop['enthronement_date'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Photo</label>
        <input type="file" name="photo" class="form-control" accept="image/*">
        <?php if (!empty($bishop['photo'])): ?>
          <img src="<?= e(upload_url($bishop['photo'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>

      <div class="col-12">
        <label class="form-label">Short Bio</label>
        <textarea name="bio_short" class="form-control" rows="2" maxlength="500"><?= e($bishop['bio_short'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Full Bio</label>
        <textarea name="bio_full" class="form-control" rows="5"><?= e($bishop['bio_full'] ?? '') ?></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label">Motto</label>
        <input type="text" name="motto" class="form-control" value="<?= e($bishop['motto'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Contact Office</label>
        <input type="text" name="contact_office" class="form-control" value="<?= e($bishop['contact_office'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Education</label>
        <textarea name="education" class="form-control" rows="3"><?= e($bishop['education'] ?? '') ?></textarea>
      </div>

      <div class="col-12">
        <label class="form-label">Ordination History</label>
        <div id="ordinationRows"></div>
        <button type="button" id="addOrdinationRow" class="btn btn-sm btn-outline-secondary mt-2">
          <i class="bi bi-plus-lg me-1"></i>Add Row
        </button>
        <input type="hidden" name="ordination_history_json" id="ordinationHistoryJson">
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Bishop</button>
  </div>
</form>

<script>
(function () {
  var initialHistory = <?= json_encode(array_map(function ($item) {
      return ['event' => is_array($item) ? ($item['event'] ?? '') : '', 'date' => is_array($item) ? ($item['date'] ?? '') : ''];
  }, $ordinationHistory)) ?>;
  var container = document.getElementById('ordinationRows');
  var hiddenInput = document.getElementById('ordinationHistoryJson');

  function addRow(eventVal, dateVal) {
    var row = document.createElement('div');
    row.className = 'row g-2 mb-2 ordination-row';
    row.innerHTML =
      '<div class="col-md-6"><input type="text" class="form-control ordination-event" placeholder="Event (e.g. Ordained Deacon)"></div>' +
      '<div class="col-md-4"><input type="date" class="form-control ordination-date"></div>' +
      '<div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-ordination-row">Remove</button></div>';
    row.querySelector('.ordination-event').value = eventVal || '';
    row.querySelector('.ordination-date').value = dateVal || '';
    container.appendChild(row);
  }

  container.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-ordination-row')) {
      e.target.closest('.ordination-row').remove();
    }
  });

  document.getElementById('addOrdinationRow').addEventListener('click', function () {
    addRow('', '');
  });

  if (initialHistory.length) {
    initialHistory.forEach(function (item) {
      addRow(item.event, item.date);
    });
  } else {
    addRow('', '');
  }

  document.getElementById('bishopForm').addEventListener('submit', function () {
    var rows = container.querySelectorAll('.ordination-row');
    var data = [];
    rows.forEach(function (row) {
      var eventVal = row.querySelector('.ordination-event').value.trim();
      var dateVal = row.querySelector('.ordination-date').value;
      if (eventVal || dateVal) {
        data.push({ event: eventVal, date: dateVal });
      }
    });
    hiddenInput.value = JSON.stringify(data);
  });
})();
</script>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
