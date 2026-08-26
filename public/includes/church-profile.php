<?php
/**
 * Shared render for a single church record.
 * Expects: $church (array), $priest (array|false), $organizations (array),
 * $galleryImages (array), $isExtended (bool) — true renders the extra
 * Pro-Cathedral intro block.
 */
$gallery = [];
if (!empty($church['gallery_json'])) {
    $decoded = json_decode($church['gallery_json'], true);
    if (is_array($decoded)) {
        $gallery = $decoded;
    }
}
?>
<div class="saa-hero" style="min-height: <?= $isExtended ? '60vh' : '45vh' ?>;">
  <div class="carousel-item" style="position:absolute; inset:0;">
    <img src="<?= e(upload_url($church['image_cover'])) ?>" alt="<?= e($church['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
  </div>
  <div class="saa-hero-overlay"></div>
  <div class="container saa-hero-content">
    <?php if ($church['is_pro_cathedra']): ?>
      <p class="text-uppercase fw-semibold" style="letter-spacing:.08em; color: var(--saa-gold);">Pro-Cathedral &middot; Seat of the Suffragan Bishop</p>
    <?php endif; ?>
    <h1><?= e($church['name']) ?></h1>
    <div class="saa-hero-accent-line"></div>
    <p class="fs-5"><?= e($church['address'] ?? '') ?></p>
  </div>
</div>

<section class="saa-section">
  <div class="container">
    <?php if ($isExtended): ?>
      <div class="saa-today-widget mb-5">
        <p class="eyebrow mb-1">About the Pro-Cathedral</p>
        <p class="mb-0">
          As the Pro-Cathedral of the Southeastern Archdeaconry, <?= e($church['name']) ?> serves as the seat of
          the Suffragan Bishop, hosting ordinations, confirmations, and archdeaconry-wide services in addition to
          its regular parish life.
        </p>
      </div>
    <?php endif; ?>

    <div class="row g-5">
      <div class="col-lg-7">
        <h3>Our History</h3>
        <p><?= nl2br(e($church['short_history'] ?? 'History coming soon.')) ?></p>

        <?php if (!empty($church['latest_update'])): ?>
          <h3 class="mt-4">Latest Update</h3>
          <p><?= nl2br(e($church['latest_update'])) ?></p>
        <?php endif; ?>

        <?php if (!empty($gallery)): ?>
          <h3 class="mt-4">Photo Gallery</h3>
          <div class="row g-2">
            <?php foreach ($gallery as $img): ?>
              <div class="col-4">
                <img src="<?= e(upload_url($img)) ?>" class="img-fluid rounded" alt="<?= e($church['name']) ?> photo">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($organizations)): ?>
          <h3 class="mt-4">Organizations at <?= e($church['name']) ?></h3>
          <ul>
            <?php foreach ($organizations as $org): ?>
              <li><a href="<?= e(base_url('pages/organization-single.php?slug=' . $org['slug'])) ?>"><?= e($org['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="col-lg-5">
        <div class="saa-today-widget mb-4">
          <p class="eyebrow mb-2">Church Details</p>
          <p class="mb-1"><strong>Status:</strong> <?= e(church_status_label($church['status'] ?? null)) ?></p>
          <p class="mb-1"><strong>District:</strong> <?= e($church['town_district'] ?? '') ?></p>
          <?php if (!empty($church['founding_date'])): ?>
            <p class="mb-1"><strong>Founded:</strong> <?= e(format_date($church['founding_date'])) ?></p>
          <?php endif; ?>
          <?php if (!empty($church['patron_saint'])): ?>
            <p class="mb-1"><strong>Patron Saint:</strong> <?= e($church['patron_saint']) ?></p>
          <?php endif; ?>
          <?php if (!empty($church['service_times'])): ?>
            <p class="mb-0"><strong>Service Times:</strong> <?= e($church['service_times']) ?></p>
          <?php endif; ?>
        </div>

        <?php if ($priest): ?>
          <div class="card saa-card mb-4">
            <div class="card-body text-center">
              <img src="<?= e(upload_url($priest['photo'])) ?>" alt="<?= e($priest['full_name']) ?>" class="rounded-circle mb-2" style="width:96px; height:96px; object-fit:cover;">
              <h6 class="mb-0"><?= e($priest['full_name']) ?></h6>
              <p class="text-muted small mb-2">Current Priest</p>
              <a href="<?= e(base_url('pages/clergy.php')) ?>" class="btn btn-sm btn-outline-primary">View Clergy Registry</a>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($church['lat']) && !empty($church['lng'])): ?>
          <div id="churchMap" style="height:260px; border-radius:8px;" data-lat="<?= e((string) $church['lat']) ?>" data-lng="<?= e((string) $church['lng']) ?>" data-name="<?= e($church['name']) ?>"></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
