<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();

$upcoming = $pdo->query(
    'SELECT * FROM events WHERE start_datetime >= NOW() ORDER BY start_datetime ASC'
)->fetchAll();

$past = $pdo->query(
    'SELECT * FROM events WHERE start_datetime < NOW() ORDER BY start_datetime DESC LIMIT 6'
)->fetchAll();

$monthLectionary = $pdo->query(
    "SELECT * FROM lectionary_days WHERE `date` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY `date` ASC"
)->fetchAll();

$eventTypeLabels = [
    'feast' => 'Feast',
    'ordination' => 'Ordination',
    'synod' => 'Synod',
    'confirmation' => 'Confirmation',
    'other' => 'Other',
];

$pageTitle = 'Events & Liturgical Calendar';
$currentPage = 'events';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">What's Ahead</p>
      <h1>Events &amp; Liturgical Calendar</h1>
    </div>

    <div class="row g-5">
      <div class="col-lg-8">
        <h4 class="mb-3">Upcoming Events</h4>
        <?php if (empty($upcoming)): ?>
          <div class="saa-empty-state"><p>No upcoming events scheduled.</p></div>
        <?php else: ?>
          <div class="list-group mb-5">
            <?php foreach ($upcoming as $event): ?>
              <div class="list-group-item py-3">
                <span class="badge mb-1" style="background-color: var(--saa-red);"><?= e($eventTypeLabels[$event['event_type']] ?? $event['event_type']) ?></span>
                <h6 class="mb-1"><?= e($event['title']) ?></h6>
                <p class="text-muted small mb-1">
                  <?= e(format_date($event['start_datetime'], 'F j, Y g:i A')) ?>
                  <?php if ($event['location']): ?> &middot; <?= e($event['location']) ?><?php endif; ?>
                </p>
                <?php if ($event['description']): ?><p class="mb-0"><?= e($event['description']) ?></p><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($past)): ?>
          <h4 class="mb-3">Recent Past Events</h4>
          <div class="list-group">
            <?php foreach ($past as $event): ?>
              <div class="list-group-item py-2 text-muted">
                <?= e(format_date($event['start_datetime'], 'M j, Y')) ?> &middot; <?= e($event['title']) ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-4">
        <div class="saa-today-widget">
          <p class="eyebrow mb-3">Liturgical Calendar</p>
          <?php if (empty($monthLectionary)): ?>
            <p class="text-muted mb-0">Not yet published for this period.</p>
          <?php else: ?>
            <?php foreach ($monthLectionary as $day): ?>
              <div class="d-flex justify-content-between border-bottom py-2">
                <span><?= e(format_date($day['date'], 'M j')) ?></span>
                <span class="text-muted small">
                  <span class="liturgical-color-swatch" style="background-color: <?= e(strtolower($day['color'] ?? '#ccc')) ?>;"></span>
                  <?= e($day['saint_of_day'] ?: $day['season']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
