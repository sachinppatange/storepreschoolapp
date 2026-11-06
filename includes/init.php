<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/commerce.php';
require_once __DIR__ . '/fulfillment.php';
require_once __DIR__ . '/print.php';
require_once __DIR__ . '/ui.php';

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

ini_set('session.use_strict_mode', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SPSTORESESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

set_exception_handler(static function (Throwable $e): void {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $message = APP_DEBUG
        ? $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
        : 'Something went wrong. Please try again.';
    echo '<!doctype html><html><body style="font-family:sans-serif;padding:32px"><h1>Store Preschool</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    exit;
});

try {
    db()->query('SELECT 1 FROM users LIMIT 1');
    ensure_fulfillment_schema();
    ensure_print_schema();
    remember_delivery_from_user(current_user());
} catch (Throwable $e) {
    render_setup_screen($e->getMessage());
}
