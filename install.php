<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SPSTORESESSID');
    session_start();
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function install_database_exists(): bool
{
    try {
        db()->query('SELECT 1 FROM users LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

$error = '';
$done = false;
$already = install_database_exists();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        $error = 'Session expired. Refresh and try again.';
    } elseif ($already && (($_POST['confirm'] ?? '') !== 'RESET')) {
        $error = 'The database already has data. Type RESET to install again. This deletes existing store data.';
    } else {
        try {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
            if ($mysqli->connect_error) {
                throw new RuntimeException($mysqli->connect_error);
            }
            $mysqli->set_charset('utf8mb4');
            $sql = file_get_contents(__DIR__ . '/database/storepreschool.sql');
            if ($sql === false) {
                throw new RuntimeException('Could not read the SQL file.');
            }
            if (!$mysqli->multi_query($sql)) {
                throw new RuntimeException($mysqli->error);
            }
            do {
                if ($result = $mysqli->store_result()) {
                    $result->free();
                }
            } while ($mysqli->more_results() && $mysqli->next_result());
            if ($mysqli->error) {
                throw new RuntimeException($mysqli->error);
            }
            $mysqli->close();
            $done = true;
            $already = true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Store Preschool</title>
    <link href="<?= e(url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="panel" style="max-width:640px;margin:32px auto">
    <h1>Install Store Preschool</h1>
    <p>This creates the <strong><?= e(DB_NAME) ?></strong> database, tables and sample preschool products.</p>
    <?php if ($done): ?>
        <div class="alert ok">The store is ready.</div>
        <p><a class="btn" href="<?= e(url()) ?>">Open the store</a> <a class="btn alt" href="<?= e(url('admin/login.php')) ?>">Admin login</a></p>
        <p>Parent: parent@example.com / Parent@123<br>Admin: admin@storepreschoolapp.in / Admin@123</p>
    <?php else: ?>
        <?php if ($already): ?><div class="alert ok">A store database is already installed.</div><?php endif; ?>
        <?php if ($error): ?><div class="alert bad"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($already): ?>
                <label class="field">Type RESET to replace all store data<input name="confirm" autocomplete="off"></label>
            <?php endif; ?>
            <button class="btn" type="submit"><?= $already ? 'Reinstall database' : 'Install database' ?></button>
        </form>
        <p>Database user <strong><?= e(DB_USER) ?></strong> on <?= e(DB_HOST) ?>:<?= (int) DB_PORT ?>. Change this in <code>config/config.php</code> if your MySQL password is not empty.</p>
    <?php endif; ?>
</div>
</body>
</html>
