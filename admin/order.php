<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

$number = strtoupper(trim((string) ($_GET['no'] ?? $_POST['order_number'] ?? '')));
$order = valid_order_number($number) ? find_order_by_number($number) : null;
if (!$order) {
    flash('error', 'Order not found.');
    redirect(url('admin/orders.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $payment = (string) ($_POST['payment_status'] ?? $order['payment_status']);
    $delivery = (string) ($_POST['delivery_status'] ?? $order['delivery_status']);
    $note = trim((string) ($_POST['note'] ?? ''));
    $school = trim((string) ($_POST['school_name'] ?? ''));
    $mobile = normalize_phone((string) ($_POST['mobile'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $pin = preg_replace('/\D/', '', (string) ($_POST['pincode'] ?? '')) ?? '';
    if (mb_strlen($school) < 2 || !valid_phone($mobile) || mb_strlen($address) < 10 || !preg_match('/^\d{6}$/', $pin)) {
        flash('error', 'Check the school, mobile, address and PIN code.');
        redirect(url('admin/order.php?no=' . rawurlencode($order['order_number'])));
    }
    db()->prepare('UPDATE orders SET school_name=?, mobile=?, address=?, city=?, pincode=? WHERE id=?')
        ->execute([$school, $mobile, $address, $city, $pin, (int) $order['id']]);
    $fresh = find_order_by_id((int) $order['id']);
    $result = change_order_state($fresh, $payment, $delivery, $note);
    flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Order updated.' : $result['error']);
    redirect(url('admin/order.php?no=' . rawurlencode($order['order_number'])));
}

$items = order_items((int) $order['id']);
$logs = order_logs((int) $order['id']);
$pageTitle = $order['order_number'];
$active = 'orders';
require __DIR__ . '/includes/header.php';
?>
<div class="layout" style="display:grid;gap:12px;grid-template-columns:1.2fr .8fr">
    <div class="panel">
        <h2>Items</h2>
        <?php foreach ($items as $item): ?>
            <p><?= (int) $item['quantity'] ?> × <?= e($item['product_name']) ?> · <?= money($item['line_total']) ?></p>
        <?php endforeach; ?>
        <p>Subtotal <?= money($order['subtotal']) ?> · Discount <?= money($order['discount']) ?> <?= e((string) $order['coupon_code']) ?><br>
            Delivery <?= money($order['delivery_fee']) ?> · <strong>Total <?= money($order['total']) ?></strong></p>
        <p>Stock <?= (int) $order['stock_deducted'] ? 'reserved' : 'not reserved' ?> · <?= e($order['order_type']) ?> · <?= e($order['payment_method']) ?></p>
        <?php if ($order['razorpay_order_id']): ?><p>Razorpay order <?= e($order['razorpay_order_id']) ?><br>Payment <?= e((string) $order['razorpay_payment_id']) ?></p><?php endif; ?>
        <h2>Timeline</h2>
        <ul><?php foreach ($logs as $log): ?><li><?= e(when($log['created_at'])) ?> · <?= e($log['status']) ?> · <?= e($log['note']) ?></li><?php endforeach; ?></ul>
    </div>
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
        <h2>Payment and delivery</h2>
        <label class="field">Payment
            <select name="payment_status"><?php foreach (payment_statuses() as $key => $label): ?><option value="<?= e($key) ?>"<?= $order['payment_status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field">Delivery
            <select name="delivery_status"><?php foreach (delivery_statuses() as $key => $label): ?><option value="<?= e($key) ?>"<?= $order['delivery_status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field">Note for the timeline<input name="note" maxlength="255"></label>
        <label class="field">School<input name="school_name" required value="<?= e($order['school_name']) ?>"></label>
        <label class="field">Mobile<input name="mobile" required value="<?= e($order['mobile']) ?>"></label>
        <label class="field">Address<textarea name="address" rows="3" required><?= e($order['address']) ?></textarea></label>
        <label class="field">City<input name="city" required value="<?= e($order['city']) ?>"></label>
        <label class="field">PIN code<input name="pincode" required value="<?= e($order['pincode']) ?>"></label>
        <p><?= e($order['customer_name']) ?> · <?= e($order['email']) ?></p>
        <?php if ($order['notes']): ?><p><?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
        <button class="btn" type="submit">Save order</button>
        <a class="btn alt" href="<?= e(url('invoice.php?no=' . rawurlencode($order['order_number']))) ?>">Invoice</a>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
