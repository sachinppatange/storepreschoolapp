<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
            $address = trim((string) ($_POST['address'] ?? ''));
            $city = trim((string) ($_POST['city'] ?? ''));
            $pin = preg_replace('/\D/', '', (string) ($_POST['pincode'] ?? '')) ?? '';
            $phone = normalize_phone((string) ($_POST['phone'] ?? ''));
            $pins = preg_split('/[\s,]+/', (string) ($_POST['pins'] ?? '')) ?: [];
            if (mb_strlen($name) < 2 || !preg_match('/^[A-Z0-9-]{3,40}$/', $code) || mb_strlen($address) < 8 || mb_strlen($city) < 2 || !preg_match('/^\d{6}$/', $pin) || !valid_phone($phone)) {
                flash('error', 'Check the store name, code, address, city, PIN and mobile.');
            } else {
                db()->prepare('INSERT INTO dark_stores (name, code, address, city, pincode, phone) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute([$name, $code, $address, $city, $pin, $phone]);
                $storeId = (int) db()->lastInsertId();
                $savePin = db()->prepare('INSERT IGNORE INTO dark_store_pins (pincode, dark_store_id) VALUES (?, ?)');
                $savePin->execute([$pin, $storeId]);
                foreach ($pins as $extra) {
                    $extra = preg_replace('/\D/', '', (string) $extra) ?? '';
                    if (preg_match('/^\d{6}$/', $extra)) {
                        $savePin->execute([$extra, $storeId]);
                    }
                }
                foreach (db()->query('SELECT id FROM products') as $product) {
                    db()->prepare('INSERT IGNORE INTO store_stock (dark_store_id, product_id, quantity) VALUES (?, ?, 0)')
                        ->execute([$storeId, (int) $product['id']]);
                }
                flash('success', 'Dark store created. Orders for its PINs will come here.');
            }
        } elseif ($action === 'pin') {
            $storeId = (int) ($_POST['dark_store_id'] ?? 0);
            $pin = preg_replace('/\D/', '', (string) ($_POST['pincode'] ?? '')) ?? '';
            if (!dark_store_by_id($storeId) || !preg_match('/^\d{6}$/', $pin)) {
                flash('error', 'Choose a store and a 6-digit PIN.');
            } else {
                db()->prepare('INSERT INTO dark_store_pins (pincode, dark_store_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE dark_store_id = VALUES(dark_store_id)')
                    ->execute([$pin, $storeId]);
                flash('success', 'PIN ' . $pin . ' now belongs to that dark store.');
            }
        } elseif ($action === 'pause') {
            $storeId = (int) ($_POST['dark_store_id'] ?? 0);
            db()->prepare('UPDATE dark_stores SET is_active = 1 - is_active WHERE id = ?')->execute([$storeId]);
            flash('success', 'Dark store updated.');
        }
    } catch (Throwable $e) {
        flash('error', 'Could not save the dark store. The code may already exist.');
    }
    redirect(url('admin/dark-stores.php'));
}

$stores = dark_stores_all();
$pageTitle = 'Dark stores';
$active = 'stores';
require __DIR__ . '/includes/header.php';
?>
<div class="layout-2">
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <h2>New dark store</h2>
        <label class="field">Name<input name="name" required placeholder="Pune Camp Dark Store"></label>
        <label class="field">Code<input name="code" required placeholder="PUNE-CAMP"></label>
        <label class="field">Address<input name="address" required></label>
        <label class="field">City<input name="city" required></label>
        <label class="field">Store PIN<input name="pincode" required inputmode="numeric"></label>
        <label class="field">More PINs this store delivers<input name="pins" placeholder="411001, 411004"></label>
        <label class="field">Mobile<input name="phone" required></label>
        <button class="btn" type="submit">Save dark store</button>
    </form>
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="pin">
        <h2>Move a PIN to a store</h2>
        <label class="field">Dark store
            <select name="dark_store_id"><?php foreach ($stores as $store): ?><option value="<?= (int) $store['id'] ?>"><?= e($store['name']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field">PIN code<input name="pincode" required inputmode="numeric"></label>
        <button class="btn" type="submit">Assign PIN</button>
    </form>
</div>
<div class="jobs">
    <?php foreach ($stores as $store): ?>
        <?php
        $open = db()->prepare("SELECT COUNT(*) FROM orders WHERE dark_store_id = ? AND delivery_status IN ('placed','confirmed','packed','shipped','out_for_delivery')");
        $open->execute([(int) $store['id']]);
        ?>
        <article class="job">
            <h2><?= e($store['name']) ?> <?= (int) $store['is_active'] ? '' : '(paused)' ?></h2>
            <p><?= e($store['address']) ?>, <?= e($store['city']) ?> <?= e($store['pincode']) ?><br><?= e($store['phone']) ?> · <?= e($store['code']) ?></p>
            <p>Delivers to <?= e(implode(', ', store_pins((int) $store['id']))) ?></p>
            <p><?= (int) $open->fetchColumn() ?> open orders</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="pause">
                <input type="hidden" name="dark_store_id" value="<?= (int) $store['id'] ?>">
                <button class="btn alt" type="submit"><?= (int) $store['is_active'] ? 'Pause store' : 'Resume store' ?></button>
            </form>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
