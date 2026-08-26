<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$slug = trim($_GET['slug'] ?? '');

$stmt = $pdo->prepare(
    'SELECT organizations.*, churches.name AS church_name, churches.slug AS church_slug
     FROM organizations LEFT JOIN churches ON churches.id = organizations.church_id
     WHERE organizations.slug = :slug'
);
$stmt->execute(['slug' => $slug]);
$org = $stmt->fetch();

$leadership = [];
if ($org && !empty($org['leadership_json'])) {
    $decoded = json_decode($org['leadership_json'], true);
    if (is_array($decoded)) {
        $leadership = $decoded;
    }
}

$pageTitle = $org['name'] ?? 'Organization Not Found';
$currentPage = 'organizations';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <?php if (!$org): ?>
      <div class="saa-empty-state">
        <h3>Organization Not Found</h3>
        <a href="<?= e(base_url('pages/organizations.php')) ?>" class="btn saa-btn-accent">Back to Organizations</a>
      </div>
    <?php else: ?>
      <div class="row g-5">
        <div class="col-lg-4 text-center">
          <img src="<?= e(upload_url($org['logo'])) ?>" alt="<?= e($org['name']) ?>" class="img-fluid rounded shadow-sm">
        </div>
        <div class="col-lg-8">
          <?php if ($org['category']): ?><p class="eyebrow"><?= e($org['category']) ?></p><?php endif; ?>
          <h1><?= e($org['name']) ?></h1>
          <?php if ($org['church_name']): ?>
            <p class="text-muted">
              Based at <a href="<?= e(base_url('pages/church-single.php?slug=' . $org['church_slug'])) ?>"><?= e($org['church_name']) ?></a>
            </p>
          <?php else: ?>
            <p class="text-muted">Archdeaconry-wide organization</p>
          <?php endif; ?>

          <?php if ($org['mission_statement']): ?>
            <h5 class="mt-4">Mission</h5>
            <p><?= nl2br(e($org['mission_statement'])) ?></p>
          <?php endif; ?>

          <?php if ($org['short_history']): ?>
            <h5 class="mt-4">History</h5>
            <p><?= nl2br(e($org['short_history'])) ?></p>
          <?php endif; ?>

          <?php if ($org['latest_update']): ?>
            <h5 class="mt-4">Latest Update</h5>
            <p><?= nl2br(e($org['latest_update'])) ?></p>
          <?php endif; ?>

          <?php if ($org['meeting_schedule']): ?>
            <p class="mt-4"><strong>Meeting Schedule:</strong> <?= e($org['meeting_schedule']) ?></p>
          <?php endif; ?>

          <?php if (!empty($leadership)): ?>
            <h5 class="mt-4">Leadership</h5>
            <ul>
              <?php foreach ($leadership as $person): ?>
                <li><?= e(is_array($person) ? ($person['role'] ?? '') . ': ' . ($person['name'] ?? '') : (string) $person) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
