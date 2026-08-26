<?php
require_once __DIR__ . '/../includes/helpers.php';

// Generic by design: whichever record is currently flagged as the sitting
// Suffragan Bishop renders here. No name is ever hardcoded in this template.
$bishop = db()->query(
    "SELECT * FROM dioceses_bishops WHERE type = 'suffragan' AND is_current = 1 ORDER BY order_number DESC LIMIT 1"
)->fetch();

$pageTitle = "Suffragan Bishop's Portfolio";
$currentPage = 'suffragan';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <?php $eyebrowText = 'Suffragan Bishop'; include __DIR__ . '/../includes/bishop-profile.php'; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
