<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();

// Live stats
$churchCount = (int) $pdo->query('SELECT COUNT(*) FROM churches')->fetchColumn();
$clergyCount = (int) $pdo->query("SELECT COUNT(*) FROM clergy WHERE status = 'active'")->fetchColumn();
$orgCount = (int) $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn();

// Hero carousel images: church cover photos, falling back to a placeholder
$heroImages = $pdo->query(
    'SELECT name, image_cover FROM churches WHERE image_cover IS NOT NULL ORDER BY is_pro_cathedra DESC LIMIT 5'
)->fetchAll();

// Today in the Church
$today = $pdo->prepare('SELECT * FROM lectionary_days WHERE `date` = CURDATE() LIMIT 1');
$today->execute();
$todayLiturgy = $today->fetch();

// Featured story: latest published blog post
$featuredPost = $pdo->query(
    "SELECT blog_posts.*, blog_categories.name AS category_name
     FROM blog_posts
     LEFT JOIN blog_categories ON blog_categories.id = blog_posts.category_id
     WHERE status = 'published' AND published_at <= NOW()
     ORDER BY published_at DESC LIMIT 1"
)->fetch();

// Meet the Clergy spotlight (rotate by day of year so it changes daily without randomness at request time)
$activeClergy = $pdo->query(
    "SELECT clergy.*, churches.name AS church_name
     FROM clergy
     LEFT JOIN churches ON churches.id = clergy.home_church_id
     WHERE clergy.status = 'active'
     ORDER BY clergy.id"
)->fetchAll();
$clergySpotlight = null;
if (!empty($activeClergy)) {
    $index = (int) date('z') % count($activeClergy);
    $clergySpotlight = $activeClergy[$index];
}

// Testimonials (approved only)
$testimonials = $pdo->query(
    "SELECT testimonies.*, churches.name AS church_name
     FROM testimonies
     LEFT JOIN churches ON churches.id = testimonies.church_id
     WHERE approved = 1
     ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

// Upcoming events + next-event countdown
$upcomingEvents = $pdo->query(
    "SELECT * FROM events WHERE start_datetime >= NOW() ORDER BY start_datetime ASC LIMIT 3"
)->fetchAll();
$nextEvent = $upcomingEvents[0] ?? null;

// Pro-Cathedral weekly schedule
$proCathedral = $pdo->query('SELECT * FROM churches WHERE is_pro_cathedra = 1 LIMIT 1')->fetch();
$proCathedralSchedule = [];
if ($proCathedral) {
    $sched = $pdo->prepare('SELECT * FROM service_schedules WHERE church_id = :id ORDER BY sort_order ASC, id ASC');
    $sched->execute(['id' => $proCathedral['id']]);
    $proCathedralSchedule = $sched->fetchAll();
}

// Districts for the "Find a Church" quick search
$districts = $pdo->query(
    'SELECT DISTINCT town_district FROM churches WHERE town_district IS NOT NULL ORDER BY town_district'
)->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = '';
$currentPage = 'home';
include __DIR__ . '/includes/header.php';
?>

<!-- ============================== HERO ============================== -->
<section class="saa-hero">
  <?php if (!empty($heroImages)): ?>
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
      <div class="carousel-inner">
        <?php foreach ($heroImages as $i => $img): ?>
          <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <img src="<?= e(upload_url($img['image_cover'])) ?>" alt="<?= e($img['name']) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
  <div class="saa-hero-overlay"></div>
  <div class="container saa-hero-content">
    <div class="row">
      <div class="col-lg-8">
        <p class="text-uppercase fw-semibold" style="letter-spacing:.08em; color: var(--saa-gold);">Episcopal Church of Liberia</p>
        <h1>The Southeastern Archdeaconry</h1>
        <div class="saa-hero-accent-line"></div>
        <p class="fs-5 mb-4"><?= e(setting('mission_statement')) ?></p>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= e(base_url('pages/about.php')) ?>" class="btn saa-btn-accent btn-lg">Learn Our Story</a>
          <a href="<?= e(base_url('pages/church.php')) ?>" class="btn btn-outline-light btn-lg">Find a Church</a>
        </div>
      </div>
    </div>
  </div>
</section>
<hr class="saa-divider">

<!-- ===================== LIVE STATS BANNER ===================== -->
<section class="saa-stats-banner">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-md-4">
        <div class="stat-number"><?= $churchCount ?></div>
        <div class="stat-label">Churches</div>
      </div>
      <div class="col-md-4">
        <div class="stat-number"><?= $clergyCount ?></div>
        <div class="stat-label">Active Clergy</div>
      </div>
      <div class="col-md-4">
        <div class="stat-number"><?= $orgCount ?></div>
        <div class="stat-label">Organizations Serving Southeastern Liberia</div>
      </div>
    </div>
  </div>
</section>

<!-- ============ TODAY IN THE CHURCH + FIND A CHURCH ============ -->
<section class="saa-section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="saa-today-widget h-100">
          <p class="eyebrow mb-1">Today in the Church</p>
          <h4 class="mb-2"><?= e(format_date('now', 'l, F j, Y')) ?></h4>
          <?php if ($todayLiturgy): ?>
            <p class="mb-1">
              <span class="liturgical-color-swatch" style="background-color: <?= e(strtolower($todayLiturgy['color'] ?? '#ccc')) ?>;"></span>
              <strong><?= e($todayLiturgy['season'] ?? '') ?></strong>
            </p>
            <?php if (!empty($todayLiturgy['saint_of_day'])): ?>
              <p class="mb-0 text-muted">Commemoration: <?= e($todayLiturgy['saint_of_day']) ?></p>
            <?php endif; ?>
          <?php else: ?>
            <p class="text-muted mb-0">Liturgical details for today have not yet been published.</p>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="saa-today-widget h-100">
          <p class="eyebrow mb-1">Find a Church Near You</p>
          <h4 class="mb-3">Search by District</h4>
          <form action="<?= e(base_url('pages/church.php')) ?>" method="get" class="d-flex gap-2">
            <select name="district" class="form-select form-select-lg">
              <option value="">All Districts</option>
              <?php foreach ($districts as $district): ?>
                <option value="<?= e($district) ?>"><?= e($district) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn saa-btn-accent btn-lg">Search</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== PRO-CATHEDRAL SCHEDULE ===================== -->
<?php if ($proCathedral && !empty($proCathedralSchedule)): ?>
<section class="saa-section saa-section-cream">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Weekly Schedule</p>
      <h2>Pro-Cathedral Schedule</h2>
      <p class="text-muted">
        <?= e($proCathedral['name']) ?><?= $proCathedral['town_district'] ? ', ' . e($proCathedral['town_district']) : '' ?>
      </p>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="table-responsive">
          <table class="table table-hover saa-card bg-white align-middle mb-0">
            <thead>
              <tr><th>Activity</th><th>Day</th><th>Time</th></tr>
            </thead>
            <tbody>
              <?php foreach ($proCathedralSchedule as $item): ?>
                <tr>
                  <td><?= e($item['activity']) ?></td>
                  <td><?= e($item['day_label']) ?></td>
                  <td><?= e($item['time_label']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================== FEATURED STORY ===================== -->
<?php if ($featuredPost): ?>
<section class="saa-section saa-section-cream">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Featured Story</p>
      <h2>From the Archdeaconry</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card saa-card flex-md-row">
          <img src="<?= e(upload_url($featuredPost['cover_image'])) ?>" class="img-fluid" style="max-width:320px;" alt="<?= e($featuredPost['title']) ?>">
          <div class="card-body">
            <?php if ($featuredPost['category_name']): ?>
              <span class="badge" style="background-color: var(--saa-red);"><?= e($featuredPost['category_name']) ?></span>
            <?php endif; ?>
            <h3 class="mt-2"><?= e($featuredPost['title']) ?></h3>
            <p class="text-muted"><?= e($featuredPost['excerpt']) ?></p>
            <a href="<?= e(base_url('pages/blog-single.php?slug=' . $featuredPost['slug'])) ?>" class="btn btn-outline-primary">Read More</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================== MEET THE CLERGY ===================== -->
<?php if ($clergySpotlight): ?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Meet the Clergy</p>
      <h2>Serving Our Parishes</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card saa-card text-center">
          <img src="<?= e(upload_url($clergySpotlight['photo'])) ?>" alt="<?= e($clergySpotlight['full_name']) ?>">
          <div class="card-body">
            <h4><?= e($clergySpotlight['full_name']) ?></h4>
            <p class="text-muted mb-1"><?= e($clergySpotlight['church_name'] ?? '') ?></p>
            <p><?= e($clergySpotlight['bio_short'] ?? '') ?></p>
            <a href="<?= e(base_url('pages/clergy.php')) ?>" class="btn btn-sm btn-outline-primary">View Clergy Registry</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================== TESTIMONIALS ===================== -->
<?php if (!empty($testimonials)): ?>
<section class="saa-section saa-section-cream">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Stories of Faith</p>
      <h2>What Our Members Say</h2>
    </div>
    <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">
        <?php foreach ($testimonials as $i => $t): ?>
          <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
            <div class="row justify-content-center">
              <div class="col-lg-7 text-center">
                <p class="fs-5 fst-italic">&ldquo;<?= e($t['message']) ?>&rdquo;</p>
                <p class="fw-semibold mb-0"><?= e($t['name']) ?></p>
                <?php if ($t['church_name']): ?><p class="text-muted small"><?= e($t['church_name']) ?></p><?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($testimonials) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" style="filter: invert(1) grayscale(1);"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" style="filter: invert(1) grayscale(1);"></span>
        </button>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================== UPCOMING EVENTS ===================== -->
<?php if (!empty($upcomingEvents)): ?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">What's Ahead</p>
      <h2>Upcoming Events</h2>
      <?php if ($nextEvent): ?>
        <p class="text-muted" data-countdown="<?= e($nextEvent['start_datetime']) ?>" id="eventCountdown">
          Next: <?= e($nextEvent['title']) ?> on <?= e(format_date($nextEvent['start_datetime'])) ?>
        </p>
      <?php endif; ?>
    </div>
    <div class="row g-4">
      <?php foreach ($upcomingEvents as $event): ?>
        <div class="col-md-4">
          <div class="card saa-card">
            <div class="card-body">
              <p class="eyebrow mb-1"><?= e(format_date($event['start_datetime'], 'M j, Y')) ?></p>
              <h5><?= e($event['title']) ?></h5>
              <p class="text-muted small"><?= e($event['location'] ?? '') ?></p>
              <p><?= e(mb_strimwidth($event['description'] ?? '', 0, 120, '...')) ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="<?= e(base_url('pages/events.php')) ?>" class="btn btn-outline-primary">View Full Calendar</a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
