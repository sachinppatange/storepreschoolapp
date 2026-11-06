<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

$from = (string) ($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
$to = (string) ($_GET['to'] ?? date('Y-m-d'));
if (!valid_date($from) || !valid_date($to) || $from > $to) {
    $from = date('Y-m-d', strtotime('-30 days'));
    $to = date('Y-m-d');
}

$paidWhere = "payment_status = 'paid' AND delivery_status <> 'cancelled' AND DATE(created_at) BETWEEN ? AND ?";
$summaryStmt = db()->prepare("SELECT COUNT(*) AS orders_count, COALESCE(SUM(total),0) AS sales, COALESCE(SUM(discount),0) AS discounts FROM orders WHERE $paidWhere");
$summaryStmt->execute([$from, $to]);
$summary = $summaryStmt->fetch();

$daysStmt = db()->prepare("SELECT DATE(created_at) AS day, COUNT(*) AS orders_count, COALESCE(SUM(total),0) AS sales FROM orders WHERE $paidWhere GROUP BY DATE(created_at) ORDER BY day");
$daysStmt->execute([$from, $to]);
$days = $daysStmt->fetchAll();

$topStmt = db()->prepare("SELECT oi.product_name, SUM(oi.quantity) AS qty, SUM(oi.line_total) AS amount
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.payment_status = 'paid' AND o.delivery_status <> 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY oi.product_id, oi.product_name ORDER BY qty DESC LIMIT 10");
$topStmt->execute([$from, $to]);
$top = $topStmt->fetchAll();

$catStmt = db()->prepare("SELECT c.name, SUM(oi.line_total) AS amount
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN products p ON p.id = oi.product_id
    JOIN categories c ON c.id = p.category_id
    WHERE o.payment_status = 'paid' AND o.delivery_status <> 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY c.id, c.name ORDER BY amount DESC");
$catStmt->execute([$from, $to]);
$categories = $catStmt->fetchAll();

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sales-' . $from . '-to-' . $to . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Order', 'Date', 'School', 'Type', 'Payment', 'Delivery', 'Total']);
    $export = db()->prepare("SELECT * FROM orders WHERE $paidWhere ORDER BY id");
    $export->execute([$from, $to]);
    while ($row = $export->fetch()) {
        fputcsv($out, [
            $row['order_number'],
            $row['created_at'],
            $row['school_name'],
            $row['order_type'],
            $row['payment_status'],
            $row['delivery_status'],
            $row['total'],
        ]);
    }
    fclose($out);
    exit;
}

$max = 0;
foreach ($days as $day) {
    $max = max($max, (float) $day['sales']);
}
$pageTitle = 'Sales reports';
$active = 'reports';
require __DIR__ . '/includes/header.php';
?>
<form class="filters" method="get">
    <label class="field">From<input type="date" name="from" value="<?= e($from) ?>"></label>
    <label class="field">To<input type="date" name="to" value="<?= e($to) ?>"></label>
    <button class="btn" type="submit">Show</button>
    <a class="btn alt" href="reports.php?from=<?= e($from) ?>&to=<?= e($to) ?>&export=1">Download CSV</a>
</form>
<div class="cards">
    <div class="card"><span>Paid orders</span><strong><?= (int) $summary['orders_count'] ?></strong></div>
    <div class="card"><span>Sales</span><strong><?= money($summary['sales']) ?></strong></div>
    <div class="card"><span>Discounts</span><strong><?= money($summary['discounts']) ?></strong></div>
</div>
<div class="panel" style="margin-bottom:12px">
    <h2>Sales by day</h2>
    <?php if (!$days): ?><p>No paid orders in this range.</p><?php endif; ?>
    <div>
        <?php foreach ($days as $day): ?>
            <?php $width = $max > 0 ? max(4, (int) round(((float) $day['sales'] / $max) * 100)) : 0; ?>
            <div style="display:grid;grid-template-columns:110px 1fr 120px;gap:8px;align-items:center;margin:6px 0">
                <span><?= e(date('d M', strtotime($day['day']))) ?></span>
                <span style="background:#e5e7eb;border-radius:99px;height:10px;display:block"><span style="display:block;height:10px;background:#0c831f;width:<?= $width ?>%;border-radius:99px"></span></span>
                <strong><?= money($day['sales']) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<div class="panel" style="margin-bottom:12px">
    <h2>Top products</h2>
    <table><thead><tr><th>Product</th><th>Qty</th><th>Amount</th></tr></thead><tbody>
    <?php foreach ($top as $row): ?><tr><td><?= e($row['product_name']) ?></td><td><?= (int) $row['qty'] ?></td><td><?= money($row['amount']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
</div>
<div class="panel">
    <h2>Sales by category</h2>
    <table><thead><tr><th>Category</th><th>Amount</th></tr></thead><tbody>
    <?php foreach ($categories as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= money($row['amount']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
