<?php
declare(strict_types=1);

function categories_all(bool $activeOnly = true): array
{
    $sql = 'SELECT c.*, COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1';
    if ($activeOnly) {
        $sql .= ' WHERE c.is_active = 1';
    }
    $sql .= ' GROUP BY c.id ORDER BY c.sort_order, c.name';
    return db()->query($sql)->fetchAll();
}

function category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function product_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function product_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p JOIN categories c ON c.id = p.category_id
        WHERE p.slug = ? AND p.is_active = 1 AND c.is_active = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function catalog(array $opts): array
{
    $where = ['p.is_active = 1', 'c.is_active = 1'];
    $params = [];

    if (!empty($opts['category_id'])) {
        $where[] = 'p.category_id = ?';
        $params[] = (int) $opts['category_id'];
    }
    if (!empty($opts['q'])) {
        $q = mb_substr(trim((string) $opts['q']), 0, 80);
        $like = '%' . like_escape($q) . '%';
        $where[] = '(p.name LIKE ? OR p.short_desc LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if (!empty($opts['bestseller'])) {
        $where[] = 'p.is_bestseller = 1';
    }
    if (!empty($opts['trending'])) {
        $where[] = 'p.is_trending = 1';
    }
    if (!empty($opts['new'])) {
        $where[] = 'p.is_new = 1';
    }
    if (!empty($opts['kits'])) {
        $where[] = "p.kit_type <> 'none'";
    }
    if (!empty($opts['kit_type'])) {
        $where[] = 'p.kit_type = ?';
        $params[] = (string) $opts['kit_type'];
    }
    if (!empty($opts['in_stock'])) {
        $where[] = 'p.stock > 0';
    }

    $orders = [
        'popular' => 'p.is_bestseller DESC, p.id DESC',
        'new' => 'p.created_at DESC, p.id DESC',
        'price_asc' => 'p.price ASC, p.id DESC',
        'price_desc' => 'p.price DESC, p.id DESC',
        'name' => 'p.name ASC',
    ];
    $order = $orders[$opts['sort'] ?? 'popular'] ?? $orders['popular'];
    $whereSql = implode(' AND ', $where);

    $count = db()->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE {$whereSql}");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    if (!empty($opts['limit'])) {
        $per = min(48, max(1, (int) $opts['limit']));
        $page = 1;
        $pages = 1;
        $offset = 0;
    } else {
        $per = min(48, max(1, (int) ($opts['per_page'] ?? 12)));
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, min($pages, (int) ($opts['page'] ?? 1)));
        $offset = ($page - 1) * $per;
    }

    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p JOIN categories c ON c.id = p.category_id
            WHERE {$whereSql} ORDER BY {$order} LIMIT {$per} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return [
        'items' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
    ];
}

function active_coupons(): array
{
    return db()->query("SELECT * FROM coupons WHERE is_active = 1 AND (valid_from IS NULL OR valid_from <= CURDATE()) AND (valid_to IS NULL OR valid_to >= CURDATE()) ORDER BY id")->fetchAll();
}

function cart_owner(): array
{
    $userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    return [$userId, session_id()];
}

function cart_lines(): array
{
    [$userId, $sessionId] = cart_owner();
    if ($userId) {
        $stmt = db()->prepare('SELECT ci.id AS cart_id, ci.quantity, p.id, p.name, p.slug, p.price, p.mrp, p.stock, p.emoji, p.image, p.unit_label, p.category_id, p.is_active
            FROM cart_items ci JOIN products p ON p.id = ci.product_id
            WHERE ci.user_id = ? AND p.is_active = 1 ORDER BY ci.id');
        $stmt->execute([$userId]);
    } else {
        $stmt = db()->prepare('SELECT ci.id AS cart_id, ci.quantity, p.id, p.name, p.slug, p.price, p.mrp, p.stock, p.emoji, p.image, p.unit_label, p.category_id, p.is_active
            FROM cart_items ci JOIN products p ON p.id = ci.product_id
            WHERE ci.session_id = ? AND ci.user_id IS NULL AND p.is_active = 1 ORDER BY ci.id');
        $stmt->execute([$sessionId]);
    }
    return $stmt->fetchAll();
}

function cart_line_qty(int $productId): int
{
    foreach (cart_lines() as $line) {
        if ((int) $line['id'] === $productId) {
            return (int) $line['quantity'];
        }
    }
    return 0;
}

function cart_qty_map(): array
{
    $map = [];
    foreach (cart_summary()['items'] as $line) {
        $map[(int) $line['id']] = (int) $line['quantity'];
    }
    return $map;
}

function evaluate_coupon(string $code, float $subtotal, bool $lock = false): array
{
    $code = strtoupper(trim($code));
    if (!preg_match('/^[A-Z0-9]{3,20}$/', $code)) {
        return ['ok' => false, 'error' => 'Enter a valid coupon code.'];
    }
    $sql = 'SELECT * FROM coupons WHERE code = ? LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
    $stmt = db()->prepare($sql);
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if (!$coupon || !(int) $coupon['is_active']) {
        return ['ok' => false, 'error' => 'This coupon is not active.'];
    }
    $today = date('Y-m-d');
    if (!empty($coupon['valid_from']) && $coupon['valid_from'] > $today) {
        return ['ok' => false, 'error' => 'This coupon is not active yet.'];
    }
    if (!empty($coupon['valid_to']) && $coupon['valid_to'] < $today) {
        return ['ok' => false, 'error' => 'This coupon has expired.'];
    }
    if ($subtotal + 0.001 < (float) $coupon['min_order']) {
        return ['ok' => false, 'error' => 'Add items worth ' . money($coupon['min_order']) . ' to use this coupon.'];
    }
    if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
        return ['ok' => false, 'error' => 'This coupon has reached its usage limit.'];
    }
    if ($coupon['discount_type'] === 'percent') {
        $discount = round($subtotal * ((float) $coupon['discount_value'] / 100), 2);
        if ($coupon['max_discount'] !== null && (float) $coupon['max_discount'] > 0) {
            $discount = min($discount, round((float) $coupon['max_discount'], 2));
        }
    } else {
        $discount = round((float) $coupon['discount_value'], 2);
    }
    $discount = round(min($discount, $subtotal), 2);
    if ($discount <= 0) {
        return ['ok' => false, 'error' => 'This coupon does not apply to this order.'];
    }
    return ['ok' => true, 'coupon' => $coupon, 'discount' => $discount];
}

function cart_summary(bool $refresh = false): array
{
    static $cache = null;
    if ($refresh) {
        $cache = null;
    }
    if ($cache !== null) {
        return $cache;
    }

    $items = cart_lines();
    $prints = print_jobs_open();
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal = round($subtotal + ((float) $item['price'] * (int) $item['quantity']), 2);
    }
    foreach ($prints as $job) {
        $subtotal = round($subtotal + (float) $job['line_total'], 2);
    }

    $discount = 0.0;
    $coupon = null;
    $couponError = null;
    if (!empty($_SESSION['coupon'])) {
        $eval = evaluate_coupon((string) $_SESSION['coupon'], $subtotal);
        if ($eval['ok']) {
            $discount = $eval['discount'];
            $coupon = $eval['coupon'];
        } else {
            $couponError = $eval['error'];
            unset($_SESSION['coupon']);
        }
    }

    $net = round($subtotal - $discount, 2);
    $delivery = (($items || $prints) && $net > 0 && $net < FREE_DELIVERY_ABOVE) ? (float) DELIVERY_FEE : 0.0;
    $total = round($net + $delivery, 2);
    $count = count($prints);
    foreach ($items as $item) {
        $count += (int) $item['quantity'];
    }

    $cache = [
        'items' => $items,
        'prints' => $prints,
        'count' => $count,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'coupon' => $coupon,
        'coupon_error' => $couponError,
        'delivery' => $delivery,
        'total' => $total,
    ];
    return $cache;
}

function cart_count(): int
{
    return (int) cart_summary()['count'];
}

function cart_payload(int $productId, string $message = ''): array
{
    $summary = cart_summary(true);
    $line = 0;
    foreach ($summary['items'] as $item) {
        if ((int) $item['id'] === $productId) {
            $line = (int) $item['quantity'];
        }
    }
    return [
        'ok' => true,
        'message' => $message,
        'count' => $summary['count'],
        'total' => $summary['total'],
        'total_label' => money($summary['total']),
        'line_qty' => $line,
        'product_id' => $productId,
    ];
}

function find_cart_row(int $productId): ?array
{
    [$userId, $sessionId] = cart_owner();
    if ($userId) {
        $stmt = db()->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1');
        $stmt->execute([$userId, $productId]);
    } else {
        $stmt = db()->prepare('SELECT * FROM cart_items WHERE session_id = ? AND user_id IS NULL AND product_id = ? LIMIT 1');
        $stmt->execute([$sessionId, $productId]);
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

function cart_set(int $productId, int $qty): array
{
    $product = product_by_id($productId);
    if (!$product || !(int) $product['is_active']) {
        return ['ok' => false, 'error' => 'This product is unavailable.'];
    }
    $stock = (int) $product['stock'];
    if ($qty <= 0) {
        $row = find_cart_row($productId);
        if ($row) {
            db()->prepare('DELETE FROM cart_items WHERE id = ?')->execute([(int) $row['id']]);
        }
        return cart_payload($productId, 'Removed from cart');
    }
    if ($stock <= 0) {
        return ['ok' => false, 'error' => 'This product is out of stock.'];
    }
    $max = min(50, $stock);
    if ($qty > $max) {
        return ['ok' => false, 'error' => 'Only ' . $max . ' in stock.'];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $row = find_cart_row($productId);
        if ($row) {
            $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')->execute([$qty, (int) $row['id']]);
        } else {
            [$userId, $sessionId] = cart_owner();
            $pdo->prepare('INSERT INTO cart_items (user_id, session_id, product_id, quantity) VALUES (?, ?, ?, ?)')
                ->execute([$userId, $sessionId, $productId, $qty]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'Could not update the cart.'];
    }
    return cart_payload($productId, 'Cart updated');
}

function cart_apply(array $post): array
{
    $action = (string) ($post['action'] ?? '');
    $productId = (int) ($post['product_id'] ?? 0);
    $qty = (int) ($post['qty'] ?? 1);
    if ($action === 'add') {
        return cart_set($productId, cart_line_qty($productId) + max(1, $qty));
    }
    if ($action === 'update') {
        return cart_set($productId, $qty);
    }
    if ($action === 'remove') {
        return cart_set($productId, 0);
    }
    if ($action === 'clear') {
        cart_clear();
        return cart_payload(0, 'Cart cleared');
    }
    return ['ok' => false, 'error' => 'Unknown cart action.'];
}

function cart_clear(): void
{
    [$userId, $sessionId] = cart_owner();
    if ($userId) {
        db()->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
    } else {
        db()->prepare('DELETE FROM cart_items WHERE session_id = ? AND user_id IS NULL')->execute([$sessionId]);
    }
    cart_summary(true);
}

function merge_cart(int $userId, string $oldSessionId): void
{
    $stmt = db()->prepare('SELECT * FROM cart_items WHERE session_id = ? AND user_id IS NULL');
    $stmt->execute([$oldSessionId]);
    $guestRows = $stmt->fetchAll();
    $find = db()->prepare('SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1');
    $update = db()->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
    $delete = db()->prepare('DELETE FROM cart_items WHERE id = ?');
    $claim = db()->prepare('UPDATE cart_items SET user_id = ?, session_id = ? WHERE id = ?');
    $newSession = session_id();

    foreach ($guestRows as $row) {
        $product = product_by_id((int) $row['product_id']);
        if (!$product || !(int) $product['is_active'] || (int) $product['stock'] <= 0) {
            $delete->execute([(int) $row['id']]);
            continue;
        }
        $find->execute([$userId, (int) $row['product_id']]);
        $existing = $find->fetch();
        $qty = min(50, (int) $product['stock'], (int) $row['quantity'] + (int) ($existing['quantity'] ?? 0));
        if ($existing) {
            $update->execute([max(1, $qty), (int) $existing['id']]);
            $delete->execute([(int) $row['id']]);
        } else {
            $qty = min(50, (int) $product['stock'], (int) $row['quantity']);
            $update->execute([$qty, (int) $row['id']]);
            $claim->execute([$userId, $newSession, (int) $row['id']]);
        }
    }
    claim_print_jobs($userId, $oldSessionId, $newSession);
    cart_summary(true);
}

function set_coupon(string $code): array
{
    $summary = cart_summary();
    $subtotal = (float) $summary['subtotal'];
    if ($subtotal <= 0) {
        return ['ok' => false, 'error' => 'Add a product or a print before applying a coupon.'];
    }
    $eval = evaluate_coupon($code, $subtotal);
    if (!$eval['ok']) {
        return $eval;
    }
    $_SESSION['coupon'] = $eval['coupon']['code'];
    cart_summary(true);
    return ['ok' => true, 'message' => $eval['coupon']['code'] . ' applied.'];
}

function wishlist_ids(): array
{
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }
    $ids = [];
    $user = current_user();
    if (!$user) {
        return $ids;
    }
    $stmt = db()->prepare('SELECT product_id FROM wishlists WHERE user_id = ?');
    $stmt->execute([(int) $user['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $ids[(int) $row['product_id']] = true;
    }
    return $ids;
}

function in_wishlist(int $productId): bool
{
    return isset(wishlist_ids()[$productId]);
}

function wishlist_items(): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }
    $stmt = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM wishlists w
        JOIN products p ON p.id = w.product_id
        JOIN categories c ON c.id = p.category_id
        WHERE w.user_id = ? AND p.is_active = 1
        ORDER BY w.id DESC');
    $stmt->execute([(int) $user['id']]);
    return $stmt->fetchAll();
}

function wishlist_toggle(int $productId): array
{
    $user = current_user();
    if (!$user) {
        return ['ok' => false, 'login' => true, 'error' => 'Please login to save items.'];
    }
    $product = product_by_id($productId);
    if (!$product || !(int) $product['is_active']) {
        return ['ok' => false, 'error' => 'This product is unavailable.'];
    }
    $stmt = db()->prepare('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1');
    $stmt->execute([(int) $user['id'], $productId]);
    $row = $stmt->fetch();
    if ($row) {
        db()->prepare('DELETE FROM wishlists WHERE id = ?')->execute([(int) $row['id']]);
        return ['ok' => true, 'on' => false, 'message' => 'Removed from wishlist'];
    }
    db()->prepare('INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)')->execute([(int) $user['id'], $productId]);
    return ['ok' => true, 'on' => true, 'message' => 'Saved to wishlist'];
}

function price_lines(array $lines, ?string $couponCode, string $type, bool $lock): array
{
    $max = $type === 'bulk' ? 500 : 50;
    $qtyMap = [];
    foreach ($lines as $line) {
        $id = (int) ($line['id'] ?? 0);
        $qty = (int) ($line['qty'] ?? 0);
        if ($id <= 0 || $qty <= 0) {
            continue;
        }
        $qtyMap[$id] = ($qtyMap[$id] ?? 0) + $qty;
        if ($qtyMap[$id] > $max) {
            return ['ok' => false, 'error' => 'Quantity is too high for one product.'];
        }
    }
    if (!$qtyMap) {
        return ['ok' => false, 'error' => 'Add at least one product.'];
    }
    if (count($qtyMap) > 100) {
        return ['ok' => false, 'error' => 'Too many different products in one order.'];
    }

    $ids = array_keys($qtyMap);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT * FROM products WHERE id IN ({$placeholders}) AND is_active = 1" . ($lock ? ' FOR UPDATE' : '');
    $stmt = db()->prepare($sql);
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $product) {
        $products[(int) $product['id']] = $product;
    }
    if (count($products) !== count($ids)) {
        return ['ok' => false, 'error' => 'A product in this order is no longer available.'];
    }

    $items = [];
    $subtotal = 0.0;
    foreach ($qtyMap as $id => $qty) {
        $product = $products[$id];
        if ((int) $product['stock'] < $qty) {
            return ['ok' => false, 'error' => $product['name'] . ' has only ' . (int) $product['stock'] . ' left.'];
        }
        $price = round((float) $product['price'], 2);
        $lineTotal = round($price * $qty, 2);
        $subtotal = round($subtotal + $lineTotal, 2);
        $items[] = [
            'id' => $id,
            'name' => $product['name'],
            'qty' => $qty,
            'price' => $price,
            'line_total' => $lineTotal,
        ];
    }

    $coupon = null;
    $discount = 0.0;
    if ($couponCode) {
        $eval = evaluate_coupon($couponCode, $subtotal, $lock);
        if (!$eval['ok']) {
            return $eval;
        }
        $coupon = $eval['coupon'];
        $discount = $eval['discount'];
    }
    $net = round($subtotal - $discount, 2);
    $delivery = ($net > 0 && $net < FREE_DELIVERY_ABOVE) ? (float) DELIVERY_FEE : 0.0;
    $total = round($net + $delivery, 2);
    if ($total <= 0) {
        return ['ok' => false, 'error' => 'Order total must be greater than zero.'];
    }

    return [
        'ok' => true,
        'items' => $items,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'delivery' => $delivery,
        'total' => $total,
        'coupon' => $coupon,
    ];
}

function make_order_number(): string
{
    for ($i = 0; $i < 5; $i++) {
        $number = 'SP' . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
        $stmt = db()->prepare('SELECT id FROM orders WHERE order_number = ? LIMIT 1');
        $stmt->execute([$number]);
        if (!$stmt->fetch()) {
            return $number;
        }
    }
    throw new RuntimeException('Could not create an order number.');
}

function place_order(array $customer, array $lines, string $method, string $type, ?string $couponCode): array
{
    if (!in_array($method, ['razorpay', 'cod'], true)) {
        return ['ok' => false, 'error' => 'Choose a payment method.'];
    }
    if (!in_array($type, ['retail', 'bulk'], true)) {
        $type = 'retail';
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        $prints = print_jobs_open(true);
        $hasProducts = false;
        foreach ($lines as $line) {
            if ((int) ($line['id'] ?? 0) > 0 && (int) ($line['qty'] ?? 0) > 0) {
                $hasProducts = true;
                break;
            }
        }
        if (!$hasProducts && !$prints) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Add a product or a print before checkout.'];
        }
        if ($hasProducts) {
            $priced = price_lines($lines, null, $type, true);
            if (!$priced['ok']) {
                $pdo->rollBack();
                return $priced;
            }
        } else {
            $priced = [
                'ok' => true,
                'items' => [],
                'subtotal' => 0.0,
                'discount' => 0.0,
                'delivery' => 0.0,
                'total' => 0.0,
                'coupon' => null,
            ];
        }
        foreach ($prints as $job) {
            $priced['subtotal'] = round($priced['subtotal'] + (float) $job['line_total'], 2);
        }
        $priced['coupon'] = null;
        $priced['discount'] = 0.0;
        if ($couponCode) {
            $eval = evaluate_coupon($couponCode, $priced['subtotal'], true);
            if (!$eval['ok']) {
                $pdo->rollBack();
                return $eval;
            }
            $priced['coupon'] = $eval['coupon'];
            $priced['discount'] = $eval['discount'];
        }
        $net = round($priced['subtotal'] - $priced['discount'], 2);
        $priced['delivery'] = ($net > 0 && $net < FREE_DELIVERY_ABOVE) ? (float) DELIVERY_FEE : 0.0;
        $priced['total'] = round($net + $priced['delivery'], 2);
        if ($priced['total'] <= 0) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Order total must be greater than zero.'];
        }
        $store = dark_store_for_pincode((string) $customer['pincode']);
        if ($store && $priced['items']) {
            $short = store_stock_shortfall((int) $store['id'], $priced['items']);
            if ($short) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => $short];
            }
        }
        $number = make_order_number();
        $deductNow = ($method === 'cod' && $priced['items']) ? 1 : 0;
        $stmt = $pdo->prepare('INSERT INTO orders
            (order_number, user_id, order_type, customer_name, email, school_name, mobile, address, city, pincode, notes,
             subtotal, discount, coupon_code, delivery_fee, total, payment_method, payment_status, delivery_status, stock_deducted, dark_store_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $number,
            $customer['user_id'],
            $type,
            $customer['name'],
            $customer['email'],
            $customer['school_name'],
            $customer['mobile'],
            $customer['address'],
            $customer['city'],
            $customer['pincode'],
            $customer['notes'] !== '' ? $customer['notes'] : null,
            $priced['subtotal'],
            $priced['discount'],
            $priced['coupon']['code'] ?? null,
            $priced['delivery'],
            $priced['total'],
            $method,
            'pending',
            'placed',
            $deductNow,
            $store['id'] ?? null,
        ]);
        $orderId = (int) $pdo->lastInsertId();
        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($priced['items'] as $item) {
            $itemStmt->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['qty'], $item['line_total']]);
        }
        if ($deductNow && $priced['items'] && !deduct_order_stock($orderId)) {
            throw new RuntimeException('Stock changed while placing the order. Please review the cart.');
        }
        attach_print_jobs($orderId, $prints);
        if ($priced['coupon']) {
            $couponStmt = $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND (usage_limit IS NULL OR used_count < usage_limit)');
            $couponStmt->execute([(int) $priced['coupon']['id']]);
            if ($couponStmt->rowCount() !== 1) {
                throw new RuntimeException('This coupon can no longer be used.');
            }
        }
        $note = $type === 'bulk' ? 'Bulk order placed' : ($method === 'cod' ? 'Cash on delivery order placed' : 'Order placed');
        if ($store) {
            $note .= ' at ' . $store['name'];
        }
        if ($prints) {
            $note .= ' · Print on demand';
        }
        $pdo->prepare('INSERT INTO order_status_logs (order_id, status, note) VALUES (?, ?, ?)')->execute([$orderId, 'placed', $note]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $e->getMessage();
        if (!str_contains($message, 'Stock changed') && !str_contains($message, 'coupon')) {
            $message = APP_DEBUG ? $message : 'Could not place the order. Please try again.';
        }
        return ['ok' => false, 'error' => $message];
    }

    $order = find_order_by_id($orderId);
    if ($order && $method === 'razorpay' && razorpay_ready()) {
        $created = razorpay_create_for_order($order);
        if (!$created['ok']) {
            $order['razorpay_error'] = $created['error'];
        } else {
            $order = find_order_by_id($orderId);
        }
    }
    return ['ok' => true, 'order' => $order];
}

function find_order_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_order_by_number(string $number): ?array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
    $stmt->execute([$number]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function order_items(int $orderId): array
{
    $stmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function order_logs(int $orderId): array
{
    $stmt = db()->prepare('SELECT * FROM order_status_logs WHERE order_id = ? ORDER BY id');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function remember_order_access(string $orderNumber): void
{
    $_SESSION['recent_order'] = $orderNumber;
    $_SESSION['tracked'][$orderNumber] = true;
}

function can_view_order(?array $order): bool
{
    if (!$order) {
        return false;
    }
    if (is_admin()) {
        return true;
    }
    $user = current_user();
    if ($user && (int) $order['user_id'] === (int) $user['id']) {
        return true;
    }
    if (($_SESSION['recent_order'] ?? '') === $order['order_number']) {
        return true;
    }
    return !empty($_SESSION['tracked'][$order['order_number']]);
}

function valid_order_number(string $number): bool
{
    return (bool) preg_match('/^SP[A-Z0-9]{6,16}$/', $number);
}

function restore_order_stock(int $orderId): void
{
    $order = find_order_by_id($orderId);
    $storeId = (int) ($order['dark_store_id'] ?? 0);
    $items = order_items($orderId);
    $stmt = db()->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
    foreach ($items as $item) {
        if ($item['product_id']) {
            $stmt->execute([(int) $item['quantity'], (int) $item['product_id']]);
            if ($storeId > 0) {
                add_store_stock($storeId, (int) $item['product_id'], (int) $item['quantity']);
            }
        }
    }
}

function deduct_order_stock(int $orderId): bool
{
    $order = find_order_by_id($orderId);
    $storeId = (int) ($order['dark_store_id'] ?? 0);
    $items = order_items($orderId);
    $stmt = db()->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
    $storeStmt = db()->prepare('UPDATE store_stock SET quantity = quantity - ? WHERE dark_store_id = ? AND product_id = ? AND quantity >= ?');
    foreach ($items as $item) {
        if (!$item['product_id']) {
            continue;
        }
        $qty = (int) $item['quantity'];
        $productId = (int) $item['product_id'];
        if ($storeId > 0) {
            $storeStmt->execute([$qty, $storeId, $productId, $qty]);
            if ($storeStmt->rowCount() !== 1) {
                return false;
            }
        }
        $stmt->execute([$qty, $productId, $qty]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }
    }
    return true;
}

function release_coupon(?string $code): void
{
    if (!$code) {
        return;
    }
    db()->prepare('UPDATE coupons SET used_count = GREATEST(used_count - 1, 0) WHERE code = ?')->execute([$code]);
}

function change_order_state(array $order, string $paymentStatus, string $deliveryStatus, string $note = ''): array
{
    if (!isset(payment_statuses()[$paymentStatus]) || !isset(delivery_statuses()[$deliveryStatus])) {
        return ['ok' => false, 'error' => 'Unknown status.'];
    }
    if ($order['delivery_status'] === 'cancelled' && $deliveryStatus !== 'cancelled') {
        return ['ok' => false, 'error' => 'A cancelled order stays cancelled. Place a new order if the school still needs the items.'];
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        $locked = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $locked->execute([(int) $order['id']]);
        $current = $locked->fetch();
        if (!$current) {
            throw new RuntimeException('Order not found.');
        }

        $deducted = (int) $current['stock_deducted'] === 1;
        $wantStockOut = $deliveryStatus !== 'cancelled'
            && !in_array($paymentStatus, ['failed', 'refunded'], true)
            && ($paymentStatus === 'paid' || ($current['payment_method'] === 'cod' && $paymentStatus === 'pending'));

        if ($deducted && !$wantStockOut) {
            restore_order_stock((int) $current['id']);
            $deducted = false;
        }
        if (!$deducted && $wantStockOut) {
            if (!deduct_order_stock((int) $current['id'])) {
                throw new RuntimeException('Not enough stock to confirm this order.');
            }
            $deducted = true;
        }
        if ($deliveryStatus === 'cancelled' && $current['delivery_status'] !== 'cancelled') {
            release_coupon($current['coupon_code'] ?: null);
        }

        $pdo->prepare('UPDATE orders SET payment_status = ?, delivery_status = ?, stock_deducted = ? WHERE id = ?')
            ->execute([$paymentStatus, $deliveryStatus, $deducted ? 1 : 0, (int) $current['id']]);

        if ($current['delivery_status'] !== $deliveryStatus) {
            $log = $note !== '' ? $note : 'Delivery status updated to ' . str_replace('_', ' ', $deliveryStatus);
            $pdo->prepare('INSERT INTO order_status_logs (order_id, status, note) VALUES (?, ?, ?)')
                ->execute([(int) $current['id'], $deliveryStatus, mb_substr($log, 0, 255)]);
        } elseif ($current['payment_status'] !== $paymentStatus) {
            $log = $note !== '' ? $note : 'Payment marked ' . $paymentStatus;
            $pdo->prepare('INSERT INTO order_status_logs (order_id, status, note) VALUES (?, ?, ?)')
                ->execute([(int) $current['id'], $paymentStatus, mb_substr($log, 0, 255)]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    return ['ok' => true];
}

function razorpay_ready(): bool
{
    return str_starts_with(RAZORPAY_KEY_ID, 'rzp_')
        && strlen(RAZORPAY_KEY_SECRET) >= 8;
}

function razorpay_api(string $method, string $path, ?array $body = null): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL is required for Razorpay.'];
    }
    $ch = curl_init('https://api.razorpay.com' . $path);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ];
    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false) {
        return ['ok' => false, 'error' => $error !== '' ? $error : 'Could not reach Razorpay.'];
    }
    $json = json_decode($raw, true);
    if ($code >= 300 || !is_array($json)) {
        $message = $json['error']['description'] ?? 'Razorpay request failed.';
        return ['ok' => false, 'error' => $message];
    }
    return ['ok' => true, 'data' => $json];
}

function razorpay_create_for_order(array $order): array
{
    if (!razorpay_ready()) {
        return ['ok' => false, 'error' => 'Razorpay keys are not configured.'];
    }
    if (!empty($order['razorpay_order_id'])) {
        return ['ok' => true, 'id' => $order['razorpay_order_id']];
    }
    $amount = (int) round(((float) $order['total']) * 100);
    $result = razorpay_api('POST', '/v1/orders', [
        'amount' => $amount,
        'currency' => 'INR',
        'receipt' => $order['order_number'],
        'payment_capture' => 1,
        'notes' => [
            'order_number' => $order['order_number'],
            'school' => $order['school_name'],
        ],
    ]);
    if (!$result['ok']) {
        return $result;
    }
    db()->prepare('UPDATE orders SET razorpay_order_id = ? WHERE id = ?')
        ->execute([$result['data']['id'], (int) $order['id']]);
    return ['ok' => true, 'id' => $result['data']['id']];
}

function mark_order_paid(array $order, ?string $paymentId): array
{
    if ($order['payment_status'] === 'paid') {
        return ['ok' => true];
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([(int) $order['id']]);
        $current = $stmt->fetch();
        if (!$current) {
            throw new RuntimeException('Order not found.');
        }
        if ($current['payment_status'] !== 'paid') {
            if (!(int) $current['stock_deducted']) {
                if (!deduct_order_stock((int) $current['id'])) {
                    $pdo->prepare('UPDATE orders SET notes = CONCAT(IFNULL(notes, \'\'), ?) WHERE id = ?')
                        ->execute(["\nStock check needed after payment.", (int) $current['id']]);
                } else {
                    $pdo->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?')->execute([(int) $current['id']]);
                }
            }
            $pdo->prepare('UPDATE orders SET payment_status = ?, razorpay_payment_id = ?, delivery_status = IF(delivery_status = \'placed\', \'confirmed\', delivery_status) WHERE id = ?')
                ->execute(['paid', $paymentId, (int) $current['id']]);
            $pdo->prepare('INSERT INTO order_status_logs (order_id, status, note) VALUES (?, ?, ?)')
                ->execute([(int) $current['id'], 'confirmed', 'Payment received']);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'Payment was received but the order could not be updated. Contact the store with your order number.'];
    }
    return ['ok' => true];
}

function verify_razorpay_payment(array $order, string $razorpayOrderId, string $paymentId, string $signature): array
{
    if (!razorpay_ready()) {
        return ['ok' => false, 'error' => 'Razorpay is not configured.'];
    }
    if ($order['payment_status'] === 'paid') {
        return ['ok' => true];
    }
    if (!hash_equals((string) $order['razorpay_order_id'], $razorpayOrderId)) {
        return ['ok' => false, 'error' => 'Payment does not match this order.'];
    }
    $expected = hash_hmac('sha256', $razorpayOrderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    if (!hash_equals($expected, $signature)) {
        db()->prepare('UPDATE orders SET payment_status = ? WHERE id = ? AND payment_status = ?')
            ->execute(['failed', (int) $order['id'], 'pending']);
        return ['ok' => false, 'error' => 'Payment verification failed.'];
    }
    return mark_order_paid($order, $paymentId);
}
