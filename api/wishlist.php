<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
require_csrf();
if (!current_user()) {
    $next = safe_return((string) ($_POST['return'] ?? ''), url('wishlist.php'));
    json_out(['ok' => false, 'login' => url('login.php?next=' . urlencode($next)), 'error' => 'Please login to save items.'], 401);
}
$result = wishlist_toggle((int) ($_POST['product_id'] ?? 0));
json_out($result, $result['ok'] ? 200 : 422);
