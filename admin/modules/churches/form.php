<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('churches');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$church = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM churches WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $church = $stmt->fetch();
    if (!$church) {
        flash('error', 'Church not found.');
        redirect('/admin/modules/churches/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
    $address = trim($_POST['address'] ?? '');
    $townDistrict = trim($_POST['town_district'] ?? '');
    $foundingDate = trim($_POST['founding_date'] ?? '') ?: null;
    $patronSaint = trim($_POST['patron_saint'] ?? '');
    $currentPriestId = (int) ($_POST['current_priest_id'] ?? 0) ?: null;
    $shortHistory = trim($_POST['short_history'] ?? '');
    $latestUpdate = trim($_POST['latest_update'] ?? '');
    $serviceTimes = trim($_POST['service_times'] ?? '');
    $status = $_POST['status'] ?? 'parish';
    if (!array_key_exists($status, CHURCH_STATUS_LABELS)) {
        $status = 'parish';
    }
    $isProCathedra = $status === 'pro_cathedral' ? 1 : 0;
    $lat = trim($_POST['lat'] ?? '') ?: null;
    $lng = trim($_POST['lng'] ?? '') ?: null;

    if ($name === '') {
        $errors[] = 'Church name is required.';
    }

    // Ensure slug uniqueness against other rows
    $slugCheck = $pdo->prepare('SELECT id FROM churches WHERE slug = :slug AND id != :id');
    $slugCheck->execute(['slug' => $slug, 'id' => $id]);
    if ($slugCheck->fetch()) {
        $slug .= '-' . bin2hex(random_bytes(2));
    }

    if (empty($errors)) {
        $imagePath = handle_file_upload('image_cover', 'churches');
        $newGalleryFiles = [];
        if (!empty($_FILES['gallery']['name'][0])) {
            foreach ($_FILES['gallery']['name'] as $i => $name_) {
                if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name' => $_FILES['gallery']['name'][$i],
                        'type' => $_FILES['gallery']['type'][$i],
                        'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                        'error' => $_FILES['gallery']['error'][$i],
                        'size' => $_FILES['gallery']['size'][$i],
                    ];
                    $_FILES['__gallery_single'] = $singleFile;
                    $path = handle_file_upload('__gallery_single', 'churches');
                    if ($path) {
                        $newGalleryFiles[] = $path;
                    }
                }
            }
        }

        $existingGallery = [];
        if ($church && !empty($church['gallery_json'])) {
            $decoded = json_decode($church['gallery_json'], true);
            $existingGallery = is_array($decoded) ? $decoded : [];
        }
        $galleryJson = json_encode(array_merge($existingGallery, $newGalleryFiles));

        if ($isProCathedra) {
            $pdo->exec('UPDATE churches SET is_pro_cathedra = 0');
        }

        if ($church) {
            $sql = 'UPDATE churches SET name = :name, slug = :slug, address = :address, town_district = :town_district,
                    founding_date = :founding_date, patron_saint = :patron_saint, current_priest_id = :current_priest_id,
                    short_history = :short_history, latest_update = :latest_update, service_times = :service_times,
                    status = :status, is_pro_cathedra = :is_pro_cathedra, lat = :lat, lng = :lng, gallery_json = :gallery_json';
            if ($imagePath) {
                $sql .= ', image_cover = :image_cover';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO churches (name, slug, address, town_district, founding_date, patron_saint,
                    current_priest_id, short_history, latest_update, service_times, status, is_pro_cathedra, lat, lng,
                    gallery_json' . ($imagePath ? ', image_cover' : '') . ')
                    VALUES (:name, :slug, :address, :town_district, :founding_date, :patron_saint,
                    :current_priest_id, :short_history, :latest_update, :service_times, :status, :is_pro_cathedra, :lat, :lng,
                    :gallery_json' . ($imagePath ? ', :image_cover' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'name' => $name, 'slug' => $slug, 'address' => $address, 'town_district' => $townDistrict,
            'founding_date' => $foundingDate, 'patron_saint' => $patronSaint, 'current_priest_id' => $currentPriestId,
            'short_history' => $shortHistory, 'latest_update' => $latestUpdate, 'service_times' => $serviceTimes,
            'status' => $status, 'is_pro_cathedra' => $isProCathedra, 'lat' => $lat, 'lng' => $lng, 'gallery_json' => $galleryJson,
        ];
        if ($church) {
            $bind['id'] = $id;
        }
        if ($imagePath) {
            $bind['image_cover'] = $imagePath;
        }
        $stmt->execute($bind);

        $newId = $church ? $id : (int) $pdo->lastInsertId();
        log_activity($church ? 'update' : 'create', 'church', $newId);
        flash('success', 'Church saved successfully.');
        redirect('/admin/modules/churches/index.php');
    }
}

$clergyOptions = $pdo->query(
    "SELECT id, full_name FROM clergy WHERE order_type IN ('priest','deacon') AND status = 'active' ORDER BY full_name"
)->fetchAll();

$pageTitle = $church ? 'Edit Church' : 'Add Church';
$activeModule = 'churches';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($church): ?><input type="hidden" name="id" value="<?= (int) $church['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Church Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($church['name'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Slug (optional)</label>
        <input type="text" name="slug" class="form-control" value="<?= e($church['slug'] ?? '') ?>" placeholder="auto-generated">
      </div>

      <div class="col-md-6">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="<?= e($church['address'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Town / District</label>
        <input type="text" name="town_district" class="form-control" value="<?= e($church['town_district'] ?? '') ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Founding Date</label>
        <input type="date" name="founding_date" class="form-control" value="<?= e($church['founding_date'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Patron Saint</label>
        <input type="text" name="patron_saint" class="form-control" value="<?= e($church['patron_saint'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Current Priest</label>
        <select name="current_priest_id" class="form-select">
          <option value="">— None —</option>
          <?php foreach ($clergyOptions as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) ($church['current_priest_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Latitude</label>
        <input type="text" name="lat" class="form-control" value="<?= e((string) ($church['lat'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Longitude</label>
        <input type="text" name="lng" class="form-control" value="<?= e((string) ($church['lng'] ?? '')) ?>">
      </div>

      <div class="col-12">
        <label class="form-label">Short History</label>
        <textarea name="short_history" class="form-control" rows="3"><?= e($church['short_history'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Latest Update</label>
        <textarea name="latest_update" class="form-control" rows="3"><?= e($church['latest_update'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Service Times</label>
        <input type="text" name="service_times" class="form-control" value="<?= e($church['service_times'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <?php foreach (CHURCH_STATUS_LABELS as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ($church['status'] ?? 'parish') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Choosing "Pro-Cathedral" designates this as the archdeaconry's Pro-Cathedral (unchecks any other church).</div>
      </div>

      <div class="col-md-6">
        <label class="form-label">Cover Image</label>
        <input type="file" name="image_cover" class="form-control" accept="image/*">
        <?php if (!empty($church['image_cover'])): ?>
          <img src="<?= e(upload_url($church['image_cover'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">Add Gallery Photos</label>
        <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Church</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
