<?php
require_once __DIR__ . '/../../includes/admin-functions.php';

require_module_access('events');
$pdo = db();

$eventTypes = ['feast', 'ordination', 'synod', 'confirmation', 'other'];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$event = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $event = $stmt->fetch();
    if (!$event) {
        flash('error', 'Event not found.');
        redirect('/admin/modules/events/index.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $startDatetime = trim($_POST['start_datetime'] ?? '');
    $endDatetime = trim($_POST['end_datetime'] ?? '') ?: null;
    $eventType = in_array($_POST['event_type'] ?? '', $eventTypes, true) ? $_POST['event_type'] : 'other';

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if ($startDatetime === '') {
        $errors[] = 'Start date/time is required.';
    }

    if (empty($errors)) {
        $startDatetime = str_replace('T', ' ', $startDatetime);
        if ($endDatetime) {
            $endDatetime = str_replace('T', ' ', $endDatetime);
        }

        $imagePath = handle_file_upload('image', 'events', ['jpg', 'jpeg', 'png', 'webp']);

        if ($event) {
            $sql = 'UPDATE events SET title = :title, description = :description, location = :location,
                    start_datetime = :start_datetime, end_datetime = :end_datetime, event_type = :event_type';
            if ($imagePath) {
                $sql .= ', image = :image';
            }
            $sql .= ' WHERE id = :id';
        } else {
            $sql = 'INSERT INTO events (title, description, location, start_datetime, end_datetime, event_type'
                . ($imagePath ? ', image' : '') . ')
                    VALUES (:title, :description, :location, :start_datetime, :end_datetime, :event_type'
                . ($imagePath ? ', :image' : '') . ')';
        }

        $stmt = $pdo->prepare($sql);
        $bind = [
            'title' => $title, 'description' => $description, 'location' => $location,
            'start_datetime' => $startDatetime, 'end_datetime' => $endDatetime, 'event_type' => $eventType,
        ];
        if ($event) {
            $bind['id'] = $id;
        }
        if ($imagePath) {
            $bind['image'] = $imagePath;
        }
        $stmt->execute($bind);

        $newId = $event ? $id : (int) $pdo->lastInsertId();
        log_activity($event ? 'update' : 'create', 'event', $newId);
        flash('success', 'Event saved successfully.');
        redirect('/admin/modules/events/index.php');
    }
}

$pageTitle = $event ? 'Edit Event' : 'Add Event';
$activeModule = 'events';
$hideQuickAdd = true;
include __DIR__ . '/../../includes/admin-header.php';
?>

<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link active" href="index.php">Events</a></li>
  <li class="nav-item"><a class="nav-link" href="lectionary.php">Lectionary Days</a></li>
</ul>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card admin-stat-card">
  <div class="card-body">
    <?= csrf_field() ?>
    <?php if ($event): ?><input type="hidden" name="id" value="<?= (int) $event['id'] ?>"><?php endif; ?>

    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="<?= e($event['title'] ?? '') ?>" required>
      </div>

      <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4"><?= e($event['description'] ?? '') ?></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label">Location</label>
        <input type="text" name="location" class="form-control" value="<?= e($event['location'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Event Type</label>
        <select name="event_type" class="form-select">
          <?php foreach ($eventTypes as $type): ?>
            <option value="<?= e($type) ?>" <?= ($event['event_type'] ?? 'other') === $type ? 'selected' : '' ?>><?= e(ucfirst($type)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Starts</label>
        <input type="datetime-local" name="start_datetime" class="form-control"
               value="<?= e(!empty($event['start_datetime']) ? str_replace(' ', 'T', substr($event['start_datetime'], 0, 16)) : '') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Ends (optional)</label>
        <input type="datetime-local" name="end_datetime" class="form-control"
               value="<?= e(!empty($event['end_datetime']) ? str_replace(' ', 'T', substr($event['end_datetime'], 0, 16)) : '') ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Image</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        <?php if (!empty($event['image'])): ?>
          <img src="<?= e(upload_url($event['image'])) ?>" class="img-thumbnail mt-2" style="max-height:100px;">
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn saa-btn-accent">Save Event</button>
  </div>
</form>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>
