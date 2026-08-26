<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();

$blocks = [];
$stmt = $pdo->query("SELECT page_key, title, body FROM pages_content WHERE page_key IN ('about_vision','about_mission','about_history')");
foreach ($stmt->fetchAll() as $row) {
    $blocks[$row['page_key']] = $row;
}

$districts = $pdo->query(
    'SELECT DISTINCT town_district FROM churches WHERE town_district IS NOT NULL ORDER BY town_district'
)->fetchAll(PDO::FETCH_COLUMN);

$currentArchdeacon = $pdo->query(
    'SELECT * FROM archdeacons WHERE term_end IS NULL ORDER BY term_start DESC LIMIT 1'
)->fetch();

$pageTitle = 'About the Archdeaconry';
$currentPage = 'about';
include __DIR__ . '/../includes/header.php';
?>

<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">About Us</p>
      <h1>The Southeastern Archdeaconry</h1>
    </div>

    <div class="row g-4 mb-5">
      <div class="col-md-6">
        <div class="card saa-card h-100">
          <div class="card-body">
            <h3><?= e($blocks['about_vision']['title'] ?? 'Our Vision') ?></h3>
            <p><?= nl2br(e($blocks['about_vision']['body'] ?? '')) ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card saa-card h-100">
          <div class="card-body">
            <h3><?= e($blocks['about_mission']['title'] ?? 'Our Mission') ?></h3>
            <p><?= nl2br(e($blocks['about_mission']['body'] ?? '')) ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 align-items-center">
      <div class="col-lg-7">
        <h3><?= e($blocks['about_history']['title'] ?? 'Our History') ?></h3>
        <p><?= nl2br(e($blocks['about_history']['body'] ?? '')) ?></p>
        <?php if ($currentArchdeacon): ?>
          <p class="text-muted">
            Currently led by <strong><?= e($currentArchdeacon['name']) ?></strong>, Archdeacon since
            <?= e(format_date($currentArchdeacon['term_start'], 'Y')) ?>.
            <a href="<?= e(base_url('pages/archdeacons.php')) ?>">View the full list of Archdeacons &rarr;</a>
          </p>
        <?php endif; ?>
      </div>
      <div class="col-lg-5">
        <div class="saa-today-widget">
          <p class="eyebrow mb-2">Coverage Area</p>
          <h5 class="mb-3">Districts We Serve</h5>
          <ul class="list-unstyled mb-0">
            <?php foreach ($districts as $district): ?>
              <li class="mb-1">&bull; <?= e($district) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
