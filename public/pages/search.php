<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$results = ['churches' => [], 'clergy' => [], 'organizations' => [], 'posts' => []];

if ($q !== '') {
    $stmt = $pdo->prepare('SELECT name, slug FROM churches WHERE name LIKE :q LIMIT 10');
    $stmt->execute(['q' => $like]);
    $results['churches'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT full_name, id FROM clergy WHERE full_name LIKE :q AND status = 'active' LIMIT 10");
    $stmt->execute(['q' => $like]);
    $results['clergy'] = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT name, slug FROM organizations WHERE name LIKE :q LIMIT 10');
    $stmt->execute(['q' => $like]);
    $results['organizations'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT title, slug FROM blog_posts WHERE title LIKE :q AND status = 'published' LIMIT 10");
    $stmt->execute(['q' => $like]);
    $results['posts'] = $stmt->fetchAll();
}

$totalResults = array_sum(array_map('count', $results));

$pageTitle = 'Search Results';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Search</p>
      <h1>Search the Archdeaconry Site</h1>
    </div>

    <form method="get" class="row g-2 justify-content-center mb-5">
      <div class="col-sm-6">
        <input type="search" name="q" class="form-control form-control-lg" placeholder="Search churches, clergy, organizations, posts..." value="<?= e($q) ?>">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn saa-btn-accent btn-lg">Search</button>
      </div>
    </form>

    <?php if ($q === ''): ?>
      <div class="saa-empty-state"><p>Enter a search term above.</p></div>
    <?php elseif ($totalResults === 0): ?>
      <div class="saa-empty-state"><p>No results found for "<?= e($q) ?>".</p></div>
    <?php else: ?>
      <div class="row g-4">
        <?php if (!empty($results['churches'])): ?>
          <div class="col-md-6">
            <h5>Churches</h5>
            <ul class="list-unstyled">
              <?php foreach ($results['churches'] as $r): ?>
                <li class="mb-1"><a href="<?= e(base_url('pages/church-single.php?slug=' . $r['slug'])) ?>"><?= e($r['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if (!empty($results['clergy'])): ?>
          <div class="col-md-6">
            <h5>Clergy</h5>
            <ul class="list-unstyled">
              <?php foreach ($results['clergy'] as $r): ?>
                <li class="mb-1"><a href="<?= e(base_url('pages/clergy.php')) ?>"><?= e($r['full_name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if (!empty($results['organizations'])): ?>
          <div class="col-md-6">
            <h5>Organizations</h5>
            <ul class="list-unstyled">
              <?php foreach ($results['organizations'] as $r): ?>
                <li class="mb-1"><a href="<?= e(base_url('pages/organization-single.php?slug=' . $r['slug'])) ?>"><?= e($r['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if (!empty($results['posts'])): ?>
          <div class="col-md-6">
            <h5>Blog Posts</h5>
            <ul class="list-unstyled">
              <?php foreach ($results['posts'] as $r): ?>
                <li class="mb-1"><a href="<?= e(base_url('pages/blog-single.php?slug=' . $r['slug'])) ?>"><?= e($r['title']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
