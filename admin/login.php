<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

if (is_admin()) {
    redirect(url('admin/index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $result = attempt_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''), true);
    if ($result['ok']) {
        redirect(url('admin/index.php'));
    }
    $error = $result['error'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login</title>
    <link href="<?= e(url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="panel" style="max-width:420px;margin:48px auto">
    <h1>Admin login</h1>
    <?php if (DEMO_MODE): ?><p>Demo: admin@storepreschoolapp.in · Admin@123</p><?php endif; ?>
    <?php if ($error): ?><div class="alert bad"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label class="field">Email<input type="email" name="email" required></label>
        <label class="field">Password<input type="password" name="password" required></label>
        <button class="btn" type="submit">Login</button>
    </form>
    <p><a href="<?= e(url()) ?>">Back to store</a></p>
</div>
</body>
</html>
