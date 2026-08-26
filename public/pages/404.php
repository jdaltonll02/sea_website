<?php
require_once __DIR__ . '/../includes/helpers.php';

http_response_code(404);
$pageTitle = 'Page Not Found';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-empty-state">
      <h1 class="font-heading" style="font-size: 4rem; color: var(--saa-red);">404</h1>
      <h3>Page Not Found</h3>
      <p>The page you're looking for doesn't exist or may have been moved.</p>
      <a href="<?= e(base_url('index.php')) ?>" class="btn saa-btn-accent">Return Home</a>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
