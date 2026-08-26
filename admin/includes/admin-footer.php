    </main>
  </div>
</div>

<?php if (empty($hideQuickAdd)): ?>
<div class="dropdown admin-quick-add">
  <button class="btn saa-btn-accent rounded-circle shadow" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Add">
    <i class="bi bi-plus-lg"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li><a class="dropdown-item" href="<?= e(base_url('admin/modules/blog/form.php')) ?>"><i class="bi bi-pencil-square me-2"></i>New Blog Post</a></li>
    <li><a class="dropdown-item" href="<?= e(base_url('admin/modules/letters/form.php')) ?>"><i class="bi bi-envelope-paper me-2"></i>New Letter</a></li>
    <li><a class="dropdown-item" href="<?= e(base_url('admin/modules/events/form.php')) ?>"><i class="bi bi-calendar-event me-2"></i>New Event</a></li>
  </ul>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($needsCharts)): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?= e(asset_url('js/admin.js')) ?>"></script>
</body>
</html>
