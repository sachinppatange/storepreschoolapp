<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_store();
$store = dark_store_by_id((int) $user['dark_store_id']);
if (!$store) {
    flash('error', 'This login is not linked to a dark store.');
    redirect(url('logout.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'advance') {
        $order = find_order_by_number(strtoupper(trim((string) ($_POST['order_number'] ?? ''))));
        $result = $order
            ? store_advance_order($order, (int) $store['id'])
            : ['ok' => false, 'error' => 'Order not found.'];
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Order updated.' : $result['error']);
    } elseif ($action === 'receive') {
        $result = receive_stock(
            (int) $store['id'],
            (int) ($_POST['product_id'] ?? 0),
            (int) ($_POST['quantity'] ?? 0),
            (int) $user['id'],
            trim((string) ($_POST['note'] ?? 'Received from vendor'))
        );
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Stock added at this dark store.' : $result['error']);
    }
    redirect(url('store/index.php'));
}

$openStmt = db()->prepare("SELECT * FROM orders WHERE dark_store_id = ? AND delivery_status IN ('placed','confirmed','packed','shipped','out_for_delivery') ORDER BY id");
$openStmt->execute([(int) $store['id']]);
$open = $openStmt->fetchAll();
$doneStmt = db()->prepare("SELECT order_number, school_name, total, delivery_status FROM orders WHERE dark_store_id = ? AND delivery_status = 'delivered' ORDER BY id DESC LIMIT 5");
$doneStmt->execute([(int) $store['id']]);
$done = $doneStmt->fetchAll();
$products = db()->query('SELECT id, name FROM products WHERE is_active = 1 ORDER BY name')->fetchAll();
$lowStmt = db()->prepare('SELECT p.name, s.quantity FROM store_stock s JOIN products p ON p.id = s.product_id WHERE s.dark_store_id = ? AND s.quantity <= 3 ORDER BY s.quantity, p.name LIMIT 8');
$lowStmt->execute([(int) $store['id']]);
$low = $lowStmt->fetchAll();
$pins = store_pins((int) $store['id']);

$pageTitle = $store['name'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($store['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= e(url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="desk">
    <div class="top">
        <div>
            <h1><?= e($store['name']) ?></h1>
            <p class="muted">PINs <?= e(implode(', ', $pins)) ?> · <?= e($user['name']) ?></p>
        </div>
        <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="btn alt" type="submit">Logout</button></form>
    </div>
    <?php if ($message = flash('success')): ?><div class="alert ok"><?= e($message) ?></div><?php endif; ?>
    <?php if ($message = flash('error')): ?><div class="alert bad"><?= e($message) ?></div><?php endif; ?>

    <h2>Orders to process</h2>
    <?php if (!$open): ?>
        <div class="panel"><p>No open orders. New school orders for these PINs will land here.</p></div>
    <?php endif; ?>
    <div class="jobs">
        <?php foreach ($open as $order): ?>
            <?php $step = store_next_step((string) $order['delivery_status']); ?>
            <article class="job">
                <h2><?= e($order['order_number']) ?></h2>
                <p><strong><?= e($order['school_name']) ?></strong><br><?= e($order['address']) ?>, <?= e($order['city']) ?> <?= e($order['pincode']) ?><br><?= e($order['mobile']) ?></p>
                <?php foreach (order_items((int) $order['id']) as $item): ?>
                    <p><?= (int) $item['quantity'] ?> × <?= e($item['product_name']) ?></p>
                <?php endforeach; ?>
                <p><?= e(str_replace('_', ' ', (string) $order['delivery_status'])) ?> · <?= e($order['payment_method']) ?> · <?= money($order['total']) ?></p>
                <?php if ($order['notes']): ?><p><?= e($order['notes']) ?></p><?php endif; ?>
                <?php if ($step): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="advance">
                        <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
                        <button class="btn" type="submit"><?= e($step[1]) ?></button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="layout-2">
        <form method="post" class="panel">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="receive">
            <h2>Receive vendor stock</h2>
            <label class="field">Product
                <select name="product_id" required>
                    <?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="field">Quantity<input type="number" name="quantity" min="1" max="500" value="1" required></label>
            <label class="field">Note<input name="note" value="Received from vendor" maxlength="180"></label>
            <button class="btn" type="submit">Add stock</button>
        </form>
        <div class="panel">
            <h2>Low stock</h2>
            <?php if (!$low): ?><p>Nothing is at 3 or below.</p><?php endif; ?>
            <?php foreach ($low as $row): ?><p><?= e($row['name']) ?> · <?= (int) $row['quantity'] ?></p><?php endforeach; ?>
            <?php if ($done): ?>
                <h2>Recent deliveries</h2>
                <?php foreach ($done as $row): ?><p><?= e($row['order_number']) ?> · <?= e($row['school_name']) ?></p><?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
