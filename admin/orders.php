<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 40);
$delivery = (string) ($_GET['delivery'] ?? '');
$payment = (string) ($_GET['payment'] ?? '');
$type = (string) ($_GET['type'] ?? '');
$sql = 'SELECT * FROM orders WHERE 1 = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (order_number LIKE ? OR school_name LIKE ? OR mobile LIKE ? OR customer_name LIKE ?)';
    $like = '%' . like_escape($q) . '%';
    array_push($params, $like, $like, $like, $like);
}
if (isset(delivery_statuses()[$delivery])) {
    $sql .= ' AND delivery_status = ?';
    $params[] = $delivery;
}
if (isset(payment_statuses()[$payment])) {
    $sql .= ' AND payment_status = ?';
    $params[] = $payment;
}
if (in_array($type, ['retail', 'bulk'], true)) {
    $sql .= ' AND order_type = ?';
    $params[] = $type;
}
$sql .= ' ORDER BY id DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
$storeNames = [];
foreach (dark_stores_all() as $store) {
    $storeNames[(int) $store['id']] = $store['name'];
}

$pageTitle = 'Orders';
$active = 'orders';
require __DIR__ . '/includes/header.php';
?>
<form class="filters" method="get">
    <input name="q" value="<?= e($q) ?>" placeholder="Order, school, mobile">
    <select name="delivery"><option value="">All delivery</option><?php foreach (delivery_statuses() as $key => $label): ?><option value="<?= e($key) ?>"<?= $delivery === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
    <select name="payment"><option value="">All payments</option><?php foreach (payment_statuses() as $key => $label): ?><option value="<?= e($key) ?>"<?= $payment === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
    <select name="type"><option value="">All types</option><option value="retail"<?= $type === 'retail' ? ' selected' : '' ?>>Retail</option><option value="bulk"<?= $type === 'bulk' ? ' selected' : '' ?>>Bulk</option></select>
    <button class="btn" type="submit">Filter</button>
</form>
<div class="table-wrap">
    <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Dark store</th><th>Total</th><th>Payment</th><th>Delivery</th><th>Placed</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><a href="order.php?no=<?= e(rawurlencode($order['order_number'])) ?>"><?= e($order['order_number']) ?></a><br><?= e($order['order_type']) ?></td>
                <td><?= e($order['customer_name']) ?><br><?= e($order['school_name']) ?><br><?= e($order['mobile']) ?></td>
                <td><?= e($storeNames[(int) ($order['dark_store_id'] ?? 0)] ?? 'Unassigned') ?></td>
                <td><?= money($order['total']) ?></td>
                <td><?= e($order['payment_status']) ?><br><?= e($order['payment_method']) ?></td>
                <td><?= e(str_replace('_', ' ', $order['delivery_status'])) ?></td>
                <td><?= e(when($order['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
