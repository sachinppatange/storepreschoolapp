<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$product = $id ? product_by_id($id) : null;
if ($id && !$product) {
    flash('error', 'Product not found.');
    redirect(url('admin/products.php'));
}
$categories = categories_all(false);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $sku = strtoupper(trim((string) ($_POST['sku'] ?? '')));
    $unit = trim((string) ($_POST['unit_label'] ?? ''));
    $emoji = trim((string) ($_POST['emoji'] ?? ''));
    $short = trim((string) ($_POST['short_desc'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $slugInput = trim((string) ($_POST['slug'] ?? ''));
    $price = round((float) ($_POST['price'] ?? 0), 2);
    $mrp = round((float) ($_POST['mrp'] ?? 0), 2);
    $stock = max(0, (int) ($_POST['stock'] ?? 0));
    $kit = (string) ($_POST['kit_type'] ?? 'none');
    $kits = ['none', 'starter', 'nursery', 'jrkg', 'srkg'];
    if (!in_array($kit, $kits, true)) {
        $kit = 'none';
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 180) {
        $errors[] = 'Enter a product name.';
    }
    if (!array_filter($categories, static fn (array $cat): bool => (int) $cat['id'] === $categoryId)) {
        $errors[] = 'Choose a category.';
    }
    if (!preg_match('/^[A-Z0-9-]{2,40}$/', $sku)) {
        $errors[] = 'SKU can use letters, numbers and dashes.';
    }
    if ($unit === '' || mb_strlen($unit) > 60) {
        $errors[] = 'Enter a unit label, such as 1 book.';
    }
    if ($emoji === '') {
        $emoji = '📦';
    }
    if (mb_strlen($emoji) > 8) {
        $errors[] = 'Use a short emoji.';
    }
    if (mb_strlen($short) < 5 || mb_strlen($short) > 255) {
        $errors[] = 'Enter a short description.';
    }
    if (mb_strlen($description) < 10) {
        $errors[] = 'Enter a full description.';
    }
    if ($price <= 0 || $mrp < $price) {
        $errors[] = 'Selling price must be above zero, and MRP must be at least that price.';
    }
    $skuCheck = db()->prepare('SELECT id FROM products WHERE sku = ? AND id <> ?');
    $skuCheck->execute([$sku, $id]);
    if ($skuCheck->fetch()) {
        $errors[] = 'That SKU is already used.';
    }
    $slug = $slugInput !== '' ? slugify($slugInput) : unique_slug($name, 'products', $id);
    $slugCheck = db()->prepare('SELECT id FROM products WHERE slug = ? AND id <> ?');
    $slugCheck->execute([$slug, $id]);
    if ($slugCheck->fetch()) {
        $errors[] = 'That link slug is already used.';
    }
    $upload = store_upload($_FILES['image'] ?? []);
    if (!$upload['ok']) {
        $errors[] = $upload['error'];
    }
    if (!$errors) {
        $image = $product['image'] ?? null;
        if (!empty($_POST['remove_image']) && $image) {
            delete_upload($image);
            $image = null;
        }
        if (!empty($upload['file'])) {
            if ($image) {
                delete_upload($image);
            }
            $image = $upload['file'];
        }
        $flags = [
            isset($_POST['is_active']) ? 1 : 0,
            isset($_POST['is_bestseller']) ? 1 : 0,
            isset($_POST['is_trending']) ? 1 : 0,
            isset($_POST['is_new']) ? 1 : 0,
        ];
        if ($product) {
            db()->prepare('UPDATE products SET category_id=?, name=?, slug=?, short_desc=?, description=?, emoji=?, image=?, unit_label=?, price=?, mrp=?, stock=?, sku=?, is_active=?, is_bestseller=?, is_trending=?, is_new=?, kit_type=? WHERE id=?')
                ->execute([$categoryId, $name, $slug, $short, $description, $emoji, $image, $unit, $price, $mrp, $stock, $sku, $flags[0], $flags[1], $flags[2], $flags[3], $kit, $id]);
            flash('success', 'Product updated.');
        } else {
            db()->prepare('INSERT INTO products (category_id, name, slug, short_desc, description, emoji, image, unit_label, price, mrp, stock, sku, is_active, is_bestseller, is_trending, is_new, kit_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$categoryId, $name, $slug, $short, $description, $emoji, $image, $unit, $price, $mrp, $stock, $sku, $flags[0], $flags[1], $flags[2], $flags[3], $kit]);
            flash('success', 'Product added.');
        }
        redirect(url('admin/products.php'));
    }
    $product = array_merge($product ?? [], $_POST, ['emoji' => $emoji, 'slug' => $slug ?? '']);
}

$pageTitle = $product && $id ? 'Edit product' : 'Add product';
$active = 'products';
require __DIR__ . '/includes/header.php';
$p = $product ?? [];
?>
<?php if ($errors): ?><div class="alert bad"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="panel" style="max-width:760px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <label class="field">Name<input name="name" required value="<?= e($p['name'] ?? '') ?>"></label>
    <label class="field">Link slug<input name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="Leave blank to generate"></label>
    <label class="field">Category
        <select name="category_id" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>"<?= (int) ($p['category_id'] ?? 0) === (int) $category['id'] ? ' selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">SKU<input name="sku" required value="<?= e($p['sku'] ?? '') ?>"></label>
    <label class="field">Unit<input name="unit_label" required value="<?= e($p['unit_label'] ?? '1 pc') ?>"></label>
    <label class="field">Emoji<input name="emoji" value="<?= e($p['emoji'] ?? '📦') ?>"></label>
    <label class="field">Short description<input name="short_desc" required value="<?= e($p['short_desc'] ?? '') ?>"></label>
    <label class="field">Description<textarea name="description" rows="4" required><?= e($p['description'] ?? '') ?></textarea></label>
    <div class="filters">
        <label class="field">Price<input type="number" step="0.01" min="0" name="price" required value="<?= e((string) ($p['price'] ?? '')) ?>"></label>
        <label class="field">MRP<input type="number" step="0.01" min="0" name="mrp" required value="<?= e((string) ($p['mrp'] ?? '')) ?>"></label>
        <label class="field">Stock<input type="number" min="0" name="stock" required value="<?= e((string) ($p['stock'] ?? '0')) ?>"></label>
    </div>
    <label class="field">Kit type
        <select name="kit_type">
            <?php foreach (['none' => 'Not a kit', 'starter' => 'Starter kit', 'nursery' => 'Nursery', 'jrkg' => 'Jr. KG', 'srkg' => 'Sr. KG'] as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= ($p['kit_type'] ?? 'none') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">Photo<input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"></label>
    <?php if (!empty($p['image'])): ?>
        <img src="<?= e(url('uploads/products/' . basename((string) $p['image']))) ?>" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:12px">
        <label class="check"><input type="checkbox" name="remove_image" value="1"> Remove current photo</label>
    <?php endif; ?>
    <label class="check"><input type="checkbox" name="is_active" value="1"<?= !isset($p['is_active']) || (int) $p['is_active'] === 1 ? ' checked' : '' ?>> Visible in the store</label>
    <label class="check"><input type="checkbox" name="is_bestseller" value="1"<?= !empty($p['is_bestseller']) ? ' checked' : '' ?>> Best seller</label>
    <label class="check"><input type="checkbox" name="is_trending" value="1"<?= !empty($p['is_trending']) ? ' checked' : '' ?>> Trending</label>
    <label class="check"><input type="checkbox" name="is_new" value="1"<?= !empty($p['is_new']) ? ' checked' : '' ?>> New arrival</label>
    <button class="btn" type="submit">Save product</button>
    <?php if ($id && !empty($p['slug'])): ?><a class="btn alt" href="<?= e(url('product.php?slug=' . rawurlencode((string) $p['slug']))) ?>">View</a><?php endif; ?>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
