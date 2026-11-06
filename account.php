<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? 'profile');
    if ($action === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = normalize_phone((string) ($_POST['mobile'] ?? ''));
        $school = trim((string) ($_POST['school_name'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $pin = preg_replace('/\D/', '', (string) ($_POST['pincode'] ?? '')) ?? '';
        if (!preg_match("/^[\\p{L}\\s.'-]{2,80}$/u", $name)) {
            $errors[] = 'Enter your name.';
        }
        if (!valid_phone($phone)) {
            $errors[] = 'Enter a valid 10-digit mobile number.';
        }
        if ($school !== '' && mb_strlen($school) > 180) {
            $errors[] = 'School name is too long.';
        }
        if (mb_strlen($address) > 500 || mb_strlen($city) > 60) {
            $errors[] = 'Address or city is too long.';
        }
        if ($pin !== '' && !preg_match('/^\d{6}$/', $pin)) {
            $errors[] = 'Enter a valid 6-digit PIN code, or leave it blank.';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET name = ?, phone = ?, school_name = ?, address = ?, city = ?, pincode = ? WHERE id = ?')
                ->execute([$name, $phone, $school !== '' ? $school : null, $address !== '' ? $address : null, $city !== '' ? $city : null, $pin !== '' ? $pin : null, (int) $user['id']]);
            flash('success', 'Profile updated.');
            redirect(url('account.php'));
        }
    }
    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        if (!password_verify($current, (string) $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (!valid_password($password)) {
            $errors[] = 'Use at least 8 characters with a letter and a number.';
        }
        if (!hash_equals($password, $confirm)) {
            $errors[] = 'New passwords do not match.';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
            flash('success', 'Password updated.');
            redirect(url('account.php'));
        }
    }
}

$pageTitle = 'My account';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head"><h1>Hello, <?= e($user['name']) ?></h1></div>
    <?php if ($errors): ?><ul class="errors"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <div class="menu-list" style="margin-bottom:16px">
        <a href="<?= e(url('orders.php')) ?>">Orders and tracking</a>
        <a href="<?= e(url('wishlist.php')) ?>">Wishlist</a>
        <a href="<?= e(url('bulk-order.php')) ?>">Bulk order for a school</a>
        <?php if ($user['role'] === 'admin'): ?><a href="<?= e(url('admin/index.php')) ?>">Open admin panel</a><?php endif; ?>
        <?php if ($user['role'] === 'vendor'): ?><a href="<?= e(url('vendor/index.php')) ?>">Vendor stock</a><?php endif; ?>
        <?php if ($user['role'] === 'store'): ?><a href="<?= e(url('store/index.php')) ?>">Dark store orders</a><?php endif; ?>
    </div>
    <div class="two">
        <form method="post" class="panel">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <h2>School and delivery</h2>
            <label class="field">Name<input name="name" required value="<?= e($user['name']) ?>"></label>
            <label class="field">Email<input value="<?= e($user['email']) ?>" disabled></label>
            <label class="field">Mobile<input name="mobile" required value="<?= e($user['phone']) ?>"></label>
            <label class="field">School<input name="school_name" value="<?= e($user['school_name'] ?? '') ?>"></label>
            <label class="field">Address<textarea name="address" rows="3"><?= e($user['address'] ?? '') ?></textarea></label>
            <label class="field">City<input name="city" value="<?= e($user['city'] ?? '') ?>"></label>
            <label class="field">PIN code<input name="pincode" value="<?= e($user['pincode'] ?? '') ?>"></label>
            <button class="btn btn-green" type="submit">Save profile</button>
        </form>
        <form method="post" class="panel">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <h2>Password</h2>
            <label class="field">Current password<input type="password" name="current_password" required></label>
            <label class="field">New password<input type="password" name="password" required></label>
            <label class="field">Confirm new password<input type="password" name="confirm" required></label>
            <button class="btn btn-line" type="submit">Update password</button>
            <div style="margin-top:18px">
                <button class="text-btn" type="submit" form="logout-form">Logout</button>
            </div>
        </form>
    </div>
    <form id="logout-form" method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?></form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
