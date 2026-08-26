<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$category = trim($_GET['category'] ?? '');

$sql = 'SELECT organizations.*, churches.name AS church_name FROM organizations
        LEFT JOIN churches ON churches.id = organizations.church_id WHERE 1=1';
$params = [];
if ($category !== '') {
    $sql .= ' AND organizations.category = :category';
    $params['category'] = $category;
}
$sql .= ' ORDER BY organizations.name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$organizations = $stmt->fetchAll();

$categories = $pdo->query(
    'SELECT DISTINCT category FROM organizations WHERE category IS NOT NULL ORDER BY category'
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Organizations';
$currentPage = 'organizations';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Serving Together</p>
      <h1>Organizations of the Archdeaconry</h1>
    </div>

    <form method="get" class="row g-2 justify-content-center mb-5">
      <div class="col-sm-4">
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn saa-btn-accent">Filter</button>
      </div>
    </form>

    <?php if (empty($organizations)): ?>
      <div class="saa-empty-state"><p>No organizations match your filter.</p></div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($organizations as $org): ?>
          <div class="col-md-6 col-lg-4">
            <a href="<?= e(base_url('pages/organization-single.php?slug=' . $org['slug'])) ?>" class="text-decoration-none text-reset">
              <div class="card saa-card h-100">
                <img src="<?= e(upload_url($org['logo'])) ?>" alt="<?= e($org['name']) ?>">
                <div class="card-body">
                  <?php if ($org['category']): ?>
                    <span class="badge mb-2" style="background-color: var(--saa-blue);"><?= e($org['category']) ?></span>
                  <?php endif; ?>
                  <h5><?= e($org['name']) ?></h5>
                  <p class="text-muted small"><?= e($org['church_name'] ?? 'Archdeaconry-wide') ?></p>
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
