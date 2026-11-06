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

function install_can_connect(): bool
{
    try {
        db()->query('SELECT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function install_is_live_host(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    return $host === 'store.preschoolapp.in' || str_starts_with($host, 'store.preschoolapp.in:');
}

function install_write_live_config(string $password, string $dbHost): void
{
    $content = "<?php\ndeclare(strict_types=1);\n\n"
        . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
        . "define('DB_PORT', 3306);\n"
        . "define('DB_NAME', 'u750208840_storepredb');\n"
        . "define('DB_USER', 'u750208840_storepreuser');\n"
        . "define('DB_PASS', " . var_export($password, true) . ");\n"
        . "define('APP_DEBUG', false);\n";
    $path = __DIR__ . '/config/config.local.php';
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Could not save the database settings in config/config.local.php.');
    }
}

$error = '';
$done = false;
$canConnect = install_can_connect();
$already = $canConnect && install_database_exists();
$askLivePassword = !$canConnect && install_is_live_host();

function install_sql(): string
{
    $sql = file_get_contents(__DIR__ . '/database/storepreschool.sql');
    if ($sql === false) {
        throw new RuntimeException('Could not read the SQL file.');
    }
    // Shared hosting already has the database. Do not CREATE DATABASE or switch away from it.
    $sql = preg_replace('/^\s*CREATE DATABASE\b.*?;\s*$/mi', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*USE\s+.+?;\s*$/mi', '', $sql) ?? $sql;
    return $sql;
}

function install_connection(?array $live = null): mysqli
{
    $host = $live['host'] ?? DB_HOST;
    $user = $live['user'] ?? DB_USER;
    $pass = $live['pass'] ?? DB_PASS;
    $name = $live['name'] ?? DB_NAME;
    $port = (int) ($live['port'] ?? DB_PORT);

    $mysqli = @new mysqli($host, $user, $pass, $name, $port);
    if (!$mysqli->connect_error) {
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    }

    $denied = $mysqli->connect_error;
    if ($live !== null) {
        throw new RuntimeException($denied);
    }
    $bootstrap = @new mysqli($host, $user, $pass, '', $port);
    if ($bootstrap->connect_error) {
        throw new RuntimeException($denied);
    }

    $name = str_replace('`', '', DB_NAME);
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        $bootstrap->close();
        throw new RuntimeException('Invalid database name.');
    }
    if (!$bootstrap->query(
        "CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    )) {
        $createError = $bootstrap->error;
        $bootstrap->close();
        throw new RuntimeException($createError !== '' ? $createError : $denied);
    }
    $bootstrap->close();

    $mysqli = new mysqli($host, $user, $pass, $name, $port);
    if ($mysqli->connect_error) {
        throw new RuntimeException($mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        $error = 'Session expired. Refresh and try again.';
    } elseif ($already && !is_admin()) {
        $error = 'The store is already installed.';
    } elseif ($already && (($_POST['confirm'] ?? '') !== 'RESET')) {
        $error = 'The database already has data. Type RESET to install again. This deletes existing store data.';
    } else {
        try {
            $live = null;
            if (($_POST['save_live_db'] ?? '') === '1') {
                if (!install_is_live_host()) {
                    throw new RuntimeException('This database form is only for the live site.');
                }
                $password = (string) ($_POST['db_pass'] ?? '');
                if ($password === '' || strlen($password) > 128 || str_contains($password, "\0")) {
                    throw new RuntimeException('Enter the Hostinger database password.');
                }
                $dbHost = 'localhost';
                try {
                    $probe = @new mysqli($dbHost, 'u750208840_storepreuser', $password, 'u750208840_storepredb', 3306);
                    if ($probe->connect_error) {
                        $dbHost = '127.0.0.1';
                        $probe = @new mysqli($dbHost, 'u750208840_storepreuser', $password, 'u750208840_storepredb', 3306);
                    }
                    if ($probe->connect_error) {
                        throw new RuntimeException('That database password was not accepted. Use the password for u750208840_storepreuser.');
                    }
                    $probe->close();
                } catch (Throwable $e) {
                    if ($e instanceof RuntimeException) {
                        throw $e;
                    }
                    if (stripos($e->getMessage(), 'Access denied') !== false) {
                        throw new RuntimeException('That database password was not accepted. Use the password for u750208840_storepreuser.');
                    }
                    throw new RuntimeException('Could not reach the database.');
                }
                install_write_live_config($password, $dbHost);
                $live = [
                    'host' => $dbHost,
                    'user' => 'u750208840_storepreuser',
                    'pass' => $password,
                    'name' => 'u750208840_storepredb',
                    'port' => 3306,
                ];
            }
            $mysqli = install_connection($live);
            $sql = install_sql();
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
    <p>This fills <strong><?= e($askLivePassword ? 'u750208840_storepredb' : DB_NAME) ?></strong> with the store tables and sample preschool products.</p>
    <?php if ($done): ?>
        <div class="alert ok">The store is ready.</div>
        <p><a class="btn" href="<?= e(url()) ?>">Open the store</a> <a class="btn alt" href="<?= e(url('admin/login.php')) ?>">Admin login</a></p>
        <p>Parent: parent@example.com / Parent@123<br>Admin: admin@storepreschoolapp.in / Admin@123</p>
    <?php else: ?>
        <?php if ($already): ?><div class="alert ok">A store database is already installed.</div><?php endif; ?>
        <?php if ($error): ?><div class="alert bad"><?= e($error) ?></div><?php endif; ?>
        <?php if ($askLivePassword): ?>
            <p>The shop address is <strong>https://store.preschoolapp.in/</strong>. Enter the password for database user <strong>u750208840_storepreuser</strong>. It is saved on the server only, then the sample products are installed.</p>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="save_live_db" value="1">
                <label class="field">Database password<input type="password" name="db_pass" autocomplete="current-password" required></label>
                <button class="btn" type="submit">Save and install</button>
            </form>
        <?php elseif ($already && !is_admin()): ?>
            <p><a class="btn" href="<?= e(url()) ?>">Open the store</a></p>
        <?php else: ?>
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($already): ?>
                <label class="field">Type RESET to replace all store data<input name="confirm" autocomplete="off"></label>
            <?php endif; ?>
            <button class="btn" type="submit"><?= $already ? 'Reinstall database' : 'Install database' ?></button>
        </form>
        <p>Database user <strong><?= e(DB_USER) ?></strong> on <?= e(DB_HOST) ?>:<?= (int) DB_PORT ?>.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
