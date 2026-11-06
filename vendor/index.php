<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_vendor();
$vendorId = (int) $user['vendor_id'];
$vendorStmt = db()->prepare('SELECT * FROM vendors WHERE id = ? LIMIT 1');
$vendorStmt->execute([$vendorId]);
$vendor = $vendorStmt->fetch();
if (!$vendor) {
    flash('error', 'This login is not linked to a vendor.');
    redirect(url('logout.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $result = receive_stock(
        (int) ($_POST['dark_store_id'] ?? 0),
        (int) ($_POST['product_id'] ?? 0),
        (int) ($_POST['quantity'] ?? 0),
        (int) $user['id'],
        trim((string) ($_POST['note'] ?? 'Sent by vendor')),
        $vendorId
    );
    flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Stock sent to the dark store.' : $result['error']);
    redirect(url('vendor/index.php'));
}

$products = db()->prepare('SELECT id, name, sku, stock FROM products WHERE vendor_id = ? AND is_active = 1 ORDER BY name');
$products->execute([$vendorId]);
$products = $products->fetchAll();
$stores = db()->query('SELECT * FROM dark_stores WHERE is_active = 1 ORDER BY name')->fetchAll();
$levels = db()->prepare('SELECT s.name AS store_name, p.name AS product_name, ss.quantity
    FROM store_stock ss
    JOIN products p ON p.id = ss.product_id
    JOIN dark_stores s ON s.id = ss.dark_store_id
    WHERE p.vendor_id = ?
    ORDER BY s.name, p.name');
$levels->execute([$vendorId]);
$levels = $levels->fetchAll();
$sales = db()->prepare("SELECT p.name, COALESCE(SUM(oi.quantity),0) AS qty, COALESCE(SUM(oi.line_total),0) AS amount
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.id
    LEFT JOIN orders o ON o.id = oi.order_id AND o.delivery_status <> 'cancelled' AND o.stock_deducted = 1
    WHERE p.vendor_id = ?
    GROUP BY p.id, p.name
    ORDER BY amount DESC");
$sales->execute([$vendorId]);
$sales = $sales->fetchAll();

$pageTitle = $vendor['name'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($vendor['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= e(url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="desk">
    <div class="top">
        <div>
            <h1><?= e($vendor['name']) ?></h1>
            <p class="muted">Send stock to a dark store. The store then packs school orders.</p>
        </div>
        <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="btn alt" type="submit">Logout</button></form>
    </div>
    <?php if ($message = flash('success')): ?><div class="alert ok"><?= e($message) ?></div><?php endif; ?>
    <?php if ($message = flash('error')): ?><div class="alert bad"><?= e($message) ?></div><?php endif; ?>

    <form method="post" class="panel">
        <?= csrf_field() ?>
        <h2>Send stock</h2>
        <label class="field">Dark store
            <select name="dark_store_id" required>
                <?php foreach ($stores as $store): ?><option value="<?= (int) $store['id'] ?>"><?= e($store['name']) ?> · <?= e($store['city']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="field">Product
            <select name="product_id" required>
                <?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label class="field">Quantity<input type="number" name="quantity" min="1" max="500" value="10" required></label>
        <label class="field">Note<input name="note" value="Vendor inward" maxlength="180"></label>
        <button class="btn" type="submit">Send to dark store</button>
    </form>

    <div class="layout-2">
        <div class="panel">
            <h2>Stock at dark stores</h2>
            <?php if (!$levels): ?><p>No stock has been placed yet.</p><?php endif; ?>
            <?php foreach ($levels as $row): ?>
                <p><?= e($row['store_name']) ?> · <?= e($row['product_name']) ?> · <?= (int) $row['quantity'] ?></p>
            <?php endforeach; ?>
        </div>
        <div class="panel">
            <h2>Sold from your products</h2>
            <?php foreach ($sales as $row): ?>
                <?php if ((int) $row['qty'] === 0) { continue; } ?>
                <p><?= e($row['name']) ?> · <?= (int) $row['qty'] ?> sold · <?= money($row['amount']) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>
</html>
