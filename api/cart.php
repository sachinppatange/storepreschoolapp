<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
require_csrf();
$result = cart_apply($_POST);
json_out($result, $result['ok'] ? 200 : 422);
