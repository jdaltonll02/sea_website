<?php
require_once __DIR__ . '/../includes/helpers.php';

$clergy = db()->query(
    "SELECT clergy.*, churches.name AS church_name
     FROM clergy
     LEFT JOIN churches ON churches.id = clergy.home_church_id
     WHERE clergy.status = 'active'
     ORDER BY clergy.order_type, clergy.full_name"
)->fetchAll();

$grouped = ['priest' => [], 'deacon' => [], 'lay_reader' => []];
foreach ($clergy as $c) {
    $grouped[$c['order_type']][] = $c;
}
$labels = ['priest' => 'Priests', 'deacon' => 'Deacons', 'lay_reader' => 'Lay Readers'];

$pageTitle = 'Clergy Registry';
$currentPage = 'clergy';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Serving Our Parishes</p>
      <h1>Clergy Registry</h1>
    </div>

    <div class="row justify-content-center mb-4">
      <div class="col-md-6">
        <input type="search" id="clergySearch" class="form-control form-control-lg" placeholder="Search by name...">
      </div>
    </div>

    <ul class="nav nav-pills justify-content-center mb-4" id="clergyTabs" role="tablist">
      <?php foreach ($labels as $type => $label): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $type === 'priest' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-<?= $type ?>" type="button">
            <?= e($label) ?> (<?= count($grouped[$type]) ?>)
          </button>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="tab-content">
      <?php foreach ($labels as $type => $label): ?>
        <div class="tab-pane fade <?= $type === 'priest' ? 'show active' : '' ?>" id="tab-<?= $type ?>">
          <?php if (empty($grouped[$type])): ?>
            <div class="saa-empty-state"><p>No <?= strtolower(e($label)) ?> on record yet.</p></div>
          <?php else: ?>
            <div class="row g-4 clergy-grid">
              <?php foreach ($grouped[$type] as $person): ?>
                <div class="col-md-6 col-lg-4 clergy-card" data-name="<?= e(strtolower($person['full_name'])) ?>">
                  <div class="card saa-card h-100" role="button" data-bs-toggle="modal" data-bs-target="#clergyModal<?= (int) $person['id'] ?>">
                    <img src="<?= e(upload_url($person['photo'])) ?>" alt="<?= e($person['full_name']) ?>">
                    <div class="card-body">
                      <h6 class="mb-0"><?= e($person['title'] ? $person['title'] . ' ' . $person['full_name'] : $person['full_name']) ?></h6>
                      <p class="text-muted small mb-0"><?= e($person['church_name'] ?? '') ?></p>
                    </div>
                  </div>
                </div>

                <div class="modal fade" id="clergyModal<?= (int) $person['id'] ?>" tabindex="-1">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title"><?= e($person['title'] ? $person['title'] . ' ' . $person['full_name'] : $person['full_name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <img src="<?= e(upload_url($person['photo'])) ?>" alt="<?= e($person['full_name']) ?>" class="img-fluid rounded mb-3">
                        <?php if ($person['church_name']): ?><p><strong>Church:</strong> <?= e($person['church_name']) ?></p><?php endif; ?>
                        <?php if ($person['ordination_date']): ?><p><strong>Ordained:</strong> <?= e(format_date($person['ordination_date'])) ?></p><?php endif; ?>
                        <?php if ($person['bio_full'] ?? $person['bio_short']): ?>
                          <p><?= nl2br(e($person['bio_full'] ?: $person['bio_short'])) ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var search = document.getElementById('clergySearch');
  if (!search) return;
  search.addEventListener('input', function () {
    var term = search.value.trim().toLowerCase();
    document.querySelectorAll('.clergy-card').forEach(function (card) {
      card.style.display = card.dataset.name.includes(term) ? '' : 'none';
    });
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
