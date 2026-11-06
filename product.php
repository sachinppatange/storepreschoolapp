<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$product = product_by_slug(trim((string) ($_GET['slug'] ?? '')));
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="wrap"><div class="empty"><h1>Product not found</h1><a class="btn btn-green" href="' . e(url()) . '">Back to home</a></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'];
$discount = discount_percent($product['price'], $product['mrp']);
$related = catalog(['category_id' => (int) $product['category_id'], 'limit' => 6]);
$relatedItems = array_values(array_filter($related['items'], static fn (array $item): bool => (int) $item['id'] !== (int) $product['id']));
$maxQty = min(50, (int) $product['stock']);

require __DIR__ . '/includes/header.php';
?>
<div class="wrap product-page">
    <div class="product-hero" style="background:<?= e(category_tint((int) $product['category_id'])) ?>">
        <?php if (!empty($product['image'])): ?>
            <img src="<?= e(url('uploads/products/' . basename((string) $product['image']))) ?>" alt="<?= e($product['name']) ?>">
        <?php else: ?>
            <span><?= e($product['emoji']) ?></span>
        <?php endif; ?>
    </div>
    <div>
        <p class="muted"><a href="<?= e(url('category.php?slug=' . rawurlencode($product['category_slug']))) ?>"><?= e($product['category_name']) ?></a> · <?= e($product['unit_label']) ?></p>
        <h1><?= e($product['name']) ?></h1>
        <p class="lede"><?= e($product['short_desc']) ?></p>
        <?php if (is_school_buyer()): ?><p class="small" style="color:#0c831f;font-weight:800">School price · <?= (int) school_b2b_percent() ?>% off the parent price</p><?php endif; ?>
        <p class="price" style="font-size:1.6rem"><?= money($product['price']) ?>
            <?php if ($discount): ?><span class="was"><?= money($product['mrp']) ?></span> <span class="off"><?= $discount ?>% OFF</span><?php endif; ?>
        </p>
        <p class="stock<?= current_delivery() && (int) $product['stock'] <= 0 ? ' out' : '' ?>">
            <?php if (!current_delivery()): ?>
                Choose a delivery area to see what this dark store can pack.
            <?php elseif ((int) $product['stock'] <= 0): ?>
                Out of stock in this area
            <?php elseif ((int) $product['stock'] <= 5): ?>
                Only <?= (int) $product['stock'] ?> left in this area
            <?php else: ?>
                In stock here · <?= (int) $product['stock'] ?> available
            <?php endif; ?>
        </p>
        <p><?= nl2br(e($product['description'])) ?></p>
        <?php if (!current_delivery()): ?>
            <button class="btn btn-green" type="button" data-need-location>Choose a delivery area</button>
        <?php elseif ($maxQty > 0): ?>
            <form method="post" action="<?= e(url('cart.php')) ?>" class="qty-row js-cart-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="return" value="<?= e(current_path()) ?>">
                <label class="sr-only" for="qty">Quantity</label>
                <input id="qty" type="number" name="qty" min="1" max="<?= $maxQty ?>" value="1">
                <button class="btn btn-green" type="submit">Add to cart</button>
            </form>
        <?php else: ?>
            <button class="btn btn-ghost" type="button" disabled>Out of stock</button>
        <?php endif; ?>
        <form method="post" action="<?= e(url('wishlist.php')) ?>" class="js-wish-form" style="margin-top:10px">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="return" value="<?= e(current_path()) ?>">
            <button class="btn btn-ghost wish<?= in_wishlist((int) $product['id']) ? ' on' : '' ?>" type="submit">Save to wishlist</button>
        </form>
        <p class="small muted">SKU <?= e($product['sku']) ?></p>
    </div>
</div>
<?php if ($relatedItems): ?>
    <div class="wrap section">
        <div class="section-head"><h2>More in <?= e($product['category_name']) ?></h2></div>
        <?php render_product_grid(array_slice($relatedItems, 0, 6)); ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
