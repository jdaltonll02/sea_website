<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$church = $pdo->query('SELECT * FROM churches WHERE is_pro_cathedra = 1 LIMIT 1')->fetch();

$priest = false;
$organizations = [];
if ($church) {
    if (!empty($church['current_priest_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM clergy WHERE id = :id');
        $stmt->execute(['id' => $church['current_priest_id']]);
        $priest = $stmt->fetch();
    }
    $stmt = $pdo->prepare('SELECT * FROM organizations WHERE church_id = :id');
    $stmt->execute(['id' => $church['id']]);
    $organizations = $stmt->fetchAll();
}

$needsMap = true;
$pageTitle = 'Pro-Cathedral';
$currentPage = 'pro-cathedral';
include __DIR__ . '/../includes/header.php';
?>

<?php if (!$church): ?>
  <div class="saa-empty-state"><p>The Pro-Cathedral record has not been published yet.</p></div>
<?php else: ?>
  <?php $isExtended = true; include __DIR__ . '/../includes/church-profile.php'; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
