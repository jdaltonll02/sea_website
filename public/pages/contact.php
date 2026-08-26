<?php
require_once __DIR__ . '/../includes/helpers.php';

$pdo = db();
$churches = $pdo->query(
    "SELECT name, slug, lat, lng FROM churches WHERE lat IS NOT NULL AND lng IS NOT NULL"
)->fetchAll();

$mapChurches = array_map(function ($c) {
    return [
        'name' => $c['name'],
        'lat' => (float) $c['lat'],
        'lng' => (float) $c['lng'],
        'url' => base_url('pages/church-single.php?slug=' . $c['slug']),
    ];
}, $churches);

$needsMap = true;
$pageTitle = 'Contact Us';
$currentPage = 'contact';
include __DIR__ . '/../includes/header.php';
?>
<section class="saa-section">
  <div class="container">
    <div class="saa-section-heading text-center">
      <p class="eyebrow">Get In Touch</p>
      <h1>Contact Us</h1>
    </div>

    <div class="row g-5">
      <div class="col-lg-6">
        <form id="contactForm" data-action="<?= e(base_url('pages/contact-submit.php')) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone (optional)</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" rows="5" required></textarea>
          </div>
          <button type="submit" class="btn saa-btn-accent btn-lg">Send Message</button>
          <div id="contactFormMessage" class="mt-3"></div>
        </form>
      </div>
      <div class="col-lg-6">
        <div class="mb-4">
          <p class="mb-1"><strong>Email:</strong> <?= e(setting('contact_email')) ?></p>
          <p class="mb-1"><strong>Phone:</strong> <?= e(setting('contact_phone')) ?></p>
          <p class="mb-0"><strong>Address:</strong> <?= e(setting('address')) ?></p>
        </div>
        <div id="contactMap" style="height:320px; border-radius:8px;" data-churches='<?= e(json_encode($mapChurches)) ?>'></div>
      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('contactForm');
  if (!form) return;
  var messageBox = document.getElementById('contactFormMessage');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    messageBox.textContent = '';
    var submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    fetch(form.getAttribute('data-action'), {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        messageBox.textContent = data.message || '';
        messageBox.className = data.success ? 'mt-3 text-success' : 'mt-3 text-danger';
        if (data.success) form.reset();
      })
      .catch(function () {
        messageBox.textContent = 'Something went wrong. Please try again later.';
        messageBox.className = 'mt-3 text-danger';
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
