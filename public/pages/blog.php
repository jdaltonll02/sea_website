<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();

$category = trim($_GET['category'] ?? '');
$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

$where = ["status = 'published'", 'published_at <= NOW()'];
$params = [];
if ($category !== '') {
    $where[] = 'category_id = (SELECT id FROM blog_categories WHERE slug = :category)';
    $params['category'] = $category;
}
if ($q !== '') {
    $where[] = 'title LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT blog_posts.*, blog_categories.name AS category_name, blog_categories.slug AS category_slug
     FROM blog_posts LEFT JOIN blog_categories ON blog_categories.id = blog_posts.category_id
     WHERE $whereSql ORDER BY published_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM blog_categories ORDER BY name')->fetchAll();

$pageTitle = 'Blog & News';
$currentPage = 'blog';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Stay Informed</p>
      <h1>Blog &amp; News</h1>
    </div>

    <form method="get" class="row g-2 justify-content-center mb-5">
      <div class="col-sm-4">
        <input type="text" name="q" class="form-control" placeholder="Search posts" value="<?= e($q) ?>">
      </div>
      <div class="col-sm-3">
        <select name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= e($c['slug']) ?>" <?= $category === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn saa-btn-accent">Search</button>
      </div>
    </form>

    <?php if (empty($posts)): ?>
      <div class="saa-empty-state"><p>No posts found.</p></div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($posts as $post): ?>
          <div class="col-md-6 col-lg-4">
            <a href="<?= e(base_url('pages/blog-single.php?slug=' . $post['slug'])) ?>" class="text-decoration-none text-reset">
              <div class="card saa-card h-100">
                <img src="<?= e(upload_url($post['cover_image'])) ?>" alt="<?= e($post['title']) ?>">
                <div class="card-body">
                  <?php if ($post['category_name']): ?>
                    <span class="badge mb-2" style="background-color: var(--saa-blue);"><?= e($post['category_name']) ?></span>
                  <?php endif; ?>
                  <h5><?= e($post['title']) ?></h5>
                  <p class="text-muted small"><?= e(format_date($post['published_at'])) ?></p>
                  <p><?= e($post['excerpt'] ?? '') ?></p>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
