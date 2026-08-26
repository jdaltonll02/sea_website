<?php
/**
 * Shared helpers for the admin dashboard: RBAC module gating, file uploads,
 * and one-shot flash messages. Included by admin-header.php on every admin page.
 */

require_once __DIR__ . '/../../public/includes/helpers.php';
require_once __DIR__ . '/../../public/includes/auth.php';

/**
 * Every admin module a role's permissions can grant, with the human-readable
 * label used when building/editing a role's permission set.
 */
const AVAILABLE_MODULES = [
    'bishops' => 'Bishops',
    'archdeacons' => 'Archdeacons',
    'letters' => 'Letters & Documents',
    'clergy' => 'Clergy Registry',
    'churches' => 'Churches',
    'organizations' => 'Organizations',
    'blog' => 'Blog',
    'newsletters' => 'Newsletters',
    'events' => 'Events & Calendar',
    'media' => 'Media Library',
    'testimonials' => 'Testimonials',
    'pages-content' => 'Static Pages',
    'employees' => 'Employees',
    'users' => 'Users & Roles',
    'settings' => 'Settings',
    'activity-log' => 'Activity Log',
];

/**
 * A role's granted modules, decoded from roles.permissions_json.
 * Cached per request per role — roles.permissions_json is the single source
 * of truth for non-SuperAdmin access; SuperAdmin bypasses this entirely.
 */
function role_permissions(int $roleId): array
{
    static $cache = [];
    if (!array_key_exists($roleId, $cache)) {
        $stmt = db()->prepare('SELECT permissions_json FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);
        $json = $stmt->fetchColumn();
        $decoded = $json ? json_decode($json, true) : null;
        $cache[$roleId] = is_array($decoded) ? $decoded : [];
    }
    return $cache[$roleId];
}

function can_access_module(string $moduleKey): bool
{
    if (has_role('SuperAdmin')) {
        return true;
    }
    $user = current_user();
    if (!$user) {
        return false;
    }
    $permissions = role_permissions((int) $user['role_id']);
    return !empty($permissions[$moduleKey]);
}

function require_module_access(string $moduleKey): void
{
    require_login();
    if (!can_access_module($moduleKey)) {
        http_response_code(403);
        exit('You do not have permission to access this module.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Validate and move an uploaded file into /public/uploads/{$subdir}.
 * Returns the relative path to store in the DB, or null if no file was submitted.
 * Dies with a 400 on invalid type/size — callers should validate before any DB write.
 */
function handle_file_upload(string $fieldName, string $subdir, array $allowedExt = ['jpg', 'jpeg', 'png', 'webp'], int $maxBytes = 5 * 1024 * 1024): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        exit('Upload failed with error code ' . $file['error']);
    }

    if ($file['size'] > $maxBytes) {
        http_response_code(400);
        exit('File is too large. Maximum size is ' . round($maxBytes / 1024 / 1024, 1) . ' MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        http_response_code(400);
        exit('File type not allowed. Allowed types: ' . implode(', ', $allowedExt));
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'pdf' => 'application/pdf', 'mp4' => 'video/mp4',
    ];
    if (isset($allowedMimes[$ext]) && $mime !== $allowedMimes[$ext]) {
        http_response_code(400);
        exit('File content does not match its extension.');
    }

    $destDir = UPLOADS_PATH . '/' . trim($subdir, '/');
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
        http_response_code(500);
        exit('Failed to save uploaded file.');
    }

    return trim($subdir, '/') . '/' . $filename;
}
