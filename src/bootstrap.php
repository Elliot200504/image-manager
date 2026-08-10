<?php

declare(strict_types=1);

/**
 * Wires the application together. Every entry point in public/ starts with:
 *
 *     require_once __DIR__ . '/../src/bootstrap.php';
 *
 * There is no autoloader and no package manager, which is deliberate — the
 * project runs on a stock PHP image with nothing to install.
 */

const APP_NAME = 'FileShare';

define('BASE_PATH',   dirname(__DIR__));
define('DATA_PATH',   BASE_PATH . '/data');
define('UPLOAD_PATH', BASE_PATH . '/storage/uploads');

/** Upload ceiling. PHP's own limits still apply and are set in docker/php.ini. */
define('MAX_UPLOAD_BYTES', (int) (getenv('MAX_UPLOAD_MB') ?: 50) * 1024 * 1024);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/FileTypes.php';
require_once __DIR__ . '/Files.php';
require_once __DIR__ . '/Users.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Uploader.php';

/**
 * Session cookie settings.
 *
 * HttpOnly keeps the cookie away from JavaScript, SameSite=Lax blocks it on
 * cross-site POSTs (defence in depth behind the CSRF token), and Secure is set
 * whenever the request arrived over HTTPS — hardcoding it would break plain-HTTP
 * local runs, so it follows the request.
 */
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);

    session_name('fileshare_session');
    session_start();
}

$storage  = new Storage(DATA_PATH . '/files.json');
$users    = new Users(new Storage(DATA_PATH . '/users.json'));
$files    = new Files($storage, UPLOAD_PATH);
$auth     = new Auth($users);
$uploader = new Uploader($files, UPLOAD_PATH, MAX_UPLOAD_BYTES);
