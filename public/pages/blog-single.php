<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$slug = trim($_GET['slug'] ?? '');

$stmt = $pdo->prepare(
    "SELECT blog_posts.*, blog_categories.name AS category_name, users.name AS author_name
     FROM blog_posts
     LEFT JOIN blog_categories ON blog_categories.id = blog_posts.category_id
     LEFT JOIN users ON users.id = blog_posts.author_id
     WHERE blog_posts.slug = :slug AND blog_posts.status = 'published'"
);
$stmt->execute(['slug' => $slug]);
$post = $stmt->fetch();

$related = [];
if ($post) {
    $upd = $pdo->prepare('UPDATE blog_posts SET views_count = views_count + 1 WHERE id = :id');
    $upd->execute(['id' => $post['id']]);

    $rel = $pdo->prepare(
        "SELECT * FROM blog_posts
         WHERE category_id = :category_id AND id != :id AND status = 'published'
         ORDER BY published_at DESC LIMIT 3"
    );
    $rel->execute(['category_id' => $post['category_id'], 'id' => $post['id']]);
    $related = $rel->fetchAll();
}

$pageTitle = $post['title'] ?? 'Post Not Found';
$pageDescription = $post['excerpt'] ?? null;
$currentPage = 'blog';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <?php if (!$post): ?>
      <div class="saa-empty-state">
        <h3>Post Not Found</h3>
        <a href="<?= e(base_url('pages/blog.php')) ?>" class="btn saa-btn-accent">Back to Blog</a>
      </div>
    <?php else: ?>
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <?php if ($post['category_name']): ?>
            <span class="badge mb-2" style="background-color: var(--saa-blue);"><?= e($post['category_name']) ?></span>
          <?php endif; ?>
          <h1><?= e($post['title']) ?></h1>
          <p class="text-muted">
            <?= e(format_date($post['published_at'])) ?>
            <?php if ($post['author_name']): ?> &middot; by <?= e($post['author_name']) ?><?php endif; ?>
            &middot; <?= (int) $post['views_count'] ?> views
          </p>
          <img src="<?= e(upload_url($post['cover_image'])) ?>" alt="<?= e($post['title']) ?>" class="img-fluid rounded mb-4">
          <div class="saa-post-body"><?= $post['body'] ?? '' ?></div>
        </div>
      </div>

      <?php if (!empty($related)): ?>
        <hr class="my-5">
        <h4 class="mb-4 text-center">Related Posts</h4>
        <div class="row g-4">
          <?php foreach ($related as $r): ?>
            <div class="col-md-4">
              <a href="<?= e(base_url('pages/blog-single.php?slug=' . $r['slug'])) ?>" class="text-decoration-none text-reset">
                <div class="card saa-card h-100">
                  <img src="<?= e(upload_url($r['cover_image'])) ?>" alt="<?= e($r['title']) ?>">
                  <div class="card-body">
                    <h6><?= e($r['title']) ?></h6>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
