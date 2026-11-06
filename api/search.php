<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
$items = [];
if (mb_strlen($q) >= 2) {
    $result = catalog(['q' => $q, 'limit' => 6]);
    foreach ($result['items'] as $product) {
        $items[] = [
            'name' => $product['name'],
            'emoji' => $product['emoji'],
            'price' => money($product['price']),
            'url' => url('product.php?slug=' . rawurlencode((string) $product['slug'])),
        ];
    }
}
json_out(['ok' => true, 'items' => $items]);
