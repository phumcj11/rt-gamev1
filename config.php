<?php
declare(strict_types=1);

define('APP_NAME', 'AR Lucky Elephant Hunt');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', __DIR__);
define('BASE_URL', '/rt-gamear1');

define('DB_HOST', 'localhost');
define('DB_NAME', 'ar_elephant_hunt');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('ITEMS_REQUIRED', 3);
define('SESSION_LIFETIME', 3600);

// Paste ngrok/cloudflare tunnel HTTPS base URL here for mobile testing, e.g. https://abc123.ngrok-free.app
define('PUBLIC_HTTPS_BASE', '');

date_default_timezone_set('Asia/Bangkok');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
