<?php
require_once __DIR__ . '/../../includes/admin-functions.php';
require_once __DIR__ . '/../../../public/includes/mailer.php';

require_module_access('newsletters');
$pdo = db();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$newsletter = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM newsletters WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $newsletter = $stmt->fetch();
    if (!$newsletter) {
        flash('error', 'Newsletter not found.');
        redirect('/admin/modules/newsletters/index.php');
    }
}

$isSent = $newsletter && $newsletter['status'] === 'sent';
$errors = [];

$existingGroups = $pdo->query(
    "SELECT DISTINCT group_name FROM subscribers WHERE group_name IS NOT NULL AND group_name != '' ORDER BY group_name"
)->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if ($isSent) {
        flash('error', 'This newsletter has already been sent and cannot be edited.');
        redirect('/admin/modules/newsletters/index.php');
    }

    $subject = trim($_POST['subject'] ?? '');
    $bodyHtml = $_POST['body_html'] ?? '';
    $submitAction = $_POST['submit_action'] ?? 'draft';
    $targetGroup = trim($_POST['target_group'] ?? '') ?: null;

    if ($subject === '') {
        $errors[] = 'Subject is required.';
    }
    if (trim(strip_tags($bodyHtml)) === '') {
        $errors[] = 'Newsletter body cannot be empty.';
    }

    if (empty($errors)) {
        $user = current_user();

        if ($newsletter) {
            $stmt = $pdo->prepare('UPDATE newsletters SET subject = :subject, body_html = :body_html, target_group = :target_group WHERE id = :id');
            $stmt->execute(['subject' => $subject, 'body_html' => $bodyHtml, 'target_group' => $targetGroup, 'id' => $id]);
            $newsletterId = $id;
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO newsletters (subject, body_html, status, target_group, created_by) VALUES (:subject, :body_html, :status, :target_group, :created_by)'
            );
            $stmt->execute([
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'status' => 'draft',
                'target_group' => $targetGroup,
                'created_by' => $user['id'] ?? null,
            ]);
            $newsletterId = (int) $pdo->lastInsertId();
        }

        if ($submitAction === 'send') {
            $pdo->prepare("UPDATE newsletters SET status = 'sent', sent_at = NOW() WHERE id = :id")->execute(['id' => $newsletterId]);

            if ($targetGroup) {
                $subStmt = $pdo->prepare('SELECT id, email, name FROM subscribers WHERE is_confirmed = 1 AND group_name = :group');
                $subStmt->execute(['group' => $targetGroup]);
            } else {
                $subStmt = $pdo->query('SELECT id, email, name FROM subscribers WHERE is_confirmed = 1');
            }
            $subscribers = $subStmt->fetchAll();

            $checkStmt = $pdo->prepare('SELECT id FROM newsletter_recipients WHERE newsletter_id = :nid AND subscriber_id = :sid');
            $insertStmt = $pdo->prepare('INSERT INTO newsletter_recipients (newsletter_id, subscriber_id) VALUES (:nid, :sid)');

            foreach ($subscribers as $subscriber) {
                $checkStmt->execute(['nid' => $newsletterId, 'sid' => $subscriber['id']]);
                if (!$checkStmt->fetch()) {
                    $insertStmt->execute(['nid' => $newsletterId, 'sid' => $subscriber['id']]);
                }

                $trackingUrl = base_url('pages/newsletter-track-open.php?nid=' . $newsletterId . '&sid=' . $subscriber['id']);
                $pixel = '<img src="' . e($trackingUrl) . '" width="1" height="1" alt="" style="display:none;">';

                send_mail($subscriber['email'], $subscriber['name'] ?? '', $subject, $bodyHtml . $pixel);
            }

            log_activity('send', 'newsletter', $newsletterId);
            $groupNote = $targetGroup ? ' in the "' . $targetGroup . '" group' : '';
            flash('success', 'Newsletter sent to ' . count($subscribers) . ' confirmed subscriber(s)' . $groupNote . '.');
        } else {
            log_activity($newsletter ? 'update' : 'create', 'newsletter', $newsletterId);
            flash('success', 'Newsletter draft saved.');
        }

        redirect('/admin/modules/newsletters/index.php');
    }
}

$pageTitle = $isSent ? 'View Newsletter' : ($newsletter ? 'Edit Newsletter' : 'Compose Newsletter');
$activeModule = 'newsletters';
$needsRichText = !$isSent;
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($isSent): ?>
  <div class="card admin-stat-card">
    <div class="card-body">
      <h4><?= e($newsletter['subject']) ?></h4>
      <p class="text-muted">
        Sent <?= e(format_date($newsletter['sent_at'], 'M j, Y g:i A')) ?>
        &middot; Sent to <?= $newsletter['target_group'] ? e($newsletter['target_group']) . ' group' : 'all confirmed subscribers' ?>
      </p>
      <hr>
      <div class="saa-post-body"><?= $newsletter['body_html'] ?></div>
    </div>
    <div class="card-footer">
      <a href="index.php" class="btn btn-outline-secondary">Back to Newsletters</a>
    </div>
  </div>
<?php else: ?>
  <form method="post" id="newsletterForm" class="card admin-stat-card">
    <div class="card-body">
      <?= csrf_field() ?>
      <?php if ($newsletter): ?><input type="hidden" name="id" value="<?= (int) $newsletter['id'] ?>"><?php endif; ?>
      <input type="hidden" name="submit_action" id="submitActionInput" value="draft">

      <div class="mb-3">
        <label class="form-label">Subject</label>
        <input type="text" name="subject" class="form-control" value="<?= e($newsletter['subject'] ?? '') ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Send To</label>
        <select name="target_group" class="form-select">
          <option value="">All confirmed subscribers</option>
          <?php foreach ($existingGroups as $g): ?>
            <option value="<?= e($g) ?>" <?= ($newsletter['target_group'] ?? '') === $g ? 'selected' : '' ?>><?= e($g) ?> group</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Body</label>
        <div id="quillEditor" style="height: 320px; background: #fff;"></div>
        <textarea name="body_html" id="bodyHtmlInput" class="d-none"></textarea>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
      <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-outline-secondary" data-action="draft">Save Draft</button>
        <button type="submit" class="btn saa-btn-accent" data-action="send">Send Now</button>
      </div>
    </div>
  </form>

  <script>
    const quill = new Quill('#quillEditor', { theme: 'snow' });
    <?php if ($newsletter): ?>
    quill.root.innerHTML = <?= json_encode($newsletter['body_html'] ?? '') ?>;
    <?php endif; ?>

    let clickedAction = 'draft';
    document.querySelectorAll('#newsletterForm button[data-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        clickedAction = btn.dataset.action;
      });
    });

    document.getElementById('newsletterForm').addEventListener('submit', function (e) {
      if (clickedAction === 'send') {
        var targetSelect = document.querySelector('select[name="target_group"]');
        var targetLabel = targetSelect.value ? 'the "' + targetSelect.value + '" group' : 'all confirmed subscribers';
        if (!window.confirm('Send this newsletter to ' + targetLabel + ' now?')) {
          e.preventDefault();
          return;
        }
      }
      document.getElementById('submitActionInput').value = clickedAction;
      document.getElementById('bodyHtmlInput').value = quill.root.innerHTML;
    });
  </script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
