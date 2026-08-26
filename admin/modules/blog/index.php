<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('blog');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category') {
    require_csrf();

    $catName = trim($_POST['category_name'] ?? '');
    if ($catName === '') {
        flash('error', 'Category name is required.');
    } else {
        $catSlug = slugify($catName);
        $slugCheck = $pdo->prepare('SELECT id FROM blog_categories WHERE slug = :slug');
        $slugCheck->execute(['slug' => $catSlug]);
        if ($slugCheck->fetch()) {
            $catSlug .= '-' . bin2hex(random_bytes(2));
        }

        $stmt = $pdo->prepare('INSERT INTO blog_categories (name, slug) VALUES (:name, :slug)');
        $stmt->execute(['name' => $catName, 'slug' => $catSlug]);
        log_activity('create', 'blog_category', (int) $pdo->lastInsertId());
        flash('success', 'Category added.');
    }
    redirect('/admin/modules/blog/index.php');
}

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = 'blog_posts.title LIKE :q';
    $params['q'] = '%' . $q . '%';
}
if ($status !== '') {
    $conditions[] = 'blog_posts.status = :status';
    $params['status'] = $status;
}
if ($categoryId > 0) {
    $conditions[] = 'blog_posts.category_id = :category_id';
    $params['category_id'] = $categoryId;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$total = (int) (function () use ($pdo, $where, $params) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT blog_posts.*, blog_categories.name AS category_name
     FROM blog_posts LEFT JOIN blog_categories ON blog_categories.id = blog_posts.category_id
     $where ORDER BY blog_posts.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$categories = $pdo->query(
    'SELECT blog_categories.*, COUNT(blog_posts.id) AS post_count
     FROM blog_categories LEFT JOIN blog_posts ON blog_posts.category_id = blog_categories.id
     GROUP BY blog_categories.id ORDER BY blog_categories.name'
)->fetchAll();

$statusBadges = ['draft' => 'secondary', 'published' => 'success', 'scheduled' => 'warning'];

$pageTitle = 'Blog';
$activeModule = 'blog';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="card admin-stat-card mb-3">
  <div class="card-body">
    <div class="row g-3 align-items-start">
      <div class="col-md-5">
        <h6 class="mb-2">Add Category</h6>
        <form method="post" class="d-flex gap-2">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_category">
          <input type="text" name="category_name" class="form-control" placeholder="Category name" required>
          <button type="submit" class="btn btn-outline-secondary">Add</button>
        </form>
      </div>
      <div class="col-md-7">
        <h6 class="mb-2">Categories</h6>
        <?php if (empty($categories)): ?>
          <p class="text-muted small mb-0">No categories yet.</p>
        <?php else: ?>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($categories as $cat): ?>
              <span class="badge bg-light text-dark border"><?= e($cat['name']) ?> (<?= (int) $cat['post_count'] ?>)</span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form method="get" class="d-flex gap-2 flex-wrap">
    <input type="text" name="q" class="form-control" placeholder="Search by title..." value="<?= e($q) ?>">
    <select name="status" class="form-select">
      <option value="">All Statuses</option>
      <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
      <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
      <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
    </select>
    <select name="category_id" class="form-select">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary" type="submit">Filter</button>
  </form>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Post</a>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th>Views</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($posts)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No blog posts found.</td></tr>
        <?php else: foreach ($posts as $post): ?>
          <tr>
            <td><?= e($post['title']) ?></td>
            <td><?= e($post['category_name'] ?? '—') ?></td>
            <td><span class="badge bg-<?= e($statusBadges[$post['status']] ?? 'secondary') ?>"><?= e(ucfirst($post['status'])) ?></span></td>
            <td><?= $post['published_at'] ? e(format_date($post['published_at'], 'M j, Y g:ia')) : '—' ?></td>
            <td><?= (int) $post['views_count'] ?></td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $post['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this blog post? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
