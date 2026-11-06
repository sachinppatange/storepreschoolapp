<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $number = strtoupper(trim((string) ($_POST['order_number'] ?? '')));
    $mobile = normalize_phone((string) ($_POST['mobile'] ?? ''));
    $order = valid_order_number($number) ? find_order_by_number($number) : null;
    if ($order && hash_equals((string) $order['mobile'], $mobile)) {
        remember_order_access($order['order_number']);
        redirect(url('order.php?no=' . rawurlencode($order['order_number'])));
    }
    $error = 'Order number and mobile number do not match.';
    remember_old($_POST, ['order_number']);
}

$pageTitle = 'Track order';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap" style="max-width:520px;padding-bottom:28px">
    <div class="page-head"><h1>Track an order</h1></div>
    <?php if ($error): ?><div class="alert-bad"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <label class="field">Order number<input name="order_number" required value="<?= old('order_number') ?>" placeholder="SP..."></label>
        <label class="field">Mobile number used at checkout<input name="mobile" inputmode="numeric" required></label>
        <button class="btn btn-green" type="submit">Track</button>
    </form>
    <?php if (current_user()): ?>
        <p><a href="<?= e(url('orders.php')) ?>">See orders on your account</a></p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
