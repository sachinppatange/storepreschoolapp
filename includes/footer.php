<?php
declare(strict_types=1);
$simpleHeader = $simpleHeader ?? false;
$hideDock = $hideDock ?? false;
$cartSummary = cart_summary();
$cartCount = (int) $cartSummary['count'];
?>
</main>
<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <strong>Store Preschool</strong>
            <p>Toys, books, classroom kits and school essentials for Nursery, Jr. KG and Sr. KG.</p>
        </div>
        <div>
            <strong>Shop</strong>
            <a href="<?= e(url('category.php')) ?>">All categories</a>
            <a href="<?= e(url('kits.php')) ?>">Class kits</a>
            <a href="<?= e(url('print.php')) ?>">Print on demand</a>
            <a href="<?= e(url('bulk-order.php')) ?>">Bulk order for schools</a>
            <a href="<?= e(url('track.php')) ?>">Track an order</a>
        </div>
        <div>
            <strong>Help</strong>
            <a href="<?= e(url('page.php?p=about')) ?>">About</a>
            <a href="<?= e(url('page.php?p=shipping')) ?>">Delivery</a>
            <a href="<?= e(url('page.php?p=contact')) ?>">Contact</a>
            <a href="<?= e(url('page.php?p=privacy')) ?>">Privacy</a>
            <a href="<?= e(url('page.php?p=terms')) ?>">Terms</a>
        </div>
    </div>
    <div class="wrap footer-base">© <?= date('Y') ?> <?= e(APP_DOMAIN) ?></div>
</footer>

<?php if (!$hideDock): ?>
    <a class="cart-dock" id="cartDock" href="<?= e(url('cart.php')) ?>"<?= $cartCount ? '' : ' hidden' ?>>
        <span><strong class="js-cart-count-label"><?= $cartCount ?> item<?= $cartCount === 1 ? '' : 's' ?></strong> · <span class="js-cart-total"><?= money($cartSummary['total']) ?></span></span>
        <span class="dock-btn">View cart</span>
    </a>
<?php endif; ?>

<?php if (!$simpleHeader): ?>
    <nav class="bottom-nav" aria-label="Mobile">
        <a href="<?= e(url()) ?>"><span>🏠</span>Home</a>
        <a href="<?= e(url('category.php')) ?>"><span>🗂️</span>Categories</a>
        <a href="<?= e(url('kits.php')) ?>"><span>🎁</span>Kits</a>
        <a href="<?= e(url('cart.php')) ?>"><span>🛒</span>Cart</a>
        <a href="<?= e(url($user ? 'account.php' : 'login.php')) ?>"><span>👤</span>Account</a>
    </nav>
<?php endif; ?>

<div id="toast" class="toast-msg" role="status"></div>
<script>
window.STORE = {
    base: <?= json_encode(url(''), JSON_UNESCAPED_SLASHES) ?>,
    csrf: <?= json_encode(csrf_token()) ?>,
    cartCount: <?= (int) $cartCount ?>,
    cartTotal: <?= json_encode(money($cartSummary['total'])) ?>
};
</script>
<script src="<?= e(url('assets/js/app.js?v=3')) ?>"></script>
<?php clear_old(); ?>
</body>
</html>
