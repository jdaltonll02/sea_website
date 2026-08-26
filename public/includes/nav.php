<?php
/**
 * Primary site navigation. Included by header.php.
 * $currentPage may be set by the calling page to highlight the active link.
 */
$currentPage = $currentPage ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark saa-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(base_url('index.php')) ?>">
      <img src="<?= e(asset_url('images/logo.png')) ?>" alt="Southeastern Archdeaconry crest" height="40" onerror="this.style.display='none'">
      <span class="brand-text">Southeastern Archdeaconry</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainNavOffcanvas" aria-controls="mainNavOffcanvas" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="offcanvas offcanvas-end saa-navbar" tabindex="-1" id="mainNavOffcanvas">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>" href="<?= e(base_url('index.php')) ?>">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'about' ? 'active' : '' ?>" href="<?= e(base_url('pages/about.php')) ?>">About</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Leadership</a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= e(base_url('pages/bishop.php')) ?>">Bishop of Liberia</a></li>
              <li><a class="dropdown-item" href="<?= e(base_url('pages/suffragan.php')) ?>">Suffragan Bishop</a></li>
              <li><a class="dropdown-item" href="<?= e(base_url('pages/archdeacons.php')) ?>">Past Archdeacons</a></li>
              <li><a class="dropdown-item" href="<?= e(base_url('pages/pro-cathedral.php')) ?>">Pro-Cathedral</a></li>
            </ul>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'churches' ? 'active' : '' ?>" href="<?= e(base_url('pages/church.php')) ?>">Churches</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'organizations' ? 'active' : '' ?>" href="<?= e(base_url('pages/organizations.php')) ?>">Organizations</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'clergy' ? 'active' : '' ?>" href="<?= e(base_url('pages/clergy.php')) ?>">Clergy Registry</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'blog' ? 'active' : '' ?>" href="<?= e(base_url('pages/blog.php')) ?>">Blog</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'letters' ? 'active' : '' ?>" href="<?= e(base_url('pages/letters.php')) ?>">Letters &amp; Documents</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'events' ? 'active' : '' ?>" href="<?= e(base_url('pages/events.php')) ?>">Events</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'gallery' ? 'active' : '' ?>" href="<?= e(base_url('pages/gallery.php')) ?>">Gallery</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'contact' ? 'active' : '' ?>" href="<?= e(base_url('pages/contact.php')) ?>">Contact</a>
          </li>
          <li class="nav-item ms-lg-2">
            <a class="btn saa-btn-accent btn-sm" href="<?= e(base_url('pages/give.php')) ?>">Give</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
