<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$tag = trim((string) ($_GET['tag'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'popular');
$page = max(1, (int) ($_GET['page'] ?? 1));
$inStock = isset($_GET['stock']) && $_GET['stock'] === '1';
$category = null;

if ($slug === '' && $tag === '') {
    $pageTitle = 'Categories';
    $categories = categories_all();
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="wrap">
        <div class="page-head">
            <h1>Best school supplies for kids</h1>
            <p class="muted">Parents can order any of these for one child. The count is the number of items in that category.</p>
        </div>
        <div class="cat-grid">
            <?php foreach ($categories as $item): ?>
                <a class="cat-tile" href="<?= e(url('category.php?slug=' . rawurlencode($item['slug']))) ?>">
                    <span class="big"><?= e($item['emoji']) ?></span>
                    <strong><?= e($item['name']) ?></strong>
                    <span class="muted small"><?= (int) $item['product_count'] ?> items</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$opts = ['sort' => $sort, 'page' => $page, 'per_page' => 12, 'in_stock' => $inStock];
if ($slug !== '') {
    $category = category_by_slug($slug);
    if (!$category) {
        http_response_code(404);
        $pageTitle = 'Category not found';
        require __DIR__ . '/includes/header.php';
        echo '<div class="wrap"><div class="empty"><h1>Category not found</h1><a class="btn btn-green" href="' . e(url('category.php')) . '">All categories</a></div></div>';
        require __DIR__ . '/includes/footer.php';
        exit;
    }
    $opts['category_id'] = (int) $category['id'];
    $pageTitle = $category['name'];
    $intro = $category['description'];
} else {
    $titles = [
        'bestseller' => ['Best sellers', 'What schools and parents add most often.', ['bestseller' => true]],
        'trending' => ['Trending', 'Popular right now in the preschool store.', ['trending' => true]],
        'new' => ['New arrivals', 'Just added to the shelves.', ['new' => true]],
    ];
    if (!isset($titles[$tag])) {
        redirect(url('category.php'));
    }
    $pageTitle = $titles[$tag][0];
    $intro = $titles[$tag][1];
    $opts = array_merge($opts, $titles[$tag][2]);
}

$result = catalog($opts);
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head">
        <h1><?= e($pageTitle) ?></h1>
        <p class="muted"><?= e($intro) ?></p>
    </div>
    <form class="filters" method="get">
        <?php if ($slug !== ''): ?><input type="hidden" name="slug" value="<?= e($slug) ?>"><?php endif; ?>
        <?php if ($tag !== ''): ?><input type="hidden" name="tag" value="<?= e($tag) ?>"><?php endif; ?>
        <label class="sr-only" for="sort">Sort</label>
        <select id="sort" name="sort">
            <?php foreach (['popular' => 'Popular', 'new' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low'] as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $sort === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="check"><input type="checkbox" name="stock" value="1"<?= $inStock ? ' checked' : '' ?>> In stock</label>
        <button class="btn btn-line" type="submit">Apply</button>
    </form>
    <?php render_product_grid($result['items']); ?>
    <?php render_pagination($result['page'], $result['pages']); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
