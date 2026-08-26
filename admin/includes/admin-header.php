<?php
/**
 * Admin layout head + opens the sidebar/content wrapper.
 * Callers must require_module_access() BEFORE including this file.
 * Expects (optional): $pageTitle, $activeModule, $needsCharts, $needsRichText
 */
require_once __DIR__ . '/admin-functions.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$activeModule = $activeModule ?? '';
$flashMsg = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> | Admin | Southeastern Archdeaconry</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset_url('css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/admin.css')) ?>">
  <?php if (!empty($needsRichText)): ?>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
  <?php endif; ?>
</head>
<body class="admin-body">
<div class="admin-shell">
  <?php include __DIR__ . '/admin-sidebar.php'; ?>
  <div class="admin-content">
    <header class="admin-topbar d-flex justify-content-between align-items-center">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="adminSidebarToggle"><i class="bi bi-list"></i></button>
      <h5 class="mb-0"><?= e($pageTitle) ?></h5>
      <div class="d-flex align-items-center gap-3">
        <form action="<?= e(base_url('pages/search.php')) ?>" method="get" class="d-none d-md-block">
          <input type="search" name="q" class="form-control form-control-sm" placeholder="Global search...">
        </form>
        <span class="text-muted small"><?= e(current_user()['name'] ?? '') ?></span>
        <a href="<?= e(base_url('admin/logout.php')) ?>" class="btn btn-sm btn-outline-danger">Log Out</a>
      </div>
    </header>
    <main class="admin-main">
      <?php if ($flashMsg): ?>
        <div class="alert alert-<?= e($flashMsg['type'] === 'error' ? 'danger' : $flashMsg['type']) ?> alert-dismissible fade show">
          <?= e($flashMsg['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
