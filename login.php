<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$next = safe_return((string) ($_GET['next'] ?? $_POST['next'] ?? ''), url('account.php'));
if (current_user()) {
    redirect($next);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $result = attempt_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
    if ($result['ok']) {
        $user = $result['user'];
        if (in_array($user['role'], ['admin', 'vendor', 'store'], true) && ($next === url('account.php') || $next === url())) {
            $next = role_home($user);
        }
        redirect($next);
    }
    $error = $result['error'];
    remember_old($_POST, ['email']);
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap" style="max-width:480px;padding:20px 0 32px">
    <div class="panel">
        <h1>Login</h1>
        <p class="muted">Use the account that tracks your school orders.</p>
        <?php if (DEMO_MODE): ?>
            <div class="demo-box small">
                <strong>Demo parent</strong><br>parent@example.com · Parent@123<br>
                <strong>Demo admin</strong><br>admin@storepreschoolapp.in · Admin@123<br>
                <strong>Vendor</strong><br>vendor@storepreschoolapp.in · Vendor@123<br>
                <strong>Dark store</strong><br>store@storepreschoolapp.in · Store@123
            </div>
        <?php endif; ?>
        <?php if ($error): ?><div class="alert-bad"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <label class="field">Email<input type="email" name="email" required autocomplete="username" value="<?= old('email') ?>"></label>
            <label class="field">Password<input type="password" name="password" required autocomplete="current-password"></label>
            <button class="btn btn-green btn-block" type="submit">Login</button>
        </form>
        <p>New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
