<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$viewing = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM newsletters WHERE id = :id AND status = 'sent'");
    $stmt->execute(['id' => $id]);
    $viewing = $stmt->fetch();
}

$newsletters = $pdo->query(
    "SELECT * FROM newsletters WHERE status = 'sent' ORDER BY sent_at DESC"
)->fetchAll();

$pageTitle = 'Newsletter Archive';
$currentPage = 'newsletter';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Newsletter</p>
      <h1>Past Issues</h1>
    </div>

    <?php if ($viewing): ?>
      <div class="row justify-content-center mb-5">
        <div class="col-lg-8">
          <a href="<?= e(base_url('pages/newsletter-archive.php')) ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; All Issues</a>
          <h3><?= e($viewing['subject']) ?></h3>
          <p class="text-muted"><?= e(format_date($viewing['sent_at'])) ?></p>
          <div class="saa-post-body"><?= $viewing['body_html'] ?></div>
        </div>
      </div>
    <?php else: ?>
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <?php if (empty($newsletters)): ?>
            <div class="saa-empty-state"><p>No newsletters have been sent yet.</p></div>
          <?php else: ?>
            <div class="list-group">
              <?php foreach ($newsletters as $n): ?>
                <a href="?id=<?= (int) $n['id'] ?>" class="list-group-item list-group-item-action py-3">
                  <h6 class="mb-1"><?= e($n['subject']) ?></h6>
                  <p class="text-muted small mb-0"><?= e(format_date($n['sent_at'])) ?></p>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
