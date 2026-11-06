<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$number = strtoupper(trim((string) ($_GET['no'] ?? $_POST['order_number'] ?? '')));
$order = valid_order_number($number) ? find_order_by_number($number) : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'track') {
        $mobile = normalize_phone((string) ($_POST['mobile'] ?? ''));
        if ($order && hash_equals((string) $order['mobile'], $mobile)) {
            remember_order_access($order['order_number']);
            redirect(url('order.php?no=' . rawurlencode($order['order_number'])));
        }
        $error = 'Order number and mobile number do not match.';
    } elseif ($action === 'cancel' && $order && can_view_order($order)) {
        if (!in_array($order['delivery_status'], ['placed', 'confirmed'], true)) {
            $error = 'This order is already being packed or delivered, so it can no longer be cancelled here.';
        } else {
            $payment = $order['payment_status'] === 'paid' ? 'paid' : 'failed';
            $result = change_order_state($order, $payment, 'cancelled', 'Cancelled by customer');
            if ($result['ok']) {
                flash('success', 'Order cancelled.');
                redirect(url('order.php?no=' . rawurlencode($order['order_number'])));
            }
            $error = $result['error'];
        }
    }
}

$pageTitle = $order ? 'Order ' . $order['order_number'] : 'Track order';
$simpleHeader = false;
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <?php if (!$order): ?>
        <div class="page-head"><h1>Order not found</h1></div>
        <div class="empty"><p>Check the order number, or <a href="<?= e(url('track.php')) ?>">track with your mobile number</a>.</p></div>
    <?php elseif (!can_view_order($order)): ?>
        <div class="page-head"><h1>Confirm it is your order</h1></div>
        <?php if ($error): ?><div class="alert-bad"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="panel" style="max-width:480px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="track">
            <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
            <p>Enter the mobile number used at checkout for <?= e($order['order_number']) ?>.</p>
            <label class="field">Mobile number<input name="mobile" inputmode="numeric" required></label>
            <button class="btn btn-green" type="submit">View order</button>
        </form>
    <?php else: ?>
        <?php $items = order_items((int) $order['id']); ?>
        <div class="page-head">
            <h1><?= e($order['order_number']) ?></h1>
            <p><?= status_badge((string) $order['delivery_status']) ?> <?= status_badge((string) $order['payment_status']) ?> <?= $order['order_type'] === 'bulk' ? status_badge('bulk') : '' ?></p>
        </div>
        <?php if ($error): ?><div class="alert-bad"><?= e($error) ?></div><?php endif; ?>
        <div class="layout">
            <div class="panel">
                <h2>Tracking</h2>
                <?php render_timeline($order, order_logs((int) $order['id'])); ?>
                <h2>Items</h2>
                <?php foreach ($items as $item): ?>
                    <p><?= (int) $item['quantity'] ?> × <?= e($item['product_name']) ?> · <?= money($item['line_total']) ?></p>
                <?php endforeach; ?>
                <?php foreach (print_jobs_for_order((int) $order['id']) as $job): ?>
                    <p><a href="<?= e(url('print-file.php?job=' . (int) $job['id'])) ?>">Download <?= e($job['original_name']) ?></a></p>
                <?php endforeach; ?>
                <?php if ((float) $order['referral_spent'] > 0): ?>
                    <p>Referral balance used <?= money($order['referral_spent']) ?><?= (int) $order['referral_void'] ? ' · returned' : '' ?></p>
                <?php endif; ?>
                <p><strong>Total <?= money($order['total']) ?></strong></p>
            </div>
            <aside class="bill">
                <h2>Delivery</h2>
                <?php $servingStore = dark_store_by_id((int) ($order['dark_store_id'] ?? 0)); ?>
                <?php if ($servingStore): ?><p><strong><?= e($servingStore['name']) ?></strong> is packing and sending this order.</p><?php endif; ?>
                <p><?= e($order['customer_name']) ?><br><?= e(order_place_label($order['school_name'])) ?><br><?= e($order['address']) ?><br><?= e($order['city']) ?> <?= e($order['pincode']) ?><br><?= e($order['mobile']) ?></p>
                <?php if ($order['payment_method'] === 'razorpay' && $order['payment_status'] === 'pending' && $order['delivery_status'] !== 'cancelled'): ?>
                    <a class="btn btn-green btn-block" href="<?= e(url('pay.php?no=' . rawurlencode($order['order_number']))) ?>">Pay now</a>
                <?php endif; ?>
                <a class="btn btn-ghost btn-block" href="<?= e(url('invoice.php?no=' . rawurlencode($order['order_number']))) ?>">View invoice</a>
                <?php if (in_array($order['delivery_status'], ['placed', 'confirmed'], true)): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
                        <button class="btn btn-ghost btn-block" type="submit">Cancel order</button>
                    </form>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
