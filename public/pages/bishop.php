<?php
require_once __DIR__ . '/../includes/helpers.php';

$bishop = db()->query(
    "SELECT * FROM dioceses_bishops WHERE type = 'diocesan' ORDER BY is_current DESC, order_number DESC LIMIT 1"
)->fetch();

$pageTitle = 'Bishop of the Episcopal Church of Liberia';
$currentPage = 'bishop';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <?php $eyebrowText = 'Diocesan Bishop'; include __DIR__ . '/../includes/bishop-profile.php'; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
