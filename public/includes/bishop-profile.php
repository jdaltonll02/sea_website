<?php
/**
 * Shared render for a dioceses_bishops record — used by both bishop.php
 * (type='diocesan') and suffragan.php (type='suffragan', is_current=1).
 * Nothing person-specific lives in this file; it only renders whatever
 * $bishop array is passed in.
 *
 * Expects: $bishop (array|false), $eyebrowText (string)
 */
if (!$bishop): ?>
  <div class="saa-empty-state">
    <h3>No record on file yet</h3>
    <p>This portfolio has not been published. Please check back soon.</p>
  </div>
<?php else:
  $ordinationHistory = [];
  if (!empty($bishop['ordination_history_json'])) {
      $decoded = json_decode($bishop['ordination_history_json'], true);
      if (is_array($decoded)) {
          $ordinationHistory = $decoded;
      }
  }
?>
<div class="row g-5 align-items-start">
  <div class="col-lg-4 text-center">
    <img src="<?= e(upload_url($bishop['photo'])) ?>" alt="<?= e($bishop['name']) ?>" class="img-fluid rounded shadow-sm mb-3">
    <?php if (!empty($bishop['motto'])): ?>
      <p class="fst-italic" style="color: var(--saa-red);">&ldquo;<?= e($bishop['motto']) ?>&rdquo;</p>
    <?php endif; ?>
  </div>
  <div class="col-lg-8">
    <p class="eyebrow"><?= e($eyebrowText ?? '') ?></p>
    <h1><?= e($bishop['name']) ?></h1>
    <h5 class="text-muted mb-3"><?= e($bishop['title']) ?></h5>

    <?php if (!empty($bishop['bio_short'])): ?>
      <p class="fs-5"><?= e($bishop['bio_short']) ?></p>
    <?php endif; ?>
    <?php if (!empty($bishop['bio_full'])): ?>
      <p><?= nl2br(e($bishop['bio_full'])) ?></p>
    <?php endif; ?>

    <div class="row g-4 mt-2">
      <?php if (!empty($bishop['consecration_date']) || !empty($bishop['enthronement_date'])): ?>
        <div class="col-sm-6">
          <h6 class="text-uppercase small text-muted">Key Dates</h6>
          <?php if (!empty($bishop['consecration_date'])): ?>
            <p class="mb-1">Consecrated: <?= e(format_date($bishop['consecration_date'])) ?></p>
          <?php endif; ?>
          <?php if (!empty($bishop['enthronement_date'])): ?>
            <p class="mb-1">Enthroned: <?= e(format_date($bishop['enthronement_date'])) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($bishop['education'])): ?>
        <div class="col-sm-6">
          <h6 class="text-uppercase small text-muted">Education</h6>
          <p class="mb-1"><?= nl2br(e($bishop['education'])) ?></p>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($ordinationHistory)): ?>
      <h6 class="text-uppercase small text-muted mt-4">Ordination History</h6>
      <ul>
        <?php foreach ($ordinationHistory as $item): ?>
          <li><?= e(is_array($item) ? ($item['event'] ?? '') . ' — ' . ($item['date'] ?? '') : (string) $item) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (!empty($bishop['contact_office'])): ?>
      <p class="text-muted mt-4"><strong>Office:</strong> <?= e($bishop['contact_office']) ?></p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
