<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('blog');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$post = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $post = $stmt->fetch();
    if (!$post) {
        flash('error', 'Blog post not found.');
        redirect('/admin/modules/blog/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: slugify($title);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body = $_POST['body'] ?? '';
    $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
    $tags = trim($_POST['tags'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');
    $publishedAtRaw = trim($_POST['published_at'] ?? '');
    $publishedAt = $publishedAtRaw ? str_replace('T', ' ', $publishedAtRaw) . ':00' : null;

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!in_array($status, ['draft', 'published', 'scheduled'], true)) {
        $errors[] = 'Invalid status.';
    }
    if (in_array($status, ['published', 'scheduled'], true) && !$publishedAt) {
        $errors[] = 'Published date/time is required for published or scheduled posts.';
    }
    if ($status === 'draft') {
        $publishedAt = null;
    }

    $slugCheck = $pdo->prepare('SELECT id FROM blog_posts WHERE slug = :slug AND id != :id');
    $slugCheck->execute(['slug' => $slug, 'id' => $id]);
    if ($slugCheck->fetch()) {
        $slug .= '-' . bin2hex(random_bytes(2));
    }

    if (empty($errors)) {
        $coverImage = handle_file_upload('cover_image', 'blog', ['jpg', 'jpeg', 'png', 'webp']);

        if ($post) {
            $sql = 'UPDATE blog_posts SET title = :title, slug = :slug, excerpt = :excerpt, body = :body,
                    category_id = :category_id, tags = :tags, status = :status, published_at = :published_at';
            if ($coverImage) {
                $sql .= ', cover_image = :cover_image';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO blog_posts (title, slug, excerpt, body, category_id, tags, status, published_at,
                    author_id' . ($coverImage ? ', cover_image' : '') . ')
                    VALUES (:title, :slug, :excerpt, :body, :category_id, :tags, :status, :published_at,
                    :author_id' . ($coverImage ? ', :cover_image' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'title' => $title, 'slug' => $slug, 'excerpt' => $excerpt, 'body' => $body,
            'category_id' => $categoryId, 'tags' => $tags, 'status' => $status, 'published_at' => $publishedAt,
        ];
        if ($post) {
            $bind['id'] = $id;
        } else {
            $bind['author_id'] = current_user()['id'] ?? null;
        }
        if ($coverImage) {
            $bind['cover_image'] = $coverImage;
        }
        $stmt->execute($bind);

        $newId = $post ? $id : (int) $pdo->lastInsertId();
        log_activity($post ? 'update' : 'create', 'blog_post', $newId);
        flash('success', 'Blog post saved successfully.');
        redirect('/admin/modules/blog/index.php');
    }
}

$categories = $pdo->query('SELECT id, name FROM blog_categories ORDER BY name')->fetchAll();

$authorName = current_user()['name'] ?? 'Unknown';
if ($post && $post['author_id']) {
    $authorStmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
    $authorStmt->execute(['id' => $post['author_id']]);
    $authorRow = $authorStmt->fetch();
    if ($authorRow) {
        $authorName = $authorRow['name'];
    }
}

$publishedAtValue = '';
if (!empty($post['published_at'])) {
    $publishedAtValue = str_replace(' ', 'T', substr($post['published_at'], 0, 16));
}

$needsRichText = true;
$pageTitle = $post ? 'Edit Blog Post' : 'Add Blog Post';
$activeModule = 'blog';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card" id="blogPostForm" data-autosave-key="blog-<?= $post['id'] ?? 'new' ?>">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($post): ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="<?= e($post['title'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Slug (optional)</label>
        <input type="text" name="slug" class="form-control" value="<?= e($post['slug'] ?? '') ?>" placeholder="auto-generated">
      </div>

      <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select">
          <option value="">— None —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= (int) ($post['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tags (comma-separated)</label>
        <input type="text" name="tags" class="form-control" value="<?= e($post['tags'] ?? '') ?>" placeholder="faith, community, outreach">
      </div>
      <div class="col-md-4">
        <label class="form-label">Author</label>
        <input type="text" class="form-control" value="<?= e($authorName) ?>" readonly disabled>
      </div>

      <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" id="statusSelect" class="form-select">
          <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
          <option value="scheduled" <?= ($post['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Published Date/Time</label>
        <input type="datetime-local" name="published_at" id="publishedAtInput" class="form-control" value="<?= e($publishedAtValue) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Cover Image</label>
        <input type="file" name="cover_image" class="form-control" accept="image/*">
        <?php if (!empty($post['cover_image'])): ?>
          <img src="<?= e(upload_url($post['cover_image'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>

      <div class="col-12">
        <label class="form-label">Excerpt</label>
        <textarea name="excerpt" class="form-control" rows="3" maxlength="500"><?= e($post['excerpt'] ?? '') ?></textarea>
      </div>

      <div class="col-12">
        <label class="form-label">Body</label>
        <div id="bodyEditor" style="min-height:250px; background:#fff;"><?= $post['body'] ?? '' ?></div>
        <textarea name="body" id="bodyInput" class="d-none"><?= e($post['body'] ?? '') ?></textarea>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Post</button>
  </div>
</form>

<script>
  var quill = new Quill('#bodyEditor', { theme: 'snow' });
  document.getElementById('blogPostForm').addEventListener('submit', function () {
    document.getElementById('bodyInput').value = quill.root.innerHTML;
  });
</script>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
