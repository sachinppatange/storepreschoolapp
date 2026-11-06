<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Class kits';
$groups = [
    'starter' => 'Preschool starter kit',
    'nursery' => 'Nursery',
    'jrkg' => 'Jr. KG',
    'srkg' => 'Sr. KG',
];

require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
    <div class="page-head">
        <h1>Preschool kits</h1>
        <p class="muted">Ready packs for a new admission or a whole class. Schools can also <a href="<?= e(url('bulk-order.php')) ?>">place a bulk order</a>.</p>
    </div>
    <?php foreach ($groups as $type => $label): ?>
        <?php $result = catalog(['kit_type' => $type, 'limit' => 8]); ?>
        <?php if (!$result['items']) continue; ?>
        <section class="section">
            <div class="section-head"><h2><?= e($label) ?></h2></div>
            <?php render_product_grid($result['items']); ?>
        </section>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
