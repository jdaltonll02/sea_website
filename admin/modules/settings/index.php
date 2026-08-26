<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('settings');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $submitted = $_POST['settings'] ?? [];
    if (is_array($submitted)) {
        $stmt = $pdo->prepare('UPDATE settings SET setting_value = :value WHERE setting_key = :key');
        foreach ($submitted as $key => $value) {
            $stmt->execute(['value' => $value, 'key' => $key]);
        }
    }

    $newKey = trim($_POST['new_key'] ?? '');
    $newValue = trim($_POST['new_value'] ?? '');
    $newKeyError = null;
    if ($newKey !== '') {
        $check = $pdo->prepare('SELECT id FROM settings WHERE setting_key = :key');
        $check->execute(['key' => $newKey]);
        if ($check->fetch()) {
            $newKeyError = 'A setting with the key "' . $newKey . '" already exists, so it was not added.';
        } else {
            $insert = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)');
            $insert->execute(['key' => $newKey, 'value' => $newValue]);
        }
    }

    log_activity('update', 'settings', null);

    if ($newKeyError) {
        flash('error', 'Settings updated. ' . $newKeyError);
    } else {
        flash('success', 'Settings updated.');
    }
    redirect('/admin/modules/settings/index.php');
}

$settings = $pdo->query('SELECT * FROM settings ORDER BY setting_key')->fetchAll();

$longFormKeys = ['mission_statement', 'giving_bank_details', 'giving_mobile_money'];

$pageTitle = 'Settings';
$activeModule = 'settings';
include __DIR__ . '/../../includes/admin-header.php';
?>

<form method="post" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <p class="text-muted">Site-wide key/value settings used across the public site and admin dashboard.</p>

    <?php foreach ($settings as $row): ?>
      <?php
        $key = $row['setting_key'];
        $value = $row['setting_value'] ?? '';
        $useTextarea = in_array($key, $longFormKeys, true) || strlen($value) > 100;
      ?>
      <div class="mb-3">
        <label class="form-label"><code><?= e($key) ?></code></label>
        <?php if ($useTextarea): ?>
          <textarea name="settings[<?= e($key) ?>]" class="form-control" rows="3"><?= e($value) ?></textarea>
        <?php else: ?>
          <input type="text" name="settings[<?= e($key) ?>]" class="form-control" value="<?= e($value) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <hr class="my-4">

    <h6>Add New Setting</h6>
    <p class="text-muted small">Introduce a new setting key not covered above (e.g. a future homepage hero text key).</p>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">New Key</label>
        <input type="text" name="new_key" class="form-control" placeholder="e.g. homepage_hero_text">
      </div>
      <div class="col-md-8">
        <label class="form-label">New Value</label>
        <input type="text" name="new_value" class="form-control">
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-end">
    <button type="submit" class="btn saa-btn-accent">Save Settings</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
