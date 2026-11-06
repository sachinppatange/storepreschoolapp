<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$summary = cart_summary();
if (!$summary['items']) {
    flash('error', 'Your cart is empty.');
    redirect(url('cart.php'));
}

$user = current_user();
$errors = [];
$oldKeys = ['name', 'email', 'school_name', 'mobile', 'address', 'city', 'pincode', 'notes', 'coupon', 'payment_method'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? 'place');
    if ($action === 'coupon') {
        $result = set_coupon((string) ($_POST['coupon'] ?? ''));
        remember_old($_POST, $oldKeys);
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? $result['message'] : $result['error']);
        redirect(url('checkout.php'));
    }
    if ($action === 'remove_coupon') {
        unset($_SESSION['coupon']);
        remember_old($_POST, $oldKeys);
        flash('success', 'Coupon removed.');
        redirect(url('checkout.php'));
    }

    [$errors, $data] = validate_customer_fields($_POST);
    $method = (string) ($_POST['payment_method'] ?? '');
    if (!in_array($method, ['razorpay', 'cod'], true)) {
        $errors[] = 'Choose a payment method.';
    }
    $summary = cart_summary(true);
    if (!$summary['items']) {
        $errors[] = 'Your cart is empty.';
    }
    foreach ($summary['items'] as $item) {
        if ((int) $item['quantity'] > (int) $item['stock']) {
            $errors[] = $item['name'] . ' has only ' . (int) $item['stock'] . ' left.';
        }
    }
    if (!$errors) {
        $lines = [];
        foreach ($summary['items'] as $item) {
            $lines[] = ['id' => (int) $item['id'], 'qty' => (int) $item['quantity']];
        }
        $coupon = $summary['coupon']['code'] ?? null;
        $result = place_order([
            'user_id' => $user ? (int) $user['id'] : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'school_name' => $data['school_name'],
            'mobile' => $data['mobile'],
            'address' => $data['address'],
            'city' => $data['city'],
            'pincode' => $data['pincode'],
            'notes' => $data['notes'],
        ], $lines, $method, 'retail', $coupon);
        if ($result['ok']) {
            cart_clear();
            unset($_SESSION['coupon']);
            remember_order_access($result['order']['order_number']);
            if ($user && !empty($_POST['save_address'])) {
                db()->prepare('UPDATE users SET school_name = ?, phone = ?, address = ?, city = ?, pincode = ? WHERE id = ?')
                    ->execute([$data['school_name'], $data['mobile'], $data['address'], $data['city'], $data['pincode'], (int) $user['id']]);
            }
            clear_old();
            if ($method === 'razorpay') {
                redirect(url('pay.php?no=' . rawurlencode($result['order']['order_number'])));
            }
            redirect(url('order-success.php?no=' . rawurlencode($result['order']['order_number'])));
        }
        $errors[] = $result['error'];
    }
    remember_old($_POST, $oldKeys);
}

$simpleHeader = true;
$hideDock = true;
$pageTitle = 'Checkout';
$summary = cart_summary();
$coupons = active_coupons();
$value = static function (string $key, string $fallback = '') use ($user): string {
    if (isset($_SESSION['old'][$key])) {
        return (string) $_SESSION['old'][$key];
    }
    return $fallback;
};

require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head"><h1>Checkout</h1></div>
    <?php if ($errors): ?><ul class="errors"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <form method="post" class="layout">
        <?= csrf_field() ?>
        <div class="panel">
            <h2>Delivery</h2>
            <label class="field">Full name<input name="name" required value="<?= e($value('name', (string) ($user['name'] ?? ''))) ?>"></label>
            <label class="field">Email<input type="email" name="email" required value="<?= e($value('email', (string) ($user['email'] ?? ''))) ?>"></label>
            <label class="field">School name<input name="school_name" required value="<?= e($value('school_name', (string) ($user['school_name'] ?? ''))) ?>"></label>
            <label class="field">Mobile number<input name="mobile" inputmode="numeric" required value="<?= e($value('mobile', (string) ($user['phone'] ?? ''))) ?>"></label>
            <label class="field">Delivery address<textarea name="address" rows="3" required><?= e($value('address', (string) ($user['address'] ?? ''))) ?></textarea></label>
            <div class="two">
                <label class="field">City<input name="city" required value="<?= e($value('city', (string) ($user['city'] ?? ''))) ?>"></label>
                <label class="field">PIN code<input name="pincode" inputmode="numeric" required value="<?= e($value('pincode', (string) ($user['pincode'] ?? ''))) ?>"></label>
            </div>
            <label class="field">Notes for delivery<textarea name="notes" rows="2"><?= e($value('notes')) ?></textarea></label>
            <?php if ($user): ?>
                <label class="check"><input type="checkbox" name="save_address" value="1"> Save this school and address to my account</label>
            <?php else: ?>
                <p class="small muted"><a href="<?= e(url('login.php?next=' . urlencode(url('checkout.php')))) ?>">Login</a> to save this order in your account.</p>
            <?php endif; ?>
            <h2>Payment</h2>
            <?php $selected = $value('payment_method', 'razorpay'); ?>
            <label class="check"><input type="radio" name="payment_method" value="razorpay"<?= $selected === 'razorpay' ? ' checked' : '' ?>> Pay online with Razorpay</label>
            <label class="check"><input type="radio" name="payment_method" value="cod"<?= $selected === 'cod' ? ' checked' : '' ?>> Cash on delivery</label>
        </div>
        <aside class="bill">
            <h2>Your order</h2>
            <?php foreach ($summary['items'] as $item): ?>
                <p><?= (int) $item['quantity'] ?> × <?= e($item['name']) ?></p>
            <?php endforeach; ?>
            <?php render_totals($summary); ?>
            <div class="coupon-row">
                <input name="coupon" placeholder="Coupon code" value="<?= e($value('coupon', (string) ($_SESSION['coupon'] ?? ''))) ?>">
                <button class="btn btn-line" type="submit" name="action" value="coupon">Apply</button>
            </div>
            <div class="chips">
                <?php foreach ($coupons as $coupon): ?>
                    <button class="chip" type="button" data-fill-coupon="<?= e($coupon['code']) ?>"><?= e($coupon['code']) ?></button>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($_SESSION['coupon'])): ?>
                <button class="text-btn" type="submit" name="action" value="remove_coupon">Remove coupon</button>
            <?php endif; ?>
            <button class="btn btn-green btn-block" type="submit" name="action" value="place">Place order</button>
        </aside>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
