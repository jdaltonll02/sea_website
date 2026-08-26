<?php
/**
 * Shared helpers for the admin dashboard: RBAC module gating, file uploads,
 * and one-shot flash messages. Included by admin-header.php on every admin page.
 */

require_once __DIR__ . '/../../public/includes/helpers.php';
require_once __DIR__ . '/../../public/includes/auth.php';

/**
 * Which non-SuperAdmin roles may access each admin module.
 * SuperAdmin always has access to everything (checked separately in has_role()).
 */
const MODULE_ROLES = [
    'bishops' => ['Bishop\'s Office'],
    'archdeacons' => ['Bishop\'s Office'],
    'letters' => ['Bishop\'s Office'],
    'clergy' => ['Registrar'],
    'churches' => ['Registrar'],
    'organizations' => ['Registrar'],
    'blog' => ['Communications', 'Editor'],
    'newsletters' => ['Communications'],
    'events' => ['Communications'],
    'media' => ['Communications'],
    'testimonials' => ['Communications'],
    'pages-content' => ['Editor'],
    'employees' => [],
    'users' => [],
    'settings' => [],
    'activity-log' => [],
];

function can_access_module(string $moduleKey): bool
{
    if (has_role('SuperAdmin')) {
        return true;
    }
    $allowed = MODULE_ROLES[$moduleKey] ?? [];
    return has_role(...$allowed);
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
