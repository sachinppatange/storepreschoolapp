<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (!current_user()) {
        $next = safe_return((string) ($_POST['return'] ?? ''), url('wishlist.php'));
        redirect(url('login.php?next=' . urlencode($next)));
    }
    $result = wishlist_toggle((int) ($_POST['product_id'] ?? 0));
    flash($result['ok'] ? 'success' : 'error', $result['ok'] ? $result['message'] : $result['error']);
    redirect(safe_return((string) ($_POST['return'] ?? ''), url('wishlist.php')));
}

require_login();
$pageTitle = 'Wishlist';
$items = wishlist_items();
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head"><h1>Wishlist</h1></div>
    <?php if (!$items): ?>
        <div class="empty">
            <h2>Nothing saved yet</h2>
            <p class="muted">Tap the heart on a product to keep it here.</p>
            <a class="btn btn-green" href="<?= e(url('category.php')) ?>">Browse products</a>
        </div>
    <?php else: ?>
        <?php render_product_grid($items); ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
