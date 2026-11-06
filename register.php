<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect(url('account.php'));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = normalize_phone((string) ($_POST['mobile'] ?? ''));
    $school = trim((string) ($_POST['school_name'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    if (!preg_match("/^[\\p{L}\\s.'-]{2,80}$/u", $name)) {
        $errors[] = 'Enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!valid_phone($phone)) {
        $errors[] = 'Enter a valid 10-digit mobile number.';
    }
    if ($school !== '' && (mb_strlen($school) < 2 || mb_strlen($school) > 180)) {
        $errors[] = 'School name looks too short or too long.';
    }
    if (!valid_password($password)) {
        $errors[] = 'Use at least 8 characters with a letter and a number.';
    }
    if (!hash_equals($password, $confirm)) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$errors) {
        $exists = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $errors[] = 'An account with this email already exists. Login instead.';
        }
    }
    if (!$errors) {
        db()->prepare('INSERT INTO users (name, email, phone, password_hash, role, school_name) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), 'customer', $school !== '' ? $school : null]);
        attempt_login($email, $password);
        flash('success', 'Account created. Welcome to Store Preschool.');
        redirect(url('account.php'));
    }
    remember_old($_POST, ['name', 'email', 'mobile', 'school_name']);
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap" style="max-width:520px;padding:20px 0 32px">
    <div class="panel">
        <h1>Create account</h1>
        <?php if ($errors): ?><ul class="errors"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <label class="field">Full name<input name="name" required value="<?= old('name') ?>"></label>
            <label class="field">Email<input type="email" name="email" required value="<?= old('email') ?>"></label>
            <label class="field">Mobile<input name="mobile" inputmode="numeric" required value="<?= old('mobile') ?>"></label>
            <label class="field">School name <span class="muted">(optional)</span><input name="school_name" value="<?= old('school_name') ?>"></label>
            <label class="field">Password<input type="password" name="password" required autocomplete="new-password"></label>
            <label class="field">Confirm password<input type="password" name="confirm" required autocomplete="new-password"></label>
            <button class="btn btn-green btn-block" type="submit">Create account</button>
        </form>
        <p>Already registered? <a href="<?= e(url('login.php')) ?>">Login</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
