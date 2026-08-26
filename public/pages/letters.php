<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = db();

$visibilities = ['public'];
if (is_logged_in()) {
    $visibilities[] = 'clergy_only';
    $visibilities[] = 'staff_only';
}
$placeholders = implode(',', array_fill(0, count($visibilities), '?'));

$category = trim($_GET['category'] ?? '');
$sql = "SELECT * FROM administrative_letters WHERE visibility IN ($placeholders)";
$params = $visibilities;
if ($category !== '') {
    $sql .= ' AND category = ?';
    $params[] = $category;
}
$sql .= ' ORDER BY published_at DESC, created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$letters = $stmt->fetchAll();

$categoryLabels = [
    'pastoral_letter' => 'Pastoral Letter',
    'circular' => 'Circular',
    'policy' => 'Policy',
    'minutes' => 'Minutes',
];

$pageTitle = 'Letters & Documents';
$currentPage = 'letters';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Administrative Documents</p>
      <h1>Letters &amp; Documents</h1>
      <?php if (!is_logged_in()): ?>
        <p class="text-muted">Some documents are restricted to clergy and staff. <a href="<?= e(base_url('admin/login.php')) ?>">Log in</a> to view them.</p>
      <?php endif; ?>
    </div>

    <form method="get" class="row g-2 justify-content-center mb-5">
      <div class="col-sm-4">
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach ($categoryLabels as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $category === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn saa-btn-accent">Filter</button>
      </div>
    </form>

    <?php if (empty($letters)): ?>
      <div class="saa-empty-state"><p>No documents published yet.</p></div>
    <?php else: ?>
      <div class="list-group">
        <?php foreach ($letters as $letter): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center py-3">
            <div>
              <span class="badge mb-1" style="background-color: var(--saa-blue);"><?= e($categoryLabels[$letter['category']] ?? $letter['category']) ?></span>
              <h6 class="mb-1"><?= e($letter['title']) ?></h6>
              <?php if ($letter['description']): ?><p class="text-muted small mb-1"><?= e($letter['description']) ?></p><?php endif; ?>
              <p class="text-muted small mb-0">
                <?php if ($letter['issued_by']): ?>Issued by <?= e($letter['issued_by']) ?> &middot; <?php endif; ?>
                <?= e(format_date($letter['published_at'] ?: $letter['created_at'])) ?>
              </p>
            </div>
            <a href="<?= e(base_url('pages/letter-download.php?id=' . $letter['id'])) ?>" class="btn btn-outline-primary">Download</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
