<?php
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/xml; charset=utf-8');

$pdo = db();
$staticPages = [
    'index.php', 'pages/about.php', 'pages/bishop.php', 'pages/suffragan.php',
    'pages/archdeacons.php', 'pages/pro-cathedral.php', 'pages/church.php',
    'pages/organizations.php', 'pages/clergy.php', 'pages/blog.php',
    'pages/letters.php', 'pages/events.php', 'pages/newsletter-archive.php',
    'pages/gallery.php', 'pages/give.php', 'pages/contact.php',
];

$churchSlugs = $pdo->query('SELECT slug, updated_at FROM churches')->fetchAll();
$orgSlugs = $pdo->query('SELECT slug, updated_at FROM organizations')->fetchAll();
$postSlugs = $pdo->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published'")->fetchAll();
$publicLetters = $pdo->query("SELECT id, created_at FROM administrative_letters WHERE visibility = 'public'")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <?php foreach ($staticPages as $page): ?>
  <url><loc><?= e(base_url($page)) ?></loc></url>
  <?php endforeach; ?>
  <?php foreach ($churchSlugs as $row): ?>
  <url><loc><?= e(base_url('pages/church-single.php?slug=' . $row['slug'])) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($row['updated_at']))) ?></lastmod></url>
  <?php endforeach; ?>
  <?php foreach ($orgSlugs as $row): ?>
  <url><loc><?= e(base_url('pages/organization-single.php?slug=' . $row['slug'])) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($row['updated_at']))) ?></lastmod></url>
  <?php endforeach; ?>
  <?php foreach ($postSlugs as $row): ?>
  <url><loc><?= e(base_url('pages/blog-single.php?slug=' . $row['slug'])) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($row['updated_at']))) ?></lastmod></url>
  <?php endforeach; ?>
  <?php foreach ($publicLetters as $row): ?>
  <url><loc><?= e(base_url('pages/letter-download.php?id=' . $row['id'])) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($row['created_at']))) ?></lastmod></url>
  <?php endforeach; ?>
</urlset>
