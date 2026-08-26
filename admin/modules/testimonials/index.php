<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('testimonials');
$pdo = db();

$perPage = 12;

$pendingPage = max(1, (int) ($_GET['pending_page'] ?? 1));
$pendingOffset = ($pendingPage - 1) * $perPage;
$pendingTotal = (int) $pdo->query('SELECT COUNT(*) FROM testimonies WHERE approved = 0')->fetchColumn();
$pendingTotalPages = max(1, (int) ceil($pendingTotal / $perPage));

$stmt = $pdo->prepare(
    "SELECT testimonies.*, churches.name AS church_name
     FROM testimonies LEFT JOIN churches ON churches.id = testimonies.church_id
     WHERE testimonies.approved = 0
     ORDER BY testimonies.created_at DESC LIMIT $perPage OFFSET $pendingOffset"
);
$stmt->execute();
$pendingTestimonies = $stmt->fetchAll();

$approvedPage = max(1, (int) ($_GET['approved_page'] ?? 1));
$approvedOffset = ($approvedPage - 1) * $perPage;
$approvedTotal = (int) $pdo->query('SELECT COUNT(*) FROM testimonies WHERE approved = 1')->fetchColumn();
$approvedTotalPages = max(1, (int) ceil($approvedTotal / $perPage));

$stmt = $pdo->prepare(
    "SELECT testimonies.*, churches.name AS church_name
     FROM testimonies LEFT JOIN churches ON churches.id = testimonies.church_id
     WHERE testimonies.approved = 1
     ORDER BY testimonies.created_at DESC LIMIT $perPage OFFSET $approvedOffset"
);
$stmt->execute();
$approvedTestimonies = $stmt->fetchAll();

$pageTitle = 'Testimonials';
$activeModule = 'testimonials';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Testimonials</h5>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Testimony Manually</a>
</div>

<h6 class="text-muted text-uppercase small mb-2">Pending Approval <span class="badge bg-secondary"><?= (int) $pendingTotal ?></span></h6>
<div class="card admin-stat-card mb-4">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Submitted By</th><th>Church</th><th>Message</th><th>Submitted</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($pendingTestimonies)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No testimonies awaiting approval.</td></tr>
        <?php else: foreach ($pendingTestimonies as $t): ?>
          <tr>
            <td>
              <?php if (!empty($t['photo'])): ?>
                <img src="<?= e(upload_url($t['photo'])) ?>" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
              <?php endif; ?>
              <?= e($t['name']) ?>
            </td>
            <td><?= e($t['church_name'] ?? '—') ?></td>
            <td style="max-width:320px;"><?= nl2br(e($t['message'])) ?></td>
            <td class="text-nowrap"><?= e(format_date($t['created_at'])) ?></td>
            <td class="text-end text-nowrap">
              <form action="approve.php" method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <button type="submit" class="btn btn-sm saa-btn-accent">Approve</button>
              </form>
              <form action="reject.php" method="post" class="d-inline" data-confirm="Reject and permanently delete this testimony? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pendingTotalPages > 1): ?>
  <nav class="mb-4">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $pendingTotalPages; $i++): ?>
        <li class="page-item <?= $i === $pendingPage ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pending_page' => $i])) ?>#pending"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<h6 class="text-muted text-uppercase small mb-2">Approved / Published <span class="badge bg-secondary"><?= (int) $approvedTotal ?></span></h6>
<div class="card admin-stat-card mb-4">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Submitted By</th><th>Church</th><th>Message</th><th>Submitted</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($approvedTestimonies)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No published testimonies yet.</td></tr>
        <?php else: foreach ($approvedTestimonies as $t): ?>
          <tr>
            <td>
              <?php if (!empty($t['photo'])): ?>
                <img src="<?= e(upload_url($t['photo'])) ?>" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
              <?php endif; ?>
              <?= e($t['name']) ?>
            </td>
            <td><?= e($t['church_name'] ?? '—') ?></td>
            <td style="max-width:320px;"><?= nl2br(e($t['message'])) ?></td>
            <td class="text-nowrap"><?= e(format_date($t['created_at'])) ?></td>
            <td class="text-end text-nowrap">
              <form action="unpublish.php" method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Unpublish</button>
              </form>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Permanently delete this testimony? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($approvedTotalPages > 1): ?>
  <nav>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $approvedTotalPages; $i++): ?>
        <li class="page-item <?= $i === $approvedPage ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['approved_page' => $i])) ?>#approved"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
