<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $product = $id ? product_by_id($id) : null;
    if (!$product) {
        flash('error', 'Product not found.');
        redirect(url('admin/products.php'));
    }
    if ($action === 'stock') {
        $stock = max(0, (int) ($_POST['stock'] ?? 0));
        db()->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute([$stock, $id]);
        flash('success', 'Stock updated for ' . $product['name'] . '.');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?')->execute([$id]);
        flash('success', 'Product visibility updated.');
    } elseif ($action === 'delete') {
        $used = db()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $used->execute([$id]);
        if ((int) $used->fetchColumn() > 0) {
            db()->prepare('UPDATE products SET is_active = 0 WHERE id = ?')->execute([$id]);
            flash('success', 'This product is on past orders, so it was hidden instead of deleted.');
        } else {
            delete_upload($product['image'] ?? null);
            db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            flash('success', 'Product deleted.');
        }
    }
    redirect(url('admin/products.php' . (!empty($_POST['back']) ? '?' . $_POST['back'] : '')));
}

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
$low = (($_GET['stock'] ?? '') === 'low');
$sql = 'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE 1 = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ?)';
    $like = '%' . like_escape($q) . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($low) {
    $sql .= ' AND p.stock <= 10';
}
$sql .= ' ORDER BY p.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$back = http_build_query(array_filter(['q' => $q, 'stock' => $low ? 'low' : null]));

$pageTitle = 'Products and inventory';
$active = 'products';
require __DIR__ . '/includes/header.php';
?>
<div class="filters">
    <form method="get" class="filters">
        <input name="q" value="<?= e($q) ?>" placeholder="Search name or SKU">
        <label class="check"><input type="checkbox" name="stock" value="low"<?= $low ? ' checked' : '' ?>> Low stock</label>
        <button class="btn" type="submit">Filter</button>
        <a class="btn alt" href="product-form.php">Add product</a>
    </form>
</div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Flags</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td>
                    <?= e($product['emoji']) ?> <a href="product-form.php?id=<?= (int) $product['id'] ?>"><?= e($product['name']) ?></a>
                    <div><?= e($product['sku']) ?><?= (int) $product['is_active'] ? '' : ' · hidden' ?></div>
                </td>
                <td><?= e($product['category_name']) ?></td>
                <td><?= money($product['price']) ?><br><s><?= money($product['mrp']) ?></s></td>
                <td>
                    <form method="post" class="filters">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="stock">
                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="back" value="<?= e($back) ?>">
                        <input type="number" name="stock" min="0" value="<?= (int) $product['stock'] ?>" style="width:80px">
                        <button class="btn" type="submit">Save</button>
                    </form>
                </td>
                <td>
                    <?= (int) $product['is_bestseller'] ? 'Best ' : '' ?>
                    <?= (int) $product['is_trending'] ? 'Trending ' : '' ?>
                    <?= (int) $product['is_new'] ? 'New ' : '' ?>
                    <?= $product['kit_type'] !== 'none' ? e($product['kit_type']) : '' ?>
                </td>
                <td>
                    <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button class="btn alt" type="submit"><?= (int) $product['is_active'] ? 'Hide' : 'Show' ?></button></form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this product?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button class="btn danger" type="submit">Delete</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
