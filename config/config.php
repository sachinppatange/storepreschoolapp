<?php
declare(strict_types=1);

define('APP_NAME', 'Store Preschool');
define('APP_DOMAIN', 'Store.PreschoolApp.in');
define('APP_TAGLINE', 'Preschool essentials, delivered to your school');

// No trailing slash. Use '' if the site is served from the domain root.
define('BASE_URL', '/storepreschoolapp');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'storepreschool');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Razorpay test or live keys from https://dashboard.razorpay.com/app/keys
// Leave both blank to use the built-in demo payment on the pay page.
define('RAZORPAY_KEY_ID', '');
define('RAZORPAY_KEY_SECRET', '');

define('DELIVERY_FEE', 40);
define('FREE_DELIVERY_ABOVE', 499);

define('DEMO_MODE', true);
define('APP_DEBUG', true);

date_default_timezone_set('Asia/Kolkata');
