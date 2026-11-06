<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = normalize_phone((string) ($_POST['phone'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $city = trim((string) ($_POST['city'] ?? ''));
        $login = strtolower(trim((string) ($_POST['login_email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($name) < 2 || !valid_phone($phone) || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($city) < 2) {
            flash('error', 'Enter the vendor name, mobile, email and city.');
        } else {
            try {
                db()->prepare('INSERT INTO vendors (name, phone, email, city) VALUES (?, ?, ?, ?)')->execute([$name, $phone, $email, $city]);
                $vendorId = (int) db()->lastInsertId();
                if ($login !== '' || $password !== '') {
                    if (!filter_var($login, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                        flash('error', 'Vendor saved. Login needs an email and a password of 8 or more characters.');
                    } else {
                        db()->prepare('INSERT INTO users (name, email, phone, password_hash, role, school_name, city, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                            ->execute([$name, $login, $phone, password_hash($password, PASSWORD_DEFAULT), 'vendor', 'Vendor', $city, $vendorId]);
                        flash('success', 'Vendor and login created.');
                    }
                } else {
                    flash('success', 'Vendor created.');
                }
            } catch (Throwable $e) {
                flash('error', 'That vendor email or login is already used.');
            }
        }
    } elseif ($action === 'assign') {
        $vendorId = (int) ($_POST['vendor_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        db()->prepare('UPDATE products SET vendor_id = ? WHERE category_id = ?')->execute([$vendorId, $categoryId]);
        flash('success', 'Products in that category now belong to the vendor.');
    }
    redirect(url('admin/vendors.php'));
}

$vendors = vendors_all();
$categories = categories_all();
$pageTitle = 'Vendors';
$active = 'vendors';
require __DIR__ . '/includes/header.php';
?>
<div class="layout-2">
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <h2>New vendor</h2>
        <label class="field">Name<input name="name" required></label>
        <label class="field">Mobile<input name="phone" required></label>
        <label class="field">Contact email<input type="email" name="email" required></label>
        <label class="field">City<input name="city" required></label>
        <h2>Optional login</h2>
        <label class="field">Login email<input type="email" name="login_email"></label>
        <label class="field">Password<input type="text" name="password" minlength="8"></label>
        <button class="btn" type="submit">Save vendor</button>
    </form>
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="assign">
        <h2>Give a category to a vendor</h2>
        <label class="field">Vendor
            <select name="vendor_id"><?php foreach ($vendors as $vendor): ?><option value="<?= (int) $vendor['id'] ?>"><?= e($vendor['name']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="field">Category
            <select name="category_id"><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select>
        </label>
        <button class="btn" type="submit">Assign</button>
    </form>
</div>
<div class="panel">
    <h2>Vendors</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>City</th><th>Contact</th><th>Products</th></tr></thead>
            <tbody>
            <?php foreach ($vendors as $vendor): ?>
                <?php $count = db()->prepare('SELECT COUNT(*) FROM products WHERE vendor_id = ?'); $count->execute([(int) $vendor['id']]); ?>
                <tr>
                    <td><?= e($vendor['name']) ?></td>
                    <td><?= e($vendor['city']) ?></td>
                    <td><?= e($vendor['phone']) ?><br><?= e($vendor['email']) ?></td>
                    <td><?= (int) $count->fetchColumn() ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
