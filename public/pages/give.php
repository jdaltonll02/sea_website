<?php
require_once __DIR__ . '/../includes/helpers.php';

$pageTitle = 'Give / Support the Archdeaconry';
$currentPage = 'give';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Partner With Us</p>
      <h1>Give / Support the Archdeaconry</h1>
      <p class="text-muted">
        Your generosity sustains the mission, clergy support, and community outreach of the
        Southeastern Archdeaconry. Below are the ways you can give.
      </p>
    </div>

    <div class="row g-4 justify-content-center">
      <div class="col-md-6">
        <div class="card saa-card h-100">
          <div class="card-body">
            <h5>Bank Transfer</h5>
            <p style="white-space: pre-line;"><?= e(setting('giving_bank_details', 'Bank details will be published soon.')) ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card saa-card h-100">
          <div class="card-body">
            <h5>Mobile Money</h5>
            <p style="white-space: pre-line;"><?= e(setting('giving_mobile_money', 'Mobile Money details will be published soon.')) ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <p class="text-muted">For questions about giving, please <a href="<?= e(base_url('pages/contact.php')) ?>">contact the Archdeaconry office</a>.</p>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
