<?php
declare(strict_types=1);

function render_product_card(array $product, int $inCart = 0): void
{
    $id = (int) $product['id'];
    $discount = discount_percent($product['price'], $product['mrp']);
    $stock = (int) $product['stock'];
    $located = current_delivery() !== null;
    $soldOut = $located && $stock <= 0;
    $href = url('product.php?slug=' . rawurlencode((string) $product['slug']));
    $return = current_path();
    ?>
    <article class="pcard" data-product-id="<?= $id ?>" data-stock="<?= $stock ?>">
        <form method="post" action="<?= e(url('wishlist.php')) ?>" class="wish-form js-wish-form">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= $id ?>">
            <input type="hidden" name="return" value="<?= e($return) ?>">
            <button class="wish<?= in_wishlist($id) ? ' on' : '' ?>" type="submit" aria-label="Save <?= e($product['name']) ?> to wishlist">♡</button>
        </form>
        <div class="pcard-media" style="background:<?= e(category_tint((int) $product['category_id'])) ?>">
            <a class="media-link" href="<?= e($href) ?>" aria-label="<?= e($product['name']) ?>">
                <?php if (!empty($product['image'])): ?>
                    <img src="<?= e(url('uploads/products/' . basename((string) $product['image']))) ?>" alt="<?= e($product['name']) ?>">
                <?php else: ?>
                    <span class="emoji" aria-hidden="true"><?= e($product['emoji']) ?></span>
                <?php endif; ?>
            </a>
            <div class="pcard-action">
                <?php if ($soldOut): ?>
                    <button class="btn-add" type="button" disabled>Sold out</button>
                <?php elseif (!$located): ?>
                    <button class="btn-add" type="button" data-need-location>ADD</button>
                <?php elseif ($inCart > 0): ?>
                    <form method="post" action="<?= e(url('cart.php')) ?>" class="qty-stepper js-cart-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                        <input type="hidden" name="return" value="<?= e($return) ?>">
                        <button type="submit" name="qty" value="<?= $inCart - 1 ?>" aria-label="Decrease quantity">−</button>
                        <span><?= $inCart ?></span>
                        <button type="submit" name="qty" value="<?= $inCart + 1 ?>" aria-label="Increase quantity">+</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= e(url('cart.php')) ?>" class="js-cart-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $id ?>">
                        <input type="hidden" name="qty" value="1">
                        <input type="hidden" name="return" value="<?= e($return) ?>">
                        <button class="btn-add" type="submit">ADD</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="pcard-body">
            <p class="unit"><?= e($product['unit_label']) ?></p>
            <h3><a href="<?= e($href) ?>"><?= e($product['name']) ?></a></h3>
            <div class="price-row">
                <strong><?= money($product['price']) ?></strong>
                <?php if ($discount > 0): ?>
                    <s><?= money($product['mrp']) ?></s>
                    <span class="off"><?= $discount ?>% OFF</span>
                <?php endif; ?>
            </div>
            <p class="stock<?= $soldOut ? ' out' : '' ?>">
                <?php if (!$located): ?>
                    Choose a delivery area
                <?php elseif ($soldOut): ?>
                    Out of stock here
                <?php elseif ($stock <= 5): ?>
                    Only <?= $stock ?> left here
                <?php else: ?>
                    In stock here
                <?php endif; ?>
            </p>
        </div>
    </article>
    <?php
}

function render_delivery_bar(): void
{
    $areas = delivery_areas();
    $current = current_delivery();
    $pin = (string) ($current['pincode'] ?? '');
    ?>
    <div class="deliver-bar<?= $pin === '' ? ' needs-pin' : '' ?>">
        <form class="wrap" method="post" action="<?= e(url('location.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="return" value="<?= e(current_path()) ?>">
            <label for="deliver-pin">Deliver to</label>
            <select id="deliver-pin" name="pincode" required>
                <option value=""<?= $pin === '' ? ' selected' : '' ?> disabled>Choose an area we deliver to</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= e($area['pincode']) ?>"<?= $pin === $area['pincode'] ? ' selected' : '' ?>><?= e($area['city']) ?> · <?= e($area['name']) ?> · <?= e($area['pincode']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-green" type="submit">Set</button>
        </form>
    </div>
    <?php
}

function render_product_grid(array $products): void
{
    if (!$products) {
        echo '<div class="empty"><p>No products match this view yet.</p></div>';
        return;
    }
    $map = cart_qty_map();
    echo '<div class="product-grid">';
    foreach ($products as $product) {
        render_product_card($product, $map[(int) $product['id']] ?? 0);
    }
    echo '</div>';
}

function render_totals(array $summary): void
{
    ?>
    <dl class="totals">
        <div><dt>Subtotal</dt><dd><?= money($summary['subtotal']) ?></dd></div>
        <div><dt>Discount<?= !empty($summary['coupon']['code']) ? ' (' . e($summary['coupon']['code']) . ')' : '' ?></dt><dd><?= $summary['discount'] > 0 ? '−' . money($summary['discount']) : money(0) ?></dd></div>
        <div><dt>Delivery</dt><dd><?= (float) $summary['delivery'] > 0 ? money($summary['delivery']) : 'FREE' ?></dd></div>
        <div class="grand"><dt>To pay</dt><dd><?= money($summary['total']) ?></dd></div>
    </dl>
    <?php
}

function render_pagination(int $page, int $pages): void
{
    if ($pages <= 1) {
        return;
    }
    echo '<nav class="pager" aria-label="Pages">';
    for ($i = 1; $i <= $pages; $i++) {
        $query = $_GET;
        $query['page'] = $i;
        $href = url(basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php')) . '?' . http_build_query($query));
        echo '<a' . ($i === $page ? ' class="on" aria-current="page"' : '') . ' href="' . e($href) . '">' . $i . '</a>';
    }
    echo '</nav>';
}

function render_timeline(array $order, array $logs): void
{
    $steps = ['placed', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered'];
    $current = (string) $order['delivery_status'];
    if ($current === 'cancelled') {
        echo '<div class="alert-bad">This order was cancelled.</div>';
    } else {
        $index = array_search($current, $steps, true);
        $index = $index === false ? 0 : (int) $index;
        echo '<ol class="timeline">';
        foreach ($steps as $i => $step) {
            $class = $i < $index ? 'done' : ($i === $index ? 'now' : '');
            echo '<li class="' . $class . '"><span>' . e(delivery_statuses()[$step]) . '</span></li>';
        }
        echo '</ol>';
    }
    if ($logs) {
        echo '<ul class="log-list">';
        foreach ($logs as $log) {
            echo '<li><strong>' . e(ucwords(str_replace('_', ' ', (string) $log['status']))) . '</strong> · ' . e(when($log['created_at'])) . '<br>' . e($log['note']) . '</li>';
        }
        echo '</ul>';
    }
}
