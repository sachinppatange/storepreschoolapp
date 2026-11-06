<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$catalog = catalog(['limit' => 48, 'sort' => 'name']);
$grouped = [];
foreach ($catalog['items'] as $product) {
    $grouped[$product['category_name']][] = $product;
}

$user = current_user();
$errors = [];
$step = 'edit';
$quote = null;
$customer = [
    'name' => (string) ($user['name'] ?? ''),
    'email' => (string) ($user['email'] ?? ''),
    'school_name' => (string) ($user['school_name'] ?? ''),
    'mobile' => (string) ($user['phone'] ?? ''),
    'address' => (string) ($user['address'] ?? ''),
    'city' => (string) ($user['city'] ?? ''),
    'pincode' => (string) (current_delivery()['pincode'] ?? ''),
    'notes' => '',
];
$qty = [];
$counts = ['nursery' => 0, 'jr' => 0, 'sr' => 0];
$method = 'cod';
$coupon = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $deliveryNow = current_delivery();
    if ($deliveryNow) {
        $_POST['pincode'] = $deliveryNow['pincode'];
        if (trim((string) ($_POST['city'] ?? '')) === '') {
            $_POST['city'] = (string) $deliveryNow['store']['city'];
        }
    }
    $action = (string) ($_POST['action'] ?? 'review');
    [$fieldErrors, $customer] = validate_customer_fields($_POST);
    $errors = $fieldErrors;
    if (!$deliveryNow) {
        $errors[] = 'Choose a delivery area at the top before placing a bulk order.';
    }
    $method = (string) ($_POST['payment_method'] ?? 'cod');
    if (!in_array($method, ['razorpay', 'cod'], true)) {
        $errors[] = 'Choose how the school will pay.';
    }
    $coupon = strtoupper(trim((string) ($_POST['coupon'] ?? '')));
    foreach (['nursery', 'jr', 'sr'] as $key) {
        $counts[$key] = max(0, min(5000, (int) ($_POST[$key . '_count'] ?? 0)));
    }
    $qtyInput = $_POST['qty'] ?? [];
    if (is_array($qtyInput)) {
        foreach ($qtyInput as $id => $value) {
            $id = (int) $id;
            $value = max(0, min(500, (int) $value));
            if ($id > 0 && $value > 0) {
                $qty[$id] = $value;
            }
        }
    }
    $lines = [];
    foreach ($qty as $id => $value) {
        $lines[] = ['id' => $id, 'qty' => $value];
    }
    if (!$lines) {
        $errors[] = 'Enter a quantity for at least one product.';
    }
    if (!$errors && $action !== 'edit') {
        $quote = price_lines($lines, $coupon !== '' ? $coupon : null, 'bulk', false);
        if (!$quote['ok']) {
            $errors[] = $quote['error'];
            $quote = null;
        } elseif ($action === 'place') {
            $classNote = 'Class strength — Nursery: ' . $counts['nursery'] . ', Jr. KG: ' . $counts['jr'] . ', Sr. KG: ' . $counts['sr'];
            $customer['notes'] = mb_substr(trim($classNote . ($customer['notes'] !== '' ? "\n" . $customer['notes'] : '')), 0, 500);
            $result = place_order([
                'user_id' => $user ? (int) $user['id'] : null,
                'name' => $customer['name'],
                'email' => $customer['email'],
                'school_name' => $customer['school_name'],
                'mobile' => $customer['mobile'],
                'address' => $customer['address'],
                'city' => $customer['city'],
                'pincode' => $customer['pincode'],
                'notes' => $customer['notes'],
            ], $lines, $method, 'bulk', $coupon !== '' ? $coupon : null);
            if ($result['ok']) {
                remember_order_access($result['order']['order_number']);
                if ($method === 'razorpay') {
                    redirect(url('pay.php?no=' . rawurlencode($result['order']['order_number'])));
                }
                redirect(url('order-success.php?no=' . rawurlencode($result['order']['order_number'])));
            }
            $errors[] = $result['error'];
        } else {
            $step = 'review';
        }
    }
}

$pageTitle = 'Bulk order';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head">
        <h1>Bulk order for schools</h1>
        <?php if (is_school_buyer()): ?>
            <p class="muted">You are ordering as <strong><?= e((string) current_user()['school_name']) ?></strong>. Every price is the school price, <?= (int) school_b2b_percent() ?>% below the parent price. No coupon is required for that discount.</p>
        <?php else: ?>
            <p class="muted">Parents pay the regular price here. A school login sees B2B prices and can order a class in one go. <a href="<?= e(url('login.php?next=' . urlencode(url('bulk-order.php')))) ?>">School login</a></p>
        <?php endif; ?>
    </div>
    <?php if ($errors): ?><ul class="errors"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>

    <?php if ($step === 'review' && $quote): ?>
        <form method="post" class="layout">
            <?= csrf_field() ?>
            <?php foreach ($customer as $key => $value): ?>
                <input type="hidden" name="<?= e($key === 'mobile' ? 'mobile' : $key) ?>" value="<?= e($value) ?>">
            <?php endforeach; ?>
            <input type="hidden" name="nursery_count" value="<?= (int) $counts['nursery'] ?>">
            <input type="hidden" name="jr_count" value="<?= (int) $counts['jr'] ?>">
            <input type="hidden" name="sr_count" value="<?= (int) $counts['sr'] ?>">
            <input type="hidden" name="coupon" value="<?= e($coupon) ?>">
            <input type="hidden" name="payment_method" value="<?= e($method) ?>">
            <?php foreach ($qty as $id => $value): ?>
                <input type="hidden" name="qty[<?= (int) $id ?>]" value="<?= (int) $value ?>">
            <?php endforeach; ?>
            <div class="panel">
                <h2>Review</h2>
                <p><?= e($customer['school_name']) ?><br><?= e($customer['name']) ?> · <?= e($customer['mobile']) ?><br><?= e($customer['address']) ?>, <?= e($customer['city']) ?> <?= e($customer['pincode']) ?></p>
                <?php foreach ($quote['items'] as $item): ?>
                    <p><?= (int) $item['qty'] ?> × <?= e($item['name']) ?> · <?= money($item['line_total']) ?></p>
                <?php endforeach; ?>
            </div>
            <aside class="bill">
                <?php render_totals([
                    'subtotal' => $quote['subtotal'],
                    'discount' => $quote['discount'],
                    'coupon' => $quote['coupon'],
                    'delivery' => $quote['delivery'],
                    'total' => $quote['total'],
                ]); ?>
                <p class="small muted"><?= $method === 'cod' ? 'Pay later against the invoice.' : 'You will pay online on the next step.' ?></p>
                <button class="btn btn-green btn-block" name="action" value="place" type="submit">Place bulk order</button>
                <button class="btn btn-ghost btn-block" name="action" value="edit" type="submit">Edit quantities</button>
            </aside>
        </form>
    <?php else: ?>
        <form method="post" data-bulk>
            <?= csrf_field() ?>
            <div class="layout">
                <div>
                    <div class="panel">
                        <h2>School details</h2>
                        <label class="field">Contact name<input name="name" required value="<?= e($customer['name']) ?>"></label>
                        <label class="field">Email<input type="email" name="email" required value="<?= e($customer['email']) ?>"></label>
                        <label class="field">School name<input name="school_name" required value="<?= e($customer['school_name']) ?>"></label>
                        <label class="field">Mobile<input name="mobile" required value="<?= e($customer['mobile']) ?>"></label>
                        <label class="field">Delivery address<textarea name="address" rows="3" required><?= e($customer['address']) ?></textarea></label>
                        <label class="field">City<input name="city" required value="<?= e($customer['city'] !== '' ? $customer['city'] : (string) (current_delivery()['store']['city'] ?? '')) ?>"></label>
                        <?php $bulkDelivery = current_delivery(); ?>
                        <?php if ($bulkDelivery): ?>
                            <input type="hidden" name="pincode" value="<?= e($bulkDelivery['pincode']) ?>">
                            <p class="small"><?= e($bulkDelivery['store']['name']) ?> packs this order for PIN <?= e($bulkDelivery['pincode']) ?>.</p>
                        <?php else: ?>
                            <p class="alert-warn">Choose a delivery area in the bar above before adding quantities.</p>
                        <?php endif; ?>
                        <div class="two">
                            <label class="field">Nursery students<input type="number" min="0" name="nursery_count" value="<?= (int) $counts['nursery'] ?>"></label>
                            <label class="field">Jr. KG students<input type="number" min="0" name="jr_count" value="<?= (int) $counts['jr'] ?>"></label>
                        </div>
                        <label class="field">Sr. KG students<input type="number" min="0" name="sr_count" value="<?= (int) $counts['sr'] ?>"></label>
                        <label class="field">Notes<textarea name="notes" rows="2"><?= e($customer['notes']) ?></textarea></label>
                    </div>
                    <?php foreach ($grouped as $categoryName => $products): ?>
                        <section class="section">
                            <h2><?= e($categoryName) ?></h2>
                            <div class="bulk-list">
                                <?php foreach ($products as $product): ?>
                                    <label class="bulk-item">
                                        <span>
                                            <strong><?= e($product['emoji']) ?> <?= e($product['name']) ?></strong><br>
                                            <span class="muted small"><?= money($product['price']) ?> · <?= (int) $product['stock'] ?> in stock</span>
                                        </span>
                                        <input data-price="<?= e((string) $product['price']) ?>" type="number" min="0" max="<?= (int) $product['stock'] ?>" name="qty[<?= (int) $product['id'] ?>]" value="<?= (int) ($qty[(int) $product['id']] ?? 0) ?>" aria-label="Quantity for <?= e($product['name']) ?>">
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
                <aside class="bill">
                    <h2>Payment</h2>
                    <label class="check"><input type="radio" name="payment_method" value="cod"<?= $method === 'cod' ? ' checked' : '' ?>> Request invoice, pay later</label>
                    <label class="check"><input type="radio" name="payment_method" value="razorpay"<?= $method === 'razorpay' ? ' checked' : '' ?>> Pay online now</label>
                    <label class="field">Coupon<input name="coupon" value="<?= e($coupon) ?>" placeholder="SCHOOL15"></label>
                    <p data-bulk-total class="price">0 items</p>
                    <button class="btn btn-green btn-block" name="action" value="review" type="submit">Review bulk order</button>
                </aside>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
