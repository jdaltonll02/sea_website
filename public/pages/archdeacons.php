<?php
require_once __DIR__ . '/../includes/helpers.php';

$archdeacons = db()->query(
    'SELECT * FROM archdeacons ORDER BY term_start ASC'
)->fetchAll();

$pageTitle = 'Past Archdeacons';
$currentPage = 'archdeacons';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Leadership</p>
      <h1>Archdeacons of the Southeastern Archdeaconry</h1>
    </div>

    <?php if (empty($archdeacons)): ?>
      <div class="saa-empty-state"><p>No archdeacon records have been published yet.</p></div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($archdeacons as $a): $isCurrent = empty($a['term_end']); ?>
          <div class="col-md-6 col-lg-4">
            <div class="card saa-card h-100">
              <img src="<?= e(upload_url($a['photo'])) ?>" alt="<?= e($a['name']) ?>">
              <div class="card-body">
                <?php if ($isCurrent): ?>
                  <span class="badge mb-2" style="background-color: var(--saa-red);">Current Archdeacon</span>
                <?php endif; ?>
                <h5><?= e($a['name']) ?></h5>
                <p class="text-muted small mb-2">
                  <?= e(format_date($a['term_start'], 'Y')) ?> &ndash;
                  <?= $isCurrent ? 'Present' : e(format_date($a['term_end'], 'Y')) ?>
                </p>
                <p><?= e($a['bio'] ?? '') ?></p>
                <?php if (!empty($a['achievements'])): ?>
                  <p class="small text-muted mb-0"><strong>Notable:</strong> <?= e($a['achievements']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
