<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Home';
$starter = product_by_slug('preschool-starter-kit');
$best = catalog(['bestseller' => true, 'limit' => 6, 'sort' => 'popular']);
$trending = catalog(['trending' => true, 'limit' => 6, 'sort' => 'popular']);
$newest = catalog(['new' => true, 'limit' => 6, 'sort' => 'new']);
$kits = catalog(['kits' => true, 'limit' => 4, 'sort' => 'name']);

require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow"><?= e(APP_DOMAIN) ?></p>
            <h1><?= e(APP_TAGLINE) ?></h1>
            <p class="lede">Toys, books, art supplies and class kits for Nursery, Jr. KG and Sr. KG. Free delivery on orders of <?= money(FREE_DELIVERY_ABOVE) ?> and above.</p>
            <div class="hero-actions">
                <a class="btn btn-green" href="<?= e(url('kits.php')) ?>">Shop class kits</a>
                <a class="btn btn-ghost" href="<?= e(url('category.php')) ?>">Browse categories</a>
            </div>
        </div>
        <?php if ($starter): ?>
            <a class="hero-kit" href="<?= e(url('product.php?slug=' . rawurlencode($starter['slug']))) ?>">
                <span><?= e($starter['emoji']) ?></span>
                <strong><?= e($starter['name']) ?></strong>
                <p class="muted"><?= e($starter['short_desc']) ?></p>
                <p class="price"><?= money($starter['price']) ?> <span class="was"><?= money($starter['mrp']) ?></span></p>
            </a>
        <?php endif; ?>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Best sellers</h2>
            <a href="<?= e(url('category.php?tag=bestseller')) ?>">See all</a>
        </div>
        <?php render_product_grid($best['items']); ?>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Class kits</h2>
            <a href="<?= e(url('kits.php')) ?>">See all</a>
        </div>
        <div class="kit-grid">
            <?php foreach ($kits['items'] as $kit): ?>
                <a class="kit-card" href="<?= e(url('product.php?slug=' . rawurlencode($kit['slug']))) ?>">
                    <span class="big"><?= e($kit['emoji']) ?></span>
                    <strong><?= e($kit['name']) ?></strong>
                    <span class="muted small"><?= e($kit['short_desc']) ?></span>
                    <span class="price"><?= money($kit['price']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>Trending</h2>
            <a href="<?= e(url('category.php?tag=trending')) ?>">See all</a>
        </div>
        <?php render_product_grid($trending['items']); ?>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>New arrivals</h2>
            <a href="<?= e(url('category.php?tag=new')) ?>">See all</a>
        </div>
        <?php render_product_grid($newest['items']); ?>
    </section>

    <section class="trust">
        <div><strong>School delivery</strong><span>Send orders to the school office or home.</span></div>
        <div><strong>Free over <?= money(FREE_DELIVERY_ABOVE) ?></strong><span>Otherwise a flat <?= money(DELIVERY_FEE) ?> delivery fee.</span></div>
        <div><strong>Class kits</strong><span>Ready packs for Nursery, Jr. KG and Sr. KG.</span></div>
        <div><strong>Bulk orders</strong><span>Schools can request a full class list in one go.</span></div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
