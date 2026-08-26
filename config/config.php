<?php
/**
 * Application-wide configuration and bootstrap.
 * Included by every public and admin entry point before anything else runs.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/database.php';

define('APP_ENV', env('APP_ENV', 'production'));
define('APP_URL', rtrim(env('APP_URL', ''), '/'));
define('APP_KEY', env('APP_KEY', ''));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 7200));

define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/public/uploads');
define('UPLOADS_URL', APP_URL . '/uploads');

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

date_default_timezone_set('GMT');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => APP_ENV !== 'development',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
