<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? APP_NAME;
$metaDesc = $metaDesc ?? 'Parents can order school supplies for kids and have them delivered home. Schools can place a class order too.';
$simpleHeader = $simpleHeader ?? false;
$hideDock = $hideDock ?? false;
$user = current_user();
$categories = categories_all();
$cartSummary = cart_summary();
$cartCount = (int) $cartSummary['count'];
$searchValue = trim((string) ($_GET['q'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($metaDesc) ?>">
    <meta name="theme-color" content="#0c831f">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" href="<?= e(url('assets/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css?v=4')) ?>" rel="stylesheet">
</head>
<body class="<?= $cartCount > 0 && !$hideDock ? 'has-dock' : '' ?>">
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
    <div class="wrap header-top">
        <a class="logo" href="<?= e(url()) ?>">
            <span class="logo-mark">S</span>
            <span class="logo-text">
                <strong>Store Preschool</strong>
                <small><?= e(APP_DOMAIN) ?></small>
            </span>
        </a>
        <?php if (!$simpleHeader): ?>
            <form class="search" action="<?= e(url('search.php')) ?>" method="get" role="search">
                <label class="sr-only" for="q">Search products</label>
                <input id="q" name="q" value="<?= e($searchValue) ?>" placeholder="Search for toys, books, kits..." autocomplete="off">
                <div class="suggest" hidden></div>
                <button type="submit">Search</button>
            </form>
        <?php else: ?>
            <div class="simple-title"><?= e($pageTitle) ?></div>
        <?php endif; ?>
        <div class="header-actions">
            <?php if ($user): ?>
                <a class="header-link" href="<?= e(role_home($user)) ?>">
                    <?= e(match ($user['role']) {
                        'admin' => 'Admin',
                        'vendor' => 'Vendor',
                        'store' => 'Dark store',
                        default => strtok($user['name'], ' ') ?: 'Account',
                    }) ?>
                </a>
            <?php else: ?>
                <a class="header-link" href="<?= e(url('login.php')) ?>">Login</a>
            <?php endif; ?>
            <a class="header-cart" href="<?= e(url('cart.php')) ?>">
                Cart
                <span class="count js-cart-count"<?= $cartCount ? '' : ' hidden' ?>><?= $cartCount ?></span>
            </a>
        </div>
    </div>
    <?php if (!$simpleHeader): ?>
        <nav class="cat-nav" aria-label="Categories">
            <div class="wrap cat-scroll">
                <a href="<?= e(url('kits.php')) ?>"><span>🎁</span>Kits</a>
                <?php foreach ($categories as $category): ?>
                    <a href="<?= e(url('category.php?slug=' . rawurlencode((string) $category['slug']))) ?>">
                        <span><?= e($category['emoji']) ?></span><?= e($category['name']) ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?= e(url('print.php')) ?>"><span>🖨️</span>Print</a>
                <a href="<?= e(url('bulk-order.php')) ?>"><span>🏫</span>Bulk order</a>
            </div>
        </nav>
    <?php endif; ?>
    <?php render_delivery_bar(); ?>
</header>
<?php if ($message = flash('success')): ?>
    <div class="wrap"><div class="alert-ok"><?= e($message) ?></div></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="wrap"><div class="alert-bad"><?= e($message) ?></div></div>
<?php endif; ?>
<main id="main">
