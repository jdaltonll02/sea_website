<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$relatedType = trim($_GET['type'] ?? '');

$sql = 'SELECT * FROM media_gallery WHERE 1=1';
$params = [];
if ($relatedType !== '') {
    $sql .= ' AND related_type = :type';
    $params['type'] = $relatedType;
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$media = $stmt->fetchAll();

$typeLabels = [
    'church' => 'Churches',
    'organization' => 'Organizations',
    'event' => 'Events',
    'blog_post' => 'Blog',
    'general' => 'General',
];

$pageTitle = 'Gallery';
$currentPage = 'gallery';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Life Across the Archdeaconry</p>
      <h1>Photo &amp; Video Gallery</h1>
    </div>

    <form method="get" class="row g-2 justify-content-center mb-5">
      <div class="col-sm-4">
        <select name="type" class="form-select">
          <option value="">All Media</option>
          <?php foreach ($typeLabels as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $relatedType === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn saa-btn-accent">Filter</button>
      </div>
    </form>

    <?php if (empty($media)): ?>
      <div class="saa-empty-state"><p>No media has been uploaded yet.</p></div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($media as $item): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= e(upload_url($item['file_path'])) ?>" target="_blank" rel="noopener">
              <?php if ($item['type'] === 'video'): ?>
                <video src="<?= e(upload_url($item['file_path'])) ?>" class="img-fluid rounded" style="height:180px; width:100%; object-fit:cover;" muted></video>
              <?php else: ?>
                <img src="<?= e(upload_url($item['file_path'])) ?>" alt="<?= e($item['alt_text'] ?? $item['title'] ?? '') ?>" class="img-fluid rounded" style="height:180px; width:100%; object-fit:cover;">
              <?php endif; ?>
            </a>
            <?php if ($item['title']): ?><p class="small text-muted mt-1 mb-0"><?= e($item['title']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
