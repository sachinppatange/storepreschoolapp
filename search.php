<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
$sort = (string) ($_GET['sort'] ?? 'popular');
$page = max(1, (int) ($_GET['page'] ?? 1));
$pageTitle = $q === '' ? 'Search' : 'Search: ' . $q;
$result = $q === '' ? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1] : catalog(['q' => $q, 'sort' => $sort, 'page' => $page, 'per_page' => 12]);

require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head">
        <h1><?= $q === '' ? 'Search the store' : e($result['total']) . ' result' . ($result['total'] === 1 ? '' : 's') . ' for “' . e($q) . '”' ?></h1>
    </div>
    <?php if ($q === ''): ?>
        <div class="empty"><p>Type a toy, book, kit or brand code in the search bar.</p></div>
    <?php else: ?>
        <form class="filters" method="get">
            <input type="hidden" name="q" value="<?= e($q) ?>">
            <select name="sort">
                <?php foreach (['popular' => 'Popular', 'new' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low'] as $key => $label): ?>
                    <option value="<?= e($key) ?>"<?= $sort === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-line" type="submit">Sort</button>
        </form>
        <?php render_product_grid($result['items']); ?>
        <?php render_pagination($result['page'], $result['pages']); ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
