<?php
require_once __DIR__ . '/../includes/helpers.php';

$nid = (int) ($_GET['nid'] ?? 0);
$sid = (int) ($_GET['sid'] ?? 0);

if ($nid && $sid) {
    $stmt = db()->prepare(
        'UPDATE newsletter_recipients SET opened_at = NOW()
         WHERE newsletter_id = :nid AND subscriber_id = :sid AND opened_at IS NULL'
    );
    $stmt->execute(['nid' => $nid, 'sid' => $sid]);
}

header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7');
