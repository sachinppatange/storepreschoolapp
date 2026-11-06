<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$number = strtoupper(trim((string) ($_GET['no'] ?? '')));
$order = valid_order_number($number) ? find_order_by_number($number) : null;
if (!$order || !can_view_order($order)) {
    flash('error', 'Open the order with your mobile number before viewing the invoice.');
    redirect(url('track.php'));
}
$items = order_items((int) $order['id']);
$pageTitle = 'Invoice ' . $order['order_number'];
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="invoice" style="margin-top:16px">
        <div class="order-top">
            <div>
                <strong><?= e(APP_NAME) ?></strong>
                <p class="muted"><?= e(APP_DOMAIN) ?></p>
            </div>
            <div>
                <h1 style="font-size:1.3rem">Invoice <?= e($order['order_number']) ?></h1>
                <p class="muted"><?= e(when($order['created_at'])) ?></p>
            </div>
        </div>
        <p><strong><?= e($order['customer_name']) ?></strong><br><?= e(order_place_label($order['school_name'])) ?><br><?= e($order['address']) ?><br><?= e($order['city']) ?> <?= e($order['pincode']) ?><br><?= e($order['mobile']) ?> · <?= e($order['email']) ?></p>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Amount</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= money($item['unit_price']) ?></td>
                        <td><?= money($item['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p>Subtotal <?= money($order['subtotal']) ?><br>
            Discount <?= money($order['discount']) ?><?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?><br>
            Delivery <?= money($order['delivery_fee']) ?><br>
            <?php if ((float) $order['referral_spent'] > 0): ?>Referral balance −<?= money($order['referral_spent']) ?><br><?php endif; ?>
            <strong>Total <?= money($order['total']) ?></strong></p>
        <p>Payment <?= e($order['payment_method']) ?> · <?= e($order['payment_status']) ?></p>
        <button class="btn btn-green no-print" type="button" onclick="window.print()">Print invoice</button>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
