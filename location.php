<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url());
}

require_csrf();
$result = set_delivery_pin((string) ($_POST['pincode'] ?? ''));
flash($result['ok'] ? 'success' : 'error', $result['ok'] ? $result['message'] : $result['error']);
redirect(safe_return((string) ($_POST['return'] ?? ''), url()));
