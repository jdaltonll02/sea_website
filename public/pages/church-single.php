<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$slug = trim($_GET['slug'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM churches WHERE slug = :slug');
$stmt->execute(['slug' => $slug]);
$church = $stmt->fetch();

$priest = false;
$organizations = [];
if ($church) {
    if (!empty($church['current_priest_id'])) {
        $ps = $pdo->prepare('SELECT * FROM clergy WHERE id = :id');
        $ps->execute(['id' => $church['current_priest_id']]);
        $priest = $ps->fetch();
    }
    $os = $pdo->prepare('SELECT * FROM organizations WHERE church_id = :id');
    $os->execute(['id' => $church['id']]);
    $organizations = $os->fetchAll();
}

$needsMap = true;
$pageTitle = $church['name'] ?? 'Church Not Found';
$currentPage = 'churches';
include __DIR__ . '/../includes/header.php';
?>

<?php if (!$church): ?>
  <div class="saa-empty-state">
    <h3>Church Not Found</h3>
    <p>We couldn't find the church you were looking for.</p>
    <a href="<?= e(base_url('pages/church.php')) ?>" class="btn saa-btn-accent">Back to Churches</a>
  </div>
<?php else: ?>
  <?php $isExtended = false; include __DIR__ . '/../includes/church-profile.php'; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
