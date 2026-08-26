</main>

<section class="saa-newsletter-band">
  <div class="container text-center">
    <h3>Stay Connected</h3>
    <p class="mb-4">Subscribe to receive news, pastoral letters, and event updates from the Southeastern Archdeaconry.</p>
    <form id="newsletterForm" class="row justify-content-center g-2" novalidate data-action="<?= e(base_url('pages/newsletter-subscribe.php')) ?>">
      <?= csrf_field() ?>
      <div class="col-sm-5 col-md-4">
        <input type="email" name="email" class="form-control form-control-lg" placeholder="Your email address" required>
      </div>
      <div class="col-sm-4 col-md-3">
        <input type="text" name="group_name" class="form-control form-control-lg" placeholder="Group (optional)">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn saa-btn-accent btn-lg">Subscribe</button>
      </div>
    </form>
    <div id="newsletterFormMessage" class="mt-3"></div>
  </div>
</section>

<footer class="saa-footer">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-4">
        <h5 class="saa-footer-heading">Southeastern Archdeaconry</h5>
        <p>Episcopal Church of Liberia</p>
        <p><?= nl2br(e(setting('address', "St. Mark's Episcopal Church, Gregory Street, Harper, Maryland County, Liberia"))) ?></p>
      </div>
      <div class="col-md-4">
        <h5 class="saa-footer-heading">Site Map</h5>
        <ul class="list-unstyled saa-footer-links">
          <li><a href="<?= e(base_url('pages/about.php')) ?>">About the Archdeaconry</a></li>
          <li><a href="<?= e(base_url('pages/church.php')) ?>">Churches</a></li>
          <li><a href="<?= e(base_url('pages/clergy.php')) ?>">Clergy Registry</a></li>
          <li><a href="<?= e(base_url('pages/blog.php')) ?>">Blog</a></li>
          <li><a href="<?= e(base_url('pages/contact.php')) ?>">Contact</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h5 class="saa-footer-heading">Contact</h5>
        <p>Email: <?= e(setting('contact_email', 'info@southeasternarchdeaconry.org')) ?></p>
        <p>Phone: <?= e(setting('contact_phone', '')) ?></p>
        <div class="saa-social-links">
          <?php $fb = setting('facebook_url', ''); ?>
          <?php if ($fb): ?><a href="<?= e($fb) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
        </div>
      </div>
    </div>
    <hr class="saa-footer-divider">
    <p class="text-center small mb-0">&copy; <?= date('Y') ?> Southeastern Archdeaconry, Episcopal Church of Liberia. All rights reserved.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($needsMap)): ?>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script src="<?= e(asset_url('js/main.js')) ?>"></script>
</body>
</html>
