<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([(int) $user['id']]);
$orders = $stmt->fetchAll();
$pageTitle = 'Orders';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head">
        <h1>Your orders</h1>
        <p class="muted">Open an order to see payment, delivery and the tracking timeline.</p>
    </div>
    <?php if (!$orders): ?>
        <div class="empty">
            <h2>No orders yet</h2>
            <a class="btn btn-green" href="<?= e(url()) ?>">Start shopping</a>
        </div>
    <?php else: ?>
        <div class="menu-list">
            <?php foreach ($orders as $order): ?>
                <a class="order-card" href="<?= e(url('order.php?no=' . rawurlencode($order['order_number']))) ?>">
                    <div class="order-top">
                        <strong><?= e($order['order_number']) ?></strong>
                        <?= status_badge((string) $order['delivery_status']) ?>
                    </div>
                    <p class="muted small"><?= e(when($order['created_at'])) ?> · <?= e($order['school_name']) ?> · <?= money($order['total']) ?></p>
                    <p class="small">Payment <?= e($order['payment_status']) ?><?= $order['order_type'] === 'bulk' ? ' · Bulk order' : '' ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
