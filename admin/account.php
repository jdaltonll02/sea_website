<?php
require_once __DIR__ . '/includes/admin-functions.php';

require_login();
$pdo = db();

$userId = (int) current_user()['id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$account = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $emailChanged = $email !== $account['email'];
    $wantsPasswordChange = $newPassword !== '' || $confirmPassword !== '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (($emailChanged || $wantsPasswordChange) && !password_verify($currentPassword, $account['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }

    if ($wantsPasswordChange) {
        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        }
    }

    if (empty($errors) && $emailChanged) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $check->execute(['email' => $email, 'id' => $userId]);
        if ($check->fetch()) {
            $errors[] = 'That email address is already in use by another account.';
        }
    }

    if (empty($errors)) {
        $sql = 'UPDATE users SET name = :name, email = :email';
        $bind = ['name' => $name, 'email' => $email, 'id' => $userId];
        if ($wantsPasswordChange) {
            $sql .= ', password_hash = :password_hash';
            $bind['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }
        $sql .= ' WHERE id = :id';
        $pdo->prepare($sql)->execute($bind);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;

        log_activity('update', 'user', $userId);
        flash('success', $wantsPasswordChange ? 'Account updated and password changed.' : 'Account updated.');
        redirect('/admin/account.php');
    }
}

$pageTitle = 'My Account';
$hideQuickAdd = true;
include __DIR__ . '/includes/admin-header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="card admin-stat-card" style="max-width: 560px;">
  <div class="card-body">
    <?= csrf_field() ?>

    <p class="text-muted small">Role: <strong><?= e(current_user()['role_name'] ?? '') ?></strong></p>

    <div class="mb-3">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" value="<?= e($account['name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email (this is your login username)</label>
      <input type="email" name="email" class="form-control" value="<?= e($account['email']) ?>" required>
    </div>

    <hr>
    <p class="text-muted small mb-2">Changing your email or password requires your current password.</p>
    <div class="mb-3">
      <label class="form-label">Current Password</label>
      <input type="password" name="current_password" class="form-control" autocomplete="current-password">
    </div>
    <div class="mb-3">
      <label class="form-label">New Password (leave blank to keep current)</label>
      <input type="password" name="new_password" class="form-control" autocomplete="new-password">
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm New Password</label>
      <input type="password" name="confirm_password" class="form-control" autocomplete="new-password">
    </div>
  </div>
  <div class="card-footer d-flex justify-content-end">
    <button type="submit" class="btn saa-btn-accent">Save Changes</button>
  </div>
</form>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
