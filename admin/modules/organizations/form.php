<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('organizations');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$organization = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM organizations WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $organization = $stmt->fetch();
    if (!$organization) {
        flash('error', 'Organization not found.');
        redirect('/admin/modules/organizations/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
    $category = trim($_POST['category'] ?? '');
    $foundingDate = trim($_POST['founding_date'] ?? '') ?: null;
    $churchId = (int) ($_POST['church_id'] ?? 0) ?: null;
    $shortHistory = trim($_POST['short_history'] ?? '');
    $latestUpdate = trim($_POST['latest_update'] ?? '');
    $missionStatement = trim($_POST['mission_statement'] ?? '');
    $meetingSchedule = trim($_POST['meeting_schedule'] ?? '');

    $leadershipJson = null;
    $leadershipRaw = trim($_POST['leadership_json'] ?? '');
    if ($leadershipRaw !== '') {
        $decoded = json_decode($leadershipRaw, true);
        if (is_array($decoded)) {
            $clean = [];
            foreach ($decoded as $row) {
                $role = trim($row['role'] ?? '');
                $person = trim($row['name'] ?? '');
                if ($role !== '' || $person !== '') {
                    $clean[] = ['role' => $role, 'name' => $person];
                }
            }
            $leadershipJson = json_encode($clean);
        }
    }

    if ($name === '') {
        $errors[] = 'Organization name is required.';
    }

    $slugCheck = $pdo->prepare('SELECT id FROM organizations WHERE slug = :slug AND id != :id');
    $slugCheck->execute(['slug' => $slug, 'id' => $id]);
    if ($slugCheck->fetch()) {
        $slug .= '-' . bin2hex(random_bytes(2));
    }

    if (empty($errors)) {
        $logoPath = handle_file_upload('logo', 'organizations', ['jpg', 'jpeg', 'png', 'webp']);

        if ($organization) {
            $sql = 'UPDATE organizations SET name = :name, slug = :slug, category = :category,
                    founding_date = :founding_date, church_id = :church_id, leadership_json = :leadership_json,
                    short_history = :short_history, latest_update = :latest_update,
                    mission_statement = :mission_statement, meeting_schedule = :meeting_schedule';
            if ($logoPath) {
                $sql .= ', logo = :logo';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO organizations (name, slug, category, founding_date, church_id, leadership_json,
                    short_history, latest_update, mission_statement, meeting_schedule' . ($logoPath ? ', logo' : '') . ')
                    VALUES (:name, :slug, :category, :founding_date, :church_id, :leadership_json,
                    :short_history, :latest_update, :mission_statement, :meeting_schedule' . ($logoPath ? ', :logo' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'name' => $name, 'slug' => $slug, 'category' => $category ?: null,
            'founding_date' => $foundingDate, 'church_id' => $churchId, 'leadership_json' => $leadershipJson,
            'short_history' => $shortHistory, 'latest_update' => $latestUpdate,
            'mission_statement' => $missionStatement, 'meeting_schedule' => $meetingSchedule,
        ];
        if ($organization) {
            $bind['id'] = $id;
        }
        if ($logoPath) {
            $bind['logo'] = $logoPath;
        }
        $stmt->execute($bind);

        $newId = $organization ? $id : (int) $pdo->lastInsertId();
        log_activity($organization ? 'update' : 'create', 'organization', $newId);
        flash('success', 'Organization saved successfully.');
        redirect('/admin/modules/organizations/index.php');
    }
}

$churchOptions = $pdo->query('SELECT id, name FROM churches ORDER BY name')->fetchAll();

$leadershipRows = [];
if ($organization && !empty($organization['leadership_json'])) {
    $decoded = json_decode($organization['leadership_json'], true);
    $leadershipRows = is_array($decoded) ? $decoded : [];
}

$pageTitle = $organization ? 'Edit Organization' : 'Add Organization';
$activeModule = 'organizations';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card" id="organizationForm">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($organization): ?><input type="hidden" name="id" value="<?= (int) $organization['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Organization Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($organization['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Slug (optional)</label>
        <input type="text" name="slug" class="form-control" value="<?= e($organization['slug'] ?? '') ?>" placeholder="auto-generated">
      </div>

      <div class="col-md-4">
        <label class="form-label">Category</label>
        <input type="text" name="category" class="form-control" value="<?= e($organization['category'] ?? '') ?>" placeholder="e.g. Youth, Women, Men">
      </div>
      <div class="col-md-4">
        <label class="form-label">Founding Date</label>
        <input type="date" name="founding_date" class="form-control" value="<?= e($organization['founding_date'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Associated Church</label>
        <select name="church_id" class="form-select">
          <option value="">— Archdeaconry-wide (no specific church) —</option>
          <?php foreach ($churchOptions as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) ($organization['church_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Logo</label>
        <input type="file" name="logo" class="form-control" accept="image/*">
        <?php if (!empty($organization['logo'])): ?>
          <img src="<?= e(upload_url($organization['logo'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">Meeting Schedule</label>
        <input type="text" name="meeting_schedule" class="form-control" value="<?= e($organization['meeting_schedule'] ?? '') ?>" placeholder="e.g. Every 1st Saturday, 10am">
      </div>

      <div class="col-12">
        <label class="form-label">Mission Statement</label>
        <textarea name="mission_statement" class="form-control" rows="2"><?= e($organization['mission_statement'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Short History</label>
        <textarea name="short_history" class="form-control" rows="3"><?= e($organization['short_history'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Latest Update</label>
        <textarea name="latest_update" class="form-control" rows="3"><?= e($organization['latest_update'] ?? '') ?></textarea>
      </div>

      <div class="col-12">
        <label class="form-label d-block">Leadership</label>
        <div id="leadershipRows"></div>
        <button type="button" id="addLeadershipRow" class="btn btn-outline-secondary btn-sm mt-1">
          <i class="bi bi-plus-lg me-1"></i>Add Row
        </button>
        <input type="hidden" name="leadership_json" id="leadershipJson">
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Organization</button>
  </div>
</form>

<script>
(function () {
  var container = document.getElementById('leadershipRows');
  var initialRows = <?= json_encode(array_map(fn($r) => ['role' => $r['role'] ?? '', 'name' => $r['name'] ?? ''], $leadershipRows), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

  function addRow(role, name) {
    var row = document.createElement('div');
    row.className = 'row g-2 mb-2 leadership-row';
    row.innerHTML =
      '<div class="col-md-5"><input type="text" class="form-control" data-field="role" placeholder="Role (e.g. President)"></div>' +
      '<div class="col-md-5"><input type="text" class="form-control" data-field="name" placeholder="Name"></div>' +
      '<div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-remove-row w-100"><i class="bi bi-trash"></i></button></div>';
    row.querySelector('[data-field="role"]').value = role || '';
    row.querySelector('[data-field="name"]').value = name || '';
    row.querySelector('.btn-remove-row').addEventListener('click', function () {
      row.remove();
    });
    container.appendChild(row);
  }

  document.getElementById('addLeadershipRow').addEventListener('click', function () {
    addRow('', '');
  });

  if (initialRows.length) {
    initialRows.forEach(function (r) { addRow(r.role, r.name); });
  } else {
    addRow('', '');
  }

  document.getElementById('organizationForm').addEventListener('submit', function () {
    var rows = [];
    container.querySelectorAll('.leadership-row').forEach(function (row) {
      var role = row.querySelector('[data-field="role"]').value.trim();
      var name = row.querySelector('[data-field="name"]').value.trim();
      if (role !== '' || name !== '') {
        rows.push({ role: role, name: name });
      }
    });
    document.getElementById('leadershipJson').value = JSON.stringify(rows);
  });
})();
</script>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
