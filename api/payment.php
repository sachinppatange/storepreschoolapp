<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
require_csrf();

$number = strtoupper(trim((string) ($_POST['order_number'] ?? '')));
$order = valid_order_number($number) ? find_order_by_number($number) : null;
if (!$order || !can_view_order($order)) {
    json_out(['ok' => false, 'error' => 'Order not found.'], 404);
}
if ($order['payment_status'] === 'paid') {
    json_out(['ok' => true, 'redirect' => url('order-success.php?no=' . rawurlencode($order['order_number']))]);
}

$action = (string) ($_POST['action'] ?? '');
if ($action === 'verify') {
    $result = verify_razorpay_payment(
        $order,
        (string) ($_POST['razorpay_order_id'] ?? ''),
        (string) ($_POST['razorpay_payment_id'] ?? ''),
        (string) ($_POST['razorpay_signature'] ?? '')
    );
    if (!$result['ok']) {
        json_out($result, 422);
    }
    json_out(['ok' => true, 'redirect' => url('order-success.php?no=' . rawurlencode($order['order_number']))]);
}

json_out(['ok' => false, 'error' => 'Unknown payment action.'], 400);
