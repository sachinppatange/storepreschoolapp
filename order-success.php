<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$number = strtoupper(trim((string) ($_GET['no'] ?? '')));
$order = valid_order_number($number) ? find_order_by_number($number) : null;
if (!$order || !can_view_order($order)) {
    flash('error', 'That order could not be opened.');
    redirect(url('track.php'));
}
$items = order_items((int) $order['id']);
$simpleHeader = true;
$hideDock = true;
$pageTitle = 'Order placed';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="panel" style="margin-top:16px">
        <p class="eyebrow">Thank you</p>
        <h1>Order <?= e($order['order_number']) ?> is <?= e(str_replace('_', ' ', $order['delivery_status'])) ?></h1>
        <p>We will deliver to <strong><?= e($order['school_name']) ?></strong>, <?= e($order['address']) ?>, <?= e($order['city']) ?> <?= e($order['pincode']) ?>.</p>
        <p>Payment: <?= status_badge((string) $order['payment_status']) ?> · <?= e($order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Razorpay') ?></p>
        <?php foreach ($items as $item): ?>
            <p><?= (int) $item['quantity'] ?> × <?= e($item['product_name']) ?> · <?= money($item['line_total']) ?></p>
        <?php endforeach; ?>
        <p class="price">Total <?= money($order['total']) ?></p>
        <div class="hero-actions">
            <a class="btn btn-green" href="<?= e(url('order.php?no=' . rawurlencode($order['order_number']))) ?>">Track order</a>
            <a class="btn btn-ghost" href="<?= e(url('invoice.php?no=' . rawurlencode($order['order_number']))) ?>">Invoice</a>
            <a class="btn btn-ghost" href="<?= e(url()) ?>">Continue shopping</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
