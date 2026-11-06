<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

$edit = null;
if (!empty($_GET['id'])) {
    $stmt = db()->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $edit = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'toggle') {
        db()->prepare('UPDATE coupons SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?')->execute([$id]);
        flash('success', 'Coupon updated.');
        redirect(url('admin/coupons.php'));
    }
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
    $description = trim((string) ($_POST['description'] ?? ''));
    $type = (string) ($_POST['discount_type'] ?? 'percent');
    $value = round((float) ($_POST['discount_value'] ?? 0), 2);
    $min = round((float) ($_POST['min_order'] ?? 0), 2);
    $max = trim((string) ($_POST['max_discount'] ?? ''));
    $limit = trim((string) ($_POST['usage_limit'] ?? ''));
    $from = trim((string) ($_POST['valid_from'] ?? ''));
    $to = trim((string) ($_POST['valid_to'] ?? ''));
    $errors = [];
    if (!preg_match('/^[A-Z0-9]{3,20}$/', $code)) {
        $errors[] = 'Code must be 3–20 letters or numbers.';
    }
    if ($description === '' || mb_strlen($description) > 180) {
        $errors[] = 'Enter a short description.';
    }
    if (!in_array($type, ['percent', 'flat'], true) || $value <= 0 || ($type === 'percent' && $value > 100)) {
        $errors[] = 'Check the discount type and value.';
    }
    if ($min < 0) {
        $errors[] = 'Minimum order cannot be negative.';
    }
    if ($from !== '' && !valid_date($from)) {
        $errors[] = 'Start date is invalid.';
    }
    if ($to !== '' && !valid_date($to)) {
        $errors[] = 'End date is invalid.';
    }
    if ($from !== '' && $to !== '' && $to < $from) {
        $errors[] = 'End date must be on or after the start date.';
    }
    $exists = db()->prepare('SELECT id FROM coupons WHERE code = ? AND id <> ?');
    $exists->execute([$code, $id]);
    if ($exists->fetch()) {
        $errors[] = 'That code already exists.';
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect(url('admin/coupons.php' . ($id ? '?id=' . $id : '')));
    }
    $maxValue = $max === '' ? null : round((float) $max, 2);
    $limitValue = $limit === '' ? null : max(0, (int) $limit);
    $fromValue = $from === '' ? null : $from;
    $toValue = $to === '' ? null : $to;
    if ($id) {
        db()->prepare('UPDATE coupons SET code=?, description=?, discount_type=?, discount_value=?, min_order=?, max_discount=?, usage_limit=?, valid_from=?, valid_to=? WHERE id=?')
            ->execute([$code, $description, $type, $value, $min, $maxValue, $limitValue, $fromValue, $toValue, $id]);
        flash('success', 'Coupon saved.');
    } else {
        db()->prepare('INSERT INTO coupons (code, description, discount_type, discount_value, min_order, max_discount, usage_limit, valid_from, valid_to) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$code, $description, $type, $value, $min, $maxValue, $limitValue, $fromValue, $toValue]);
        flash('success', 'Coupon created.');
    }
    redirect(url('admin/coupons.php'));
}

$coupons = db()->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
$pageTitle = 'Coupons';
$active = 'coupons';
require __DIR__ . '/includes/header.php';
$c = $edit ?? [];
?>
<form method="post" class="panel" style="max-width:720px;margin-bottom:16px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($c['id'] ?? 0) ?>">
    <h2><?= $edit ? 'Edit coupon' : 'Add coupon' ?></h2>
    <label class="field">Code<input name="code" required value="<?= e($c['code'] ?? '') ?>"></label>
    <label class="field">Description<input name="description" required value="<?= e($c['description'] ?? '') ?>"></label>
    <label class="field">Type
        <select name="discount_type">
            <option value="percent"<?= ($c['discount_type'] ?? '') === 'percent' ? ' selected' : '' ?>>Percent</option>
            <option value="flat"<?= ($c['discount_type'] ?? '') === 'flat' ? ' selected' : '' ?>>Flat amount</option>
        </select>
    </label>
    <div class="filters">
        <label class="field">Value<input type="number" step="0.01" name="discount_value" required value="<?= e((string) ($c['discount_value'] ?? '')) ?>"></label>
        <label class="field">Minimum order<input type="number" step="0.01" name="min_order" value="<?= e((string) ($c['min_order'] ?? '0')) ?>"></label>
        <label class="field">Max discount<input type="number" step="0.01" name="max_discount" value="<?= e((string) ($c['max_discount'] ?? '')) ?>"></label>
        <label class="field">Usage limit<input type="number" name="usage_limit" value="<?= e((string) ($c['usage_limit'] ?? '')) ?>"></label>
    </div>
    <div class="filters">
        <label class="field">Valid from<input type="date" name="valid_from" value="<?= e((string) ($c['valid_from'] ?? '')) ?>"></label>
        <label class="field">Valid to<input type="date" name="valid_to" value="<?= e((string) ($c['valid_to'] ?? '')) ?>"></label>
    </div>
    <button class="btn" type="submit">Save coupon</button>
</form>
<div class="table-wrap">
    <table>
        <thead><tr><th>Code</th><th>Offer</th><th>Used</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($coupons as $coupon): ?>
            <tr>
                <td><?= e($coupon['code']) ?><br><?= e($coupon['description']) ?></td>
                <td><?= $coupon['discount_type'] === 'percent' ? e((string) $coupon['discount_value']) . '%' : money($coupon['discount_value']) ?> off from <?= money($coupon['min_order']) ?></td>
                <td><?= (int) $coupon['used_count'] ?><?= $coupon['usage_limit'] !== null ? ' / ' . (int) $coupon['usage_limit'] : '' ?></td>
                <td><?= (int) $coupon['is_active'] ? 'Active' : 'Off' ?></td>
                <td>
                    <a class="btn alt" href="coupons.php?id=<?= (int) $coupon['id'] ?>">Edit</a>
                    <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $coupon['id'] ?>"><button class="btn alt" type="submit"><?= (int) $coupon['is_active'] ? 'Disable' : 'Enable' ?></button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
