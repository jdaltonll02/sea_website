<?php
/**
 * Admin sidebar navigation. Items are hidden per-role, but every module
 * ALSO enforces require_module_access() server-side — this is UX only.
 */
$menuGroups = [
    'Content' => [
        ['key' => 'blog', 'label' => 'Blog', 'icon' => 'bi-pencil-square', 'href' => 'modules/blog/index.php'],
        ['key' => 'pages-content', 'label' => 'Static Pages', 'icon' => 'bi-file-earmark-text', 'href' => 'modules/pages-content/index.php'],
        ['key' => 'letters', 'label' => 'Letters & Documents', 'icon' => 'bi-envelope-paper', 'href' => 'modules/letters/index.php'],
        ['key' => 'media', 'label' => 'Media Library', 'icon' => 'bi-images', 'href' => 'modules/media/index.php'],
    ],
    'Leadership & Registry' => [
        ['key' => 'bishops', 'label' => 'Bishops', 'icon' => 'bi-person-badge', 'href' => 'modules/bishops/index.php'],
        ['key' => 'archdeacons', 'label' => 'Archdeacons', 'icon' => 'bi-person-lines-fill', 'href' => 'modules/archdeacons/index.php'],
        ['key' => 'clergy', 'label' => 'Clergy Registry', 'icon' => 'bi-people', 'href' => 'modules/clergy/index.php'],
        ['key' => 'churches', 'label' => 'Churches', 'icon' => 'bi-building', 'href' => 'modules/churches/index.php'],
        ['key' => 'organizations', 'label' => 'Organizations', 'icon' => 'bi-diagram-3', 'href' => 'modules/organizations/index.php'],
        ['key' => 'employees', 'label' => 'Employees', 'icon' => 'bi-briefcase', 'href' => 'modules/employees/index.php'],
    ],
    'Engagement' => [
        ['key' => 'newsletters', 'label' => 'Newsletters', 'icon' => 'bi-newspaper', 'href' => 'modules/newsletters/index.php'],
        ['key' => 'events', 'label' => 'Events & Calendar', 'icon' => 'bi-calendar-event', 'href' => 'modules/events/index.php'],
        ['key' => 'testimonials', 'label' => 'Testimonials', 'icon' => 'bi-chat-quote', 'href' => 'modules/testimonials/index.php'],
    ],
    'System' => [
        ['key' => 'users', 'label' => 'Users & Roles', 'icon' => 'bi-shield-lock', 'href' => 'modules/users/index.php'],
        ['key' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear', 'href' => 'modules/settings/index.php'],
        ['key' => 'activity-log', 'label' => 'Activity Log', 'icon' => 'bi-clock-history', 'href' => 'modules/activity-log/index.php'],
    ],
];
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-sidebar-brand">
    <a href="<?= e(base_url('admin/index.php')) ?>">Southeastern Archdeaconry</a>
  </div>
  <nav class="admin-sidebar-nav">
    <a class="admin-nav-link <?= $activeModule === 'dashboard' ? 'active' : '' ?>" href="<?= e(base_url('admin/index.php')) ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <?php foreach ($menuGroups as $groupLabel => $items): ?>
      <?php $visibleItems = array_filter($items, fn($item) => can_access_module($item['key'])); ?>
      <?php if (!empty($visibleItems)): ?>
        <p class="admin-nav-heading"><?= e($groupLabel) ?></p>
        <?php foreach ($visibleItems as $item): ?>
          <a class="admin-nav-link <?= $activeModule === $item['key'] ? 'active' : '' ?>" href="<?= e(base_url('admin/' . $item['href'])) ?>">
            <i class="bi <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</aside>
