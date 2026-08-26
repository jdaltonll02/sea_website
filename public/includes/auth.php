<?php
/**
 * Session-based authentication and role checks.
 * Shared by public pages (e.g. gating clergy-only documents) and the admin dashboard.
 */

require_once __DIR__ . '/helpers.php';

const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string ...$roleNames): bool
{
    $user = current_user();
    return $user !== null && in_array($user['role_name'], $roleNames, true);
}

/** Attempt to log a user in. Returns true on success, false on invalid credentials or lockout. */
function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT users.*, roles.name AS role_name
         FROM users
         JOIN roles ON roles.id = users.role_id
         WHERE users.email = :email AND users.is_active = 1
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = $user['failed_login_attempts'] + 1;
        $lockUntil = $attempts >= MAX_LOGIN_ATTEMPTS
            ? date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_MINUTES . ' minutes'))
            : null;

        $upd = db()->prepare('UPDATE users SET failed_login_attempts = :attempts, locked_until = :lock WHERE id = :id');
        $upd->execute(['attempts' => $attempts, 'lock' => $lockUntil, 'id' => $user['id']]);
        return false;
    }

    $reset = db()->prepare(
        'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id'
    );
    $reset->execute(['id' => $user['id']]);

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role_id' => $user['role_id'],
        'role_name' => $user['role_name'],
        'avatar' => $user['avatar'],
    ];

    log_activity('login', 'user', (int) $user['id']);

    return true;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('/admin/login.php');
    }
}

function require_role(string ...$roleNames): void
{
    require_login();
    if (!has_role(...$roleNames) && !has_role('SuperAdmin')) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function log_activity(string $action, ?string $targetType = null, ?int $targetId = null): void
{
    $user = current_user();
    $stmt = db()->prepare(
        'INSERT INTO activity_log (user_id, action, target_type, target_id) VALUES (:user_id, :action, :target_type, :target_id)'
    );
    $stmt->execute([
        'user_id' => $user['id'] ?? null,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
    ]);
}
