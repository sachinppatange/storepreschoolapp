<?php
declare(strict_types=1);

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Store Preschool');
}
if (!defined('APP_DOMAIN')) {
    define('APP_DOMAIN', 'Store.PreschoolApp.in');
}
if (!defined('APP_TAGLINE')) {
    define('APP_TAGLINE', 'Preschool essentials, delivered to your school');
}

// No trailing slash. Empty when the site is the domain root.
// /storepreschoolapp on XAMPP, '' on https://store.preschoolapp.in/
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseUrl = '';
    if (preg_match('#^(.*?)/storepreschoolapp(?:/|$)#', $scriptName, $baseMatch)) {
        $baseUrl = rtrim($baseMatch[1], '/') . '/storepreschoolapp';
    }
    define('BASE_URL', $baseUrl);
}

if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'storepreschool');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Razorpay test or live keys from https://dashboard.razorpay.com/app/keys
// Leave both blank to use the built-in demo payment on the pay page.
define('RAZORPAY_KEY_ID', '');
define('RAZORPAY_KEY_SECRET', '');

define('DELIVERY_FEE', 40);
define('FREE_DELIVERY_ABOVE', 499);

if (!defined('DEMO_MODE')) {
    define('DEMO_MODE', true);
}
if (!defined('APP_DEBUG')) {
    $httpHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $isLiveHost = $httpHost === 'store.preschoolapp.in' || str_starts_with($httpHost, 'store.preschoolapp.in:');
    define('APP_DEBUG', !$isLiveHost);
}

date_default_timezone_set('Asia/Kolkata');
