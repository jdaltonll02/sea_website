<?php
require_once __DIR__ . '/../public/includes/helpers.php';
require_once __DIR__ . '/../public/includes/auth.php';

if (is_logged_in()) {
    redirect('/admin/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (attempt_login($email, $password)) {
            redirect('/admin/index.php');
        }
        $error = 'Invalid email or password, or your account is temporarily locked after repeated failed attempts.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Southeastern Archdeaconry</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset_url('css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/admin.css')) ?>">
</head>
<body class="admin-login-body">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="card saa-card mt-5">
          <div class="card-body p-4">
            <h4 class="text-center mb-1 font-heading">Southeastern Archdeaconry</h4>
            <p class="text-center text-muted mb-4">Admin Dashboard</p>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <button type="submit" class="btn saa-btn-accent w-100">Log In</button>
            </form>
          </div>
        </div>
        <p class="text-center mt-3"><a href="<?= e(base_url('index.php')) ?>">&larr; Back to site</a></p>
      </div>
    </div>
  </div>
</body>
</html>
