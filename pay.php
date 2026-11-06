<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$number = strtoupper(trim((string) ($_GET['no'] ?? $_POST['order_number'] ?? '')));
$order = valid_order_number($number) ? find_order_by_number($number) : null;
if (!$order || !can_view_order($order)) {
    flash('error', 'That order could not be opened.');
    redirect(url('track.php'));
}
if ($order['payment_status'] === 'paid' || $order['payment_method'] === 'cod') {
    redirect(url('order-success.php?no=' . rawurlencode($order['order_number'])));
}
if ($order['delivery_status'] === 'cancelled') {
    flash('error', 'This order was cancelled.');
    redirect(url('order.php?no=' . rawurlencode($order['order_number'])));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'demo') {
        if (razorpay_ready()) {
            $error = 'Demo payment is turned off because Razorpay keys are set.';
        } else {
            $paid = mark_order_paid($order, 'pay_demo_' . $order['order_number']);
            if ($paid['ok']) {
                redirect(url('order-success.php?no=' . rawurlencode($order['order_number'])));
            }
            $error = $paid['error'];
        }
    } elseif ($action === 'cancel') {
        $result = change_order_state($order, 'failed', 'cancelled', 'Payment cancelled by customer');
        if ($result['ok']) {
            flash('success', 'Order cancelled.');
            redirect(url('order.php?no=' . rawurlencode($order['order_number'])));
        }
        $error = $result['error'];
    }
}

if (razorpay_ready() && empty($order['razorpay_order_id'])) {
    $created = razorpay_create_for_order($order);
    if ($created['ok']) {
        $order = find_order_by_number($order['order_number']);
    } else {
        $error = $created['error'];
    }
}

$simpleHeader = true;
$hideDock = true;
$pageTitle = 'Pay ' . $order['order_number'];
require __DIR__ . '/includes/header.php';
?>
<div class="wrap page-narrow">
    <div class="panel pay-box">
        <p class="muted">Order <?= e($order['order_number']) ?> · <?= e(order_place_label($order['school_name'])) ?></p>
        <p class="amount"><?= money($order['total']) ?></p>
        <p>Pay online for <?= e($order['customer_name']) ?>.</p>
        <?php if ($error): ?><div class="alert-bad"><?= e($error) ?></div><?php endif; ?>
        <?php if (!empty($order['razorpay_error'])): ?><div class="alert-bad"><?= e($order['razorpay_error']) ?></div><?php endif; ?>

        <?php if (razorpay_ready() && !empty($order['razorpay_order_id'])): ?>
            <button class="btn btn-green btn-block" id="rzp-button" type="button">Pay <?= money($order['total']) ?> with Razorpay</button>
            <p class="small muted">UPI, cards and netbanking. The payment is verified on the server before the order is confirmed.</p>
        <?php else: ?>
            <div class="alert-warn">Razorpay keys are not in <strong>config/config.php</strong> yet. Use demo payment to finish this order, or add your test keys and pay for real.</div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
                <button class="btn btn-green btn-block" name="action" value="demo" type="submit">Complete demo payment</button>
            </form>
        <?php endif; ?>
        <form method="post" style="margin-top:10px">
            <?= csrf_field() ?>
            <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
            <button class="btn btn-ghost btn-block" name="action" value="cancel" type="submit">Cancel order</button>
        </form>
    </div>
</div>
<?php if (razorpay_ready() && !empty($order['razorpay_order_id'])): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('rzp-button').addEventListener('click', function () {
    var options = {
        key: <?= json_encode(RAZORPAY_KEY_ID) ?>,
        amount: <?= (int) round(((float) $order['total']) * 100) ?>,
        currency: 'INR',
        name: <?= json_encode(APP_NAME) ?>,
        description: <?= json_encode('Order ' . $order['order_number']) ?>,
        order_id: <?= json_encode($order['razorpay_order_id']) ?>,
        prefill: {
            name: <?= json_encode($order['customer_name']) ?>,
            email: <?= json_encode($order['email']) ?>,
            contact: <?= json_encode($order['mobile']) ?>
        },
        theme: { color: '#0c831f' },
        handler: function (response) {
            var data = new FormData();
            data.set('csrf', window.STORE.csrf);
            data.set('action', 'verify');
            data.set('order_number', <?= json_encode($order['order_number']) ?>);
            data.set('razorpay_order_id', response.razorpay_order_id);
            data.set('razorpay_payment_id', response.razorpay_payment_id);
            data.set('razorpay_signature', response.razorpay_signature);
            fetch(window.STORE.base + 'api/payment.php', {
                method: 'POST',
                body: data,
                headers: { 'X-CSRF-Token': window.STORE.csrf, 'Accept': 'application/json' }
            }).then(function (res) { return res.json(); }).then(function (json) {
                if (json.ok) window.location = json.redirect;
                else alert(json.error || 'Payment could not be verified.');
            }).catch(function () { alert('Payment verification failed. If money was debited, check the order with your order number.'); });
        }
    };
    new Razorpay(options).open();
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
