<?php
declare(strict_types=1);
$active = $active ?? '';
$pageTitle = $pageTitle ?? 'Admin';
$adminUser = current_user();
$unreadMessages = (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
$links = [
    'dashboard' => ['Dashboard', 'index.php'],
    'products' => ['Products', 'products.php'],
    'categories' => ['Categories', 'categories.php'],
    'orders' => ['Orders', 'orders.php'],
    'customers' => ['Customers', 'customers.php'],
    'coupons' => ['Coupons', 'coupons.php'],
    'messages' => ['Messages' . ($unreadMessages ? ' (' . $unreadMessages . ')' : ''), 'messages.php'],
    'reports' => ['Reports', 'reports.php'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · Admin</title>
    <link rel="icon" href="<?= e(url('assets/favicon.svg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= e(url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
    <aside class="side" id="side">
        <div class="brand">Store Preschool</div>
        <?php foreach ($links as $key => [$label, $href]): ?>
            <a class="<?= $active === $key ? 'on' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <a href="<?= e(url()) ?>">View store</a>
        <form method="post" action="logout.php"><?= csrf_field() ?><button class="btn alt" type="submit">Logout</button></form>
    </aside>
    <div class="admin-main">
        <div class="top">
            <div>
                <button class="btn alt menu-toggle" type="button" onclick="document.getElementById('side').classList.toggle('open')">Menu</button>
                <h1 style="display:inline;font-size:1.4rem;margin:0"><?= e($pageTitle) ?></h1>
            </div>
            <span><?= e($adminUser['name'] ?? '') ?></span>
        </div>
        <?php if ($message = flash('success')): ?><div class="alert ok"><?= e($message) ?></div><?php endif; ?>
        <?php if ($message = flash('error')): ?><div class="alert bad"><?= e($message) ?></div><?php endif; ?>
