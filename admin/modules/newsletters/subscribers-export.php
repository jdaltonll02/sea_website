<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('newsletters');
$pdo = db();

$subscribers = $pdo->query('SELECT email, name, group_name, subscribed_at, is_confirmed FROM subscribers ORDER BY subscribed_at DESC')->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Email', 'Name', 'Group', 'Subscribed At', 'Confirmed']);
foreach ($subscribers as $s) {
    fputcsv($out, [$s['email'], $s['name'] ?? '', $s['group_name'] ?? '', $s['subscribed_at'], $s['is_confirmed'] ? 'Yes' : 'No']);
}
fclose($out);
