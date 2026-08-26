<?php
/**
 * Shared helper functions used across public pages and admin modules.
 */

require_once __DIR__ . '/../../config/config.php';

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    $path = ltrim($path, '/');
    $url = base_url('assets/' . $path);

    $filePath = BASE_PATH . '/public/assets/' . $path;
    if (is_file($filePath)) {
        $url .= '?v=' . filemtime($filePath);
    }

    return $url;
}

function upload_url(?string $path): string
{
    if (!$path) {
        return asset_url('images/placeholder.jpg');
    }
    return UPLOADS_URL . '/' . ltrim($path, '/');
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    return $text ?: 'n-a';
}

const CHURCH_STATUS_LABELS = [
    'preaching_station' => 'Preaching Station',
    'mission' => 'Mission',
    'aided_parish' => 'Aided Parish',
    'parish' => 'Parish',
    'pro_cathedral' => 'Pro-Cathedral',
];

function church_status_label(?string $status): string
{
    return CHURCH_STATUS_LABELS[$status] ?? 'Parish';
}

function format_date(?string $datetime, string $format = 'F j, Y'): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '';
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

/** Fetch a single value from the settings table, with a fallback default. */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = db()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        http_response_code(403);
        exit('Invalid or expired form submission. Please go back and try again.');
    }
}
