<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

$stats = [
    'Sales today' => money((float) db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid' AND delivery_status <> 'cancelled' AND DATE(created_at) = CURDATE()")->fetchColumn()),
    'Orders today' => (string) db()->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'Paid sales' => money((float) db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid' AND delivery_status <> 'cancelled'")->fetchColumn()),
    'Customers' => (string) db()->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    'Pending payment' => (string) db()->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending' AND delivery_status <> 'cancelled'")->fetchColumn(),
    'Low stock' => (string) db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= 10')->fetchColumn(),
];
$recent = db()->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();
$low = db()->query('SELECT name, stock, sku FROM products WHERE is_active = 1 AND stock <= 10 ORDER BY stock ASC LIMIT 6')->fetchAll();

$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="cards">
    <?php foreach ($stats as $label => $value): ?>
        <div class="card"><span><?= e($label) ?></span><strong><?= e((string) $value) ?></strong></div>
    <?php endforeach; ?>
</div>
<div class="panel" style="margin-bottom:12px">
    <h2>Recent orders</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Order</th><th>School</th><th>Total</th><th>Payment</th><th>Delivery</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $order): ?>
                <tr>
                    <td><a href="order.php?no=<?= e(rawurlencode($order['order_number'])) ?>"><?= e($order['order_number']) ?></a></td>
                    <td><?= e($order['school_name']) ?></td>
                    <td><?= money($order['total']) ?></td>
                    <td><?= e($order['payment_status']) ?></td>
                    <td><?= e(str_replace('_', ' ', $order['delivery_status'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="panel">
    <h2>Low stock</h2>
    <?php if (!$low): ?>
        <p>All active products have more than 10 in stock.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($low as $item): ?>
                <li><?= e($item['name']) ?> · <?= (int) $item['stock'] ?> left · <?= e($item['sku']) ?></li>
            <?php endforeach; ?>
        </ul>
        <a class="btn" href="products.php?stock=low">Manage inventory</a>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
