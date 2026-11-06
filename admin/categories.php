<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

$editId = (int) ($_GET['id'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'toggle') {
        db()->prepare('UPDATE categories SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?')->execute([$id]);
        flash('success', 'Category updated.');
        redirect(url('admin/categories.php'));
    }
    if ($action === 'delete') {
        $count = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $count->execute([$id]);
        if ((int) $count->fetchColumn() > 0) {
            flash('error', 'Move or delete the products in this category first.');
        } else {
            db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'Category deleted.');
        }
        redirect(url('admin/categories.php'));
    }
    $name = trim((string) ($_POST['name'] ?? ''));
    $emoji = trim((string) ($_POST['emoji'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $sort = (int) ($_POST['sort_order'] ?? 0);
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || $emoji === '' || mb_strlen($description) < 5) {
        flash('error', 'Name, emoji and description are required.');
        redirect(url('admin/categories.php' . ($id ? '?id=' . $id : '')));
    }
    $slug = unique_slug($name, 'categories', $id);
    if ($id) {
        db()->prepare('UPDATE categories SET name=?, slug=?, emoji=?, description=?, sort_order=? WHERE id=?')
            ->execute([$name, $slug, $emoji, $description, $sort, $id]);
        flash('success', 'Category saved.');
    } else {
        db()->prepare('INSERT INTO categories (name, slug, emoji, description, sort_order) VALUES (?, ?, ?, ?, ?)')
            ->execute([$name, $slug, $emoji, $description, $sort]);
        flash('success', 'Category added.');
    }
    redirect(url('admin/categories.php'));
}

$categories = categories_all(false);
$pageTitle = 'Categories';
$active = 'categories';
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="panel" style="max-width:640px;margin-bottom:16px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <h2><?= $edit ? 'Edit category' : 'Add category' ?></h2>
    <label class="field">Name<input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
    <label class="field">Emoji<input name="emoji" required value="<?= e($edit['emoji'] ?? '📦') ?>"></label>
    <label class="field">Description<input name="description" required value="<?= e($edit['description'] ?? '') ?>"></label>
    <label class="field">Sort order<input type="number" name="sort_order" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"></label>
    <button class="btn" type="submit">Save category</button>
</form>
<div class="table-wrap">
    <table>
        <thead><tr><th>Category</th><th>Products</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= e($category['emoji']) ?> <?= e($category['name']) ?></td>
                <td><?= (int) $category['product_count'] ?></td>
                <td><?= (int) $category['is_active'] ? 'Visible' : 'Hidden' ?></td>
                <td>
                    <a class="btn alt" href="categories.php?id=<?= (int) $category['id'] ?>">Edit</a>
                    <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="btn alt" type="submit"><?= (int) $category['is_active'] ? 'Hide' : 'Show' ?></button></form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this category?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="btn danger" type="submit">Delete</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
