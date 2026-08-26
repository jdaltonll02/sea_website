<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$siteName = setting('site_name', 'Southeastern Archdeaconry — Episcopal Church of Liberia');
$pageTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' . $siteName : $siteName;
$pageDescription = $pageDescription ?? setting('mission_statement', 'The Southeastern Archdeaconry of the Episcopal Church of Liberia.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php if (!empty($needsMap)): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= e(asset_url('css/theme.css')) ?>">
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<main>
