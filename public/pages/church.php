<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();

$district = trim($_GET['district'] ?? '');
$q = trim($_GET['q'] ?? '');

$sql = 'SELECT * FROM churches WHERE 1=1';
$params = [];
if ($district !== '') {
    $sql .= ' AND town_district = :district';
    $params['district'] = $district;
}
if ($q !== '') {
    $sql .= ' AND name LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY is_pro_cathedra DESC, name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$churches = $stmt->fetchAll();

$districts = $pdo->query(
    'SELECT DISTINCT town_district FROM churches WHERE town_district IS NOT NULL ORDER BY town_district'
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Churches';
$currentPage = 'churches';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Our Parishes</p>
      <h1>Churches of the Southeastern Archdeaconry</h1>
    </div>

    <form method="get" class="row g-2 justify-content-center mb-5">
      <div class="col-sm-4">
        <input type="text" name="q" class="form-control" placeholder="Search by name" value="<?= e($q) ?>">
      </div>
      <div class="col-sm-3">
        <select name="district" class="form-select">
          <option value="">All Districts</option>
          <?php foreach ($districts as $d): ?>
            <option value="<?= e($d) ?>" <?= $district === $d ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn saa-btn-accent">Filter</button>
      </div>
    </form>

    <?php if (empty($churches)): ?>
      <div class="saa-empty-state"><p>No churches match your search.</p></div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($churches as $church): ?>
          <div class="col-md-6 col-lg-4">
            <a href="<?= e(base_url('pages/church-single.php?slug=' . $church['slug'])) ?>" class="text-decoration-none text-reset">
              <div class="card saa-card h-100">
                <img src="<?= e(upload_url($church['image_cover'])) ?>" alt="<?= e($church['name']) ?>">
                <div class="card-body">
                  <?php if ($church['status'] !== 'parish'): ?>
                    <span class="badge mb-2" style="background-color: var(--saa-red);"><?= e(church_status_label($church['status'])) ?></span>
                  <?php endif; ?>
                  <h5><?= e($church['name']) ?></h5>
                  <p class="text-muted small"><?= e($church['town_district'] ?? '') ?></p>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
