<?php
require_once __DIR__ . '/includes/admin-functions.php';

require_login();
$pdo = db();

$stats = [
    'churches' => (int) $pdo->query('SELECT COUNT(*) FROM churches')->fetchColumn(),
    'clergy' => (int) $pdo->query('SELECT COUNT(*) FROM clergy')->fetchColumn(),
    'organizations' => (int) $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn(),
    'published_posts' => (int) $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn(),
    'subscribers' => (int) $pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn(),
    'unread_messages' => (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(),
];

// Subscriber growth, last 6 months
$subscriberGrowth = $pdo->query(
    "SELECT DATE_FORMAT(subscribed_at, '%Y-%m') AS month, COUNT(*) AS total
     FROM subscribers
     WHERE subscribed_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month ORDER BY month"
)->fetchAll();

// Posts published per month, last 6 months
$postsPerMonth = $pdo->query(
    "SELECT DATE_FORMAT(published_at, '%Y-%m') AS month, COUNT(*) AS total
     FROM blog_posts
     WHERE status = 'published' AND published_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month ORDER BY month"
)->fetchAll();

// Clergy by status
$clergyByStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total FROM clergy GROUP BY status'
)->fetchAll();

// Pending testimonial approvals
$pendingTestimonials = (int) $pdo->query('SELECT COUNT(*) FROM testimonies WHERE approved = 0')->fetchColumn();

$recentActivity = $pdo->query(
    "SELECT activity_log.*, users.name AS user_name
     FROM activity_log LEFT JOIN users ON users.id = activity_log.user_id
     ORDER BY activity_log.created_at DESC LIMIT 10"
)->fetchAll();

$pageTitle = 'Dashboard';
$activeModule = 'dashboard';
$needsCharts = true;
include __DIR__ . '/includes/admin-header.php';
?>

<?php if ($pendingTestimonials > 0 && can_access_module('testimonials')): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="bi bi-bell me-2"></i><?= $pendingTestimonials ?> testimonial(s) awaiting approval.</span>
    <a href="<?= e(base_url('admin/modules/testimonials/index.php')) ?>" class="btn btn-sm btn-outline-dark">Review</a>
  </div>
<?php endif; ?>
<?php if ($stats['unread_messages'] > 0): ?>
  <div class="alert alert-info">
    <i class="bi bi-envelope me-2"></i><?= $stats['unread_messages'] ?> unread contact message(s).
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-2">
    <div class="card admin-stat-card"><div class="card-body"><div class="stat-value"><?= $stats['churches'] ?></div><div class="text-muted small">Churches</div></div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card admin-stat-card"><div class="card-body"><div class="stat-value"><?= $stats['clergy'] ?></div><div class="text-muted small">Clergy</div></div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card admin-stat-card"><div class="card-body"><div class="stat-value"><?= $stats['organizations'] ?></div><div class="text-muted small">Organizations</div></div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card admin-stat-card"><div class="card-body"><div class="stat-value"><?= $stats['published_posts'] ?></div><div class="text-muted small">Published Posts</div></div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card admin-stat-card"><div class="card-body"><div class="stat-value"><?= $stats['subscribers'] ?></div><div class="text-muted small">Subscribers</div></div></div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card admin-stat-card"><div class="card-body"><div class="stat-value"><?= $stats['unread_messages'] ?></div><div class="text-muted small">Unread Messages</div></div></div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-4">
    <div class="card admin-stat-card h-100"><div class="card-body">
      <h6>Subscriber Growth</h6>
      <canvas id="subscriberChart" height="180"></canvas>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card admin-stat-card h-100"><div class="card-body">
      <h6>Posts Published / Month</h6>
      <canvas id="postsChart" height="180"></canvas>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card admin-stat-card h-100"><div class="card-body">
      <h6>Clergy by Status</h6>
      <canvas id="clergyChart" height="180"></canvas>
    </div></div>
  </div>
</div>

<div class="card admin-stat-card">
  <div class="card-body">
    <h6>Recent Activity</h6>
    <?php if (empty($recentActivity)): ?>
      <p class="text-muted mb-0">No activity recorded yet.</p>
    <?php else: ?>
      <ul class="list-unstyled mb-0">
        <?php foreach ($recentActivity as $log): ?>
          <li class="border-bottom py-2 small">
            <strong><?= e($log['user_name'] ?? 'System') ?></strong> &mdash; <?= e($log['action']) ?>
            <?php if ($log['target_type']): ?> (<?= e($log['target_type']) ?> #<?= (int) $log['target_id'] ?>)<?php endif; ?>
            <span class="text-muted float-end"><?= e(format_date($log['created_at'], 'M j, Y g:i A')) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<script>
const subscriberData = <?= json_encode($subscriberGrowth) ?>;
const postsData = <?= json_encode($postsPerMonth) ?>;
const clergyData = <?= json_encode($clergyByStatus) ?>;

new Chart(document.getElementById('subscriberChart'), {
  type: 'line',
  data: {
    labels: subscriberData.map(r => r.month),
    datasets: [{ label: 'New Subscribers', data: subscriberData.map(r => r.total), borderColor: '#A16207', tension: 0.3 }]
  },
  options: { plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('postsChart'), {
  type: 'bar',
  data: {
    labels: postsData.map(r => r.month),
    datasets: [{ label: 'Posts Published', data: postsData.map(r => r.total), backgroundColor: '#14532D' }]
  },
  options: { plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('clergyChart'), {
  type: 'doughnut',
  data: {
    labels: clergyData.map(r => r.status),
    datasets: [{ data: clergyData.map(r => r.total), backgroundColor: ['#A16207', '#14532D', '#D4AF37', '#6b7280'] }]
  }
});
</script>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
