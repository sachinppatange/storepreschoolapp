<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = normalize_phone((string) ($_POST['phone'] ?? ''));
    $school = trim((string) ($_POST['school_name'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!preg_match("/^[\\p{L}\\s.'-]{2,80}$/u", $name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter the contact name and a valid email.');
    } elseif (!valid_phone($phone)) {
        flash('error', 'Enter a valid 10-digit mobile number.');
    } elseif (mb_strlen($school) < 2 || mb_strlen($school) > 180 || mb_strlen($city) < 2) {
        flash('error', 'Enter the school name and city.');
    } elseif (!valid_password($password)) {
        flash('error', 'Use a password of at least 8 characters with a letter and a number.');
    } else {
        $exists = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            flash('error', 'That email is already used.');
        } else {
            db()->prepare('INSERT INTO users (name, email, phone, password_hash, role, school_name, city) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), 'school', $school, $city]);
            flash('success', $school . ' can log in now. Share the password with the school once.');
        }
    }
    redirect(url('admin/schools.php'));
}

$schools = db()->query("SELECT id, name, email, phone, school_name, city, is_active, created_at FROM users WHERE role = 'school' ORDER BY school_name, name")->fetchAll();
$active = 'schools';
$pageTitle = 'Schools';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>School logins</h1></div>
<p class="muted">A school login sees every product at <?= (int) school_b2b_percent() ?>% below the parent price and can place a bulk order. Parents keep the regular price.</p>
<div class="layout-2">
    <form method="post" class="panel">
        <?= csrf_field() ?>
        <h2>Add a school</h2>
        <label class="field">Contact name<input name="name" required></label>
        <label class="field">School name<input name="school_name" required></label>
        <label class="field">Email<input type="email" name="email" required></label>
        <label class="field">Mobile<input name="phone" inputmode="numeric" required></label>
        <label class="field">City<input name="city" required></label>
        <label class="field">Password<input type="text" name="password" required autocomplete="off"></label>
        <button class="btn" type="submit">Create school login</button>
    </form>
    <div class="panel">
        <h2>Schools</h2>
        <?php if (!$schools): ?><p>No school logins yet.</p><?php endif; ?>
        <?php foreach ($schools as $school): ?>
            <p><strong><?= e($school['school_name']) ?></strong><br><?= e($school['name']) ?> · <?= e($school['email']) ?><br><?= e($school['phone']) ?> · <?= e($school['city']) ?><?= (int) $school['is_active'] ? '' : ' · disabled' ?></p>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
