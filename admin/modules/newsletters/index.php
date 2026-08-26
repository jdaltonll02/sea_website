<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('newsletters');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_subscriber') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $name = trim($_POST['name'] ?? '');
        $group = trim($_POST['group_name'] ?? '');
        if (!$email) {
            flash('error', 'Please enter a valid email address.');
        } else {
            $existing = $pdo->prepare('SELECT id FROM subscribers WHERE email = :email');
            $existing->execute(['email' => $email]);
            if ($existing->fetch()) {
                flash('error', 'That email is already subscribed.');
            } else {
                $token = bin2hex(random_bytes(24));
                $stmt = $pdo->prepare(
                    'INSERT INTO subscribers (email, name, group_name, is_confirmed, unsubscribe_token) VALUES (:email, :name, :group_name, 1, :token)'
                );
                $stmt->execute(['email' => $email, 'name' => $name ?: null, 'group_name' => $group ?: null, 'token' => $token]);
                log_activity('create', 'subscriber', (int) $pdo->lastInsertId());
                flash('success', 'Subscriber added.');
            }
        }
    } elseif ($action === 'update_group') {
        $subId = (int) ($_POST['subscriber_id'] ?? 0);
        $group = trim($_POST['group_name'] ?? '');
        if ($subId) {
            $stmt = $pdo->prepare('UPDATE subscribers SET group_name = :group_name WHERE id = :id');
            $stmt->execute(['group_name' => $group ?: null, 'id' => $subId]);
            log_activity('update', 'subscriber', $subId);
            flash('success', 'Subscriber group updated.');
        }
    } elseif ($action === 'remove_subscriber') {
        $subId = (int) ($_POST['subscriber_id'] ?? 0);
        if ($subId) {
            $stmt = $pdo->prepare('DELETE FROM subscribers WHERE id = :id');
            $stmt->execute(['id' => $subId]);
            log_activity('delete', 'subscriber', $subId);
            flash('success', 'Subscriber removed.');
        }
    }

    redirect('/admin/modules/newsletters/index.php');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$total = (int) $pdo->query('SELECT COUNT(*) FROM newsletters')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT n.*,
        (SELECT COUNT(*) FROM newsletter_recipients nr WHERE nr.newsletter_id = n.id) AS recipient_count,
        (SELECT COUNT(*) FROM newsletter_recipients nr WHERE nr.newsletter_id = n.id AND nr.opened_at IS NOT NULL) AS opened_count
     FROM newsletters n
     ORDER BY n.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute();
$newsletters = $stmt->fetchAll();

$subscribers = $pdo->query('SELECT * FROM subscribers ORDER BY subscribed_at DESC')->fetchAll();
$existingGroups = $pdo->query(
    "SELECT DISTINCT group_name FROM subscribers WHERE group_name IS NOT NULL AND group_name != '' ORDER BY group_name"
)->fetchAll(PDO::FETCH_COLUMN);

$statusBadges = [
    'draft' => 'bg-secondary',
    'sent' => 'bg-success',
    'scheduled' => 'bg-info text-dark',
];

$pageTitle = 'Newsletters';
$activeModule = 'newsletters';
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Newsletters</h5>
  <a href="form.php" class="btn saa-btn-accent"><i class="bi bi-plus-lg me-1"></i>Compose Newsletter</a>
</div>

<div class="card admin-stat-card mb-4">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Subject</th><th>Status</th><th>Target</th><th>Sent</th><th>Recipients</th><th>Opened</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($newsletters)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No newsletters found.</td></tr>
        <?php else: foreach ($newsletters as $n): ?>
          <tr>
            <td><?= e($n['subject']) ?></td>
            <td><span class="badge <?= e($statusBadges[$n['status']] ?? 'bg-secondary') ?>"><?= e(ucfirst($n['status'])) ?></span></td>
            <td><?= $n['target_group'] ? e($n['target_group']) : 'All subscribers' ?></td>
            <td><?= $n['sent_at'] ? e(format_date($n['sent_at'], 'M j, Y g:i A')) : '—' ?></td>
            <td><?= (int) $n['recipient_count'] ?></td>
            <td><?= (int) $n['opened_count'] ?></td>
            <td class="text-end">
              <a href="form.php?id=<?= (int) $n['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
              <form action="delete.php" method="post" class="d-inline" data-confirm="Delete this newsletter? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
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
  <nav class="mb-4">
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Subscribers</h5>
  <a href="subscribers-export.php" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Export CSV</a>
</div>

<div class="card admin-stat-card mb-4">
  <div class="card-body">
    <form method="post" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_subscriber">
      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Name (optional)</label>
        <input type="text" name="name" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Group (optional)</label>
        <input type="text" name="group_name" class="form-control" list="subscriberGroups" placeholder="e.g. Choir, Clergy">
        <datalist id="subscriberGroups">
          <?php foreach ($existingGroups as $g): ?>
            <option value="<?= e($g) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn saa-btn-accent w-100">Add Subscriber</button>
      </div>
    </form>
  </div>
</div>

<div class="card admin-stat-card">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr><th>Email</th><th>Name</th><th>Group</th><th>Subscribed</th><th>Confirmed</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($subscribers)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No subscribers yet.</td></tr>
        <?php else: foreach ($subscribers as $s): ?>
          <tr>
            <td><?= e($s['email']) ?></td>
            <td><?= e($s['name'] ?? '') ?: '—' ?></td>
            <td>
              <form action="index.php" method="post" class="d-flex gap-1">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_group">
                <input type="hidden" name="subscriber_id" value="<?= (int) $s['id'] ?>">
                <input type="text" name="group_name" class="form-control form-control-sm" list="subscriberGroups" value="<?= e($s['group_name'] ?? '') ?>" style="max-width: 140px;">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Save</button>
              </form>
            </td>
            <td><?= e(format_date($s['subscribed_at'], 'M j, Y')) ?></td>
            <td><?= $s['is_confirmed'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
            <td class="text-end">
              <form action="index.php" method="post" class="d-inline" data-confirm="Remove this subscriber?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="remove_subscriber">
                <input type="hidden" name="subscriber_id" value="<?= (int) $s['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
