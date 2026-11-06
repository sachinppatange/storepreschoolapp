<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'coupon') {
        $result = set_coupon((string) ($_POST['coupon'] ?? ''));
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? $result['message'] : $result['error']);
    } elseif ($action === 'remove_coupon') {
        unset($_SESSION['coupon']);
        flash('success', 'Coupon removed.');
    } elseif ($action === 'remove_print') {
        $result = print_job_remove((int) ($_POST['print_id'] ?? 0));
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Print removed.' : $result['error']);
    } else {
        $result = cart_apply($_POST);
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? ($result['message'] ?? 'Cart updated') : $result['error']);
    }
    $return = safe_return((string) ($_POST['return'] ?? ''), url('cart.php'));
    if (!in_array($action, ['add', 'update', 'remove'], true)) {
        $return = url('cart.php');
    }
    redirect($return);
}

$hideDock = true;
$pageTitle = 'Cart';
$summary = cart_summary();
$coupons = active_coupons();
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head"><h1>Your cart</h1></div>
    <?php if (!$summary['items'] && empty($summary['prints'])): ?>
        <div class="empty">
            <p class="emoji" style="font-size:42px">🛒</p>
            <h2>Your cart is empty</h2>
            <p class="muted">Add toys, books, a class kit, or a file to print.</p>
            <a class="btn btn-green" href="<?= e(url('category.php')) ?>">Browse products</a>
            <a class="btn btn-ghost" href="<?= e(url('print.php')) ?>">Print a file</a>
        </div>
    <?php else: ?>
        <div class="layout">
            <div class="panel">
                <?php foreach ($summary['items'] as $item): ?>
                    <div class="cart-line">
                        <a class="thumb" style="background:<?= e(category_tint((int) $item['category_id'])) ?>" href="<?= e(url('product.php?slug=' . rawurlencode($item['slug']))) ?>"><?= e($item['emoji']) ?></a>
                        <div>
                            <strong><a href="<?= e(url('product.php?slug=' . rawurlencode($item['slug']))) ?>"><?= e($item['name']) ?></a></strong>
                            <p class="muted small"><?= e($item['unit_label']) ?> · <?= money($item['price']) ?></p>
                            <?php if ((int) $item['quantity'] > (int) $item['stock']): ?>
                                <p class="stock out">Only <?= (int) $item['stock'] ?> left. Reduce the quantity.</p>
                            <?php endif; ?>
                            <div class="line-actions">
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                    <input type="number" name="qty" min="0" max="<?= min(50, (int) $item['stock']) ?>" value="<?= (int) $item['quantity'] ?>" aria-label="Quantity for <?= e($item['name']) ?>">
                                    <button class="btn btn-line" type="submit">Update</button>
                                </form>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                                    <button class="text-btn" type="submit">Remove</button>
                                </form>
                            </div>
                        </div>
                        <strong><?= money((float) $item['price'] * (int) $item['quantity']) ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($summary['prints'] ?? [] as $job): ?>
                    <div class="cart-line">
                        <div class="thumb">🖨️</div>
                        <div>
                            <strong><?= e(print_label($job)) ?></strong>
                            <p class="muted small"><?= e($job['original_name']) ?></p>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove_print">
                                <input type="hidden" name="print_id" value="<?= (int) $job['id'] ?>">
                                <button class="text-btn" type="submit">Remove</button>
                            </form>
                        </div>
                        <strong><?= money($job['line_total']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
            <aside class="bill">
                <h2>Bill details</h2>
                <?php render_totals($summary); ?>
                <form method="post" class="coupon-row">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="coupon">
                    <label class="sr-only" for="coupon">Coupon code</label>
                    <input id="coupon" name="coupon" placeholder="Coupon code" value="<?= e($_SESSION['coupon'] ?? '') ?>">
                    <button class="btn btn-line" type="submit">Apply</button>
                </form>
                <?php if (!empty($_SESSION['coupon'])): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove_coupon">
                        <button class="text-btn" type="submit">Remove coupon</button>
                    </form>
                <?php endif; ?>
                <div class="chips">
                    <?php foreach ($coupons as $coupon): ?>
                        <button class="chip" type="button" data-fill-coupon="<?= e($coupon['code']) ?>"><?= e($coupon['code']) ?></button>
                    <?php endforeach; ?>
                </div>
                <p class="small muted">Free delivery over <?= money(FREE_DELIVERY_ABOVE) ?>.</p>
                <a class="btn btn-green btn-block" href="<?= e(url('checkout.php')) ?>">Checkout</a>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
