<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND role = ?');
    $stmt->execute([$id, 'customer']);
    $customer = $stmt->fetch();
    if (!$customer) {
        flash('error', 'Customer not found.');
    } else {
        $active = (int) $customer['is_active'] ? 0 : 1;
        db()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$active, $id]);
        flash('success', $active ? 'Customer activated.' : 'Customer disabled.');
    }
    redirect(url('admin/customers.php' . ($id ? '?id=' . $id : '')));
}

$viewId = (int) ($_GET['id'] ?? 0);
$view = null;
$viewOrders = [];
if ($viewId) {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$viewId]);
    $view = $stmt->fetch() ?: null;
    if ($view) {
        $orders = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
        $orders->execute([$viewId]);
        $viewOrders = $orders->fetchAll();
    }
}

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
$sql = "SELECT * FROM users WHERE role = 'customer'";
$params = [];
if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR school_name LIKE ?)';
    $like = '%' . like_escape($q) . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql .= ' ORDER BY id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customers';
$active = 'customers';
require __DIR__ . '/includes/header.php';
?>
<form class="filters" method="get"><input name="q" value="<?= e($q) ?>" placeholder="Name, email, phone, school"><button class="btn" type="submit">Search</button></form>
<?php if ($view): ?>
    <div class="panel" style="margin-bottom:12px">
        <h2><?= e($view['name']) ?></h2>
        <p><?= e($view['email']) ?> · <?= e($view['phone']) ?><br><?= e((string) $view['school_name']) ?><br><?= e((string) $view['address']) ?> <?= e((string) $view['city']) ?> <?= e((string) $view['pincode']) ?></p>
        <p>Status: <?= (int) $view['is_active'] ? 'Active' : 'Disabled' ?></p>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $view['id'] ?>"><button class="btn alt" type="submit"><?= (int) $view['is_active'] ? 'Disable account' : 'Activate account' ?></button></form>
        <h3>Orders</h3>
        <?php if (!$viewOrders): ?><p>No orders yet.</p><?php endif; ?>
        <?php foreach ($viewOrders as $order): ?>
            <p><a href="order.php?no=<?= e(rawurlencode($order['order_number'])) ?>"><?= e($order['order_number']) ?></a> · <?= money($order['total']) ?> · <?= e($order['payment_status']) ?> · <?= e($order['delivery_status']) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<div class="table-wrap">
    <table>
        <thead><tr><th>Name</th><th>Contact</th><th>School</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($customers as $customer): ?>
            <tr>
                <td><a href="customers.php?id=<?= (int) $customer['id'] ?>"><?= e($customer['name']) ?></a></td>
                <td><?= e($customer['email']) ?><br><?= e($customer['phone']) ?></td>
                <td><?= e((string) $customer['school_name']) ?></td>
                <td><?= (int) $customer['is_active'] ? 'Active' : 'Disabled' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
