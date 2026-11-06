<?php
declare(strict_types=1);

function ensure_fulfillment_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = db();
    $role = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if ($role && !str_contains((string) $role['Type'], 'vendor')) {
        $pdo->exec("ALTER TABLE users MODIFY role ENUM('customer','admin','vendor','store') NOT NULL DEFAULT 'customer'");
        $role = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    }
    if ($role && !str_contains((string) $role['Type'], "'school'")) {
        $pdo->exec("ALTER TABLE users MODIFY role ENUM('customer','admin','vendor','store','school') NOT NULL DEFAULT 'customer'");
    }
    fulfillment_add_column('users', 'vendor_id', 'INT UNSIGNED NULL');
    fulfillment_add_column('users', 'dark_store_id', 'INT UNSIGNED NULL');
    fulfillment_add_column('users', 'referral_code', 'VARCHAR(16) NULL');
    fulfillment_add_column('users', 'referral_balance', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
    fulfillment_add_column('products', 'vendor_id', 'INT UNSIGNED NULL');
    fulfillment_add_column('orders', 'dark_store_id', 'INT UNSIGNED NULL');
    fulfillment_add_column('orders', 'referral_school_id', 'INT UNSIGNED NULL');
    fulfillment_add_column('orders', 'referral_earn', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
    fulfillment_add_column('orders', 'referral_spent', 'DECIMAL(10,2) NOT NULL DEFAULT 0');
    fulfillment_add_column('orders', 'referral_void', 'TINYINT(1) NOT NULL DEFAULT 0');
    $index = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $index->execute(['users', 'uniq_referral_code']);
    if ((int) $index->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_referral_code (referral_code)');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS vendors (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(140) NOT NULL,
        phone VARCHAR(15) NOT NULL,
        email VARCHAR(160) NOT NULL,
        city VARCHAR(80) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_vendor_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS dark_stores (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(140) NOT NULL,
        code VARCHAR(40) NOT NULL,
        address VARCHAR(255) NOT NULL,
        city VARCHAR(80) NOT NULL,
        pincode CHAR(6) NOT NULL,
        phone VARCHAR(15) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_store_code (code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS dark_store_pins (
        pincode CHAR(6) PRIMARY KEY,
        dark_store_id INT UNSIGNED NOT NULL,
        KEY idx_store_pin (dark_store_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS store_stock (
        dark_store_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        PRIMARY KEY (dark_store_id, product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_moves (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        dark_store_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        vendor_id INT UNSIGNED NULL,
        quantity INT NOT NULL,
        note VARCHAR(180) NOT NULL,
        user_id INT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_move_store (dark_store_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    seed_fulfillment();
    seed_school_account();
    assign_missing_referral_codes();
}

function fulfillment_add_column(string $table, string $column, string $definition): void
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    if ((int) $stmt->fetchColumn() === 0) {
        db()->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    }
}

function seed_fulfillment(): void
{
    $count = (int) db()->query('SELECT COUNT(*) FROM vendors')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $insertVendor = db()->prepare('INSERT INTO vendors (name, phone, email, city) VALUES (?, ?, ?, ?)');
    $insertVendor->execute(['Little Hands Supply', '9811111111', 'vendor.toys@storepreschoolapp.in', 'Pune']);
    $toys = (int) db()->lastInsertId();
    $insertVendor->execute(['PaperNest', '9822222222', 'vendor.books@storepreschoolapp.in', 'Pune']);
    $books = (int) db()->lastInsertId();
    $insertVendor->execute(['ClassBench', '9833333333', 'vendor.kits@storepreschoolapp.in', 'Kochi']);
    $kits = (int) db()->lastInsertId();

    $insertStore = db()->prepare('INSERT INTO dark_stores (name, code, address, city, pincode, phone) VALUES (?, ?, ?, ?, ?, ?)');
    $insertStore->execute(['Pune Camp Dark Store', 'PUNE-CAMP', '12 MG Road, Camp', 'Pune', '411001', '9844444444']);
    $pune = (int) db()->lastInsertId();
    $insertStore->execute(['Kochi Lake Dark Store', 'KOCHI-LAKE', '8 Lake View Road', 'Kochi', '682001', '9855555555']);
    $kochi = (int) db()->lastInsertId();

    $pin = db()->prepare('INSERT INTO dark_store_pins (pincode, dark_store_id) VALUES (?, ?)');
    $pin->execute(['411001', $pune]);
    $pin->execute(['411004', $pune]);
    $pin->execute(['682001', $kochi]);

    db()->prepare('UPDATE products SET vendor_id = ? WHERE category_id IN (1, 7)')->execute([$toys]);
    db()->prepare('UPDATE products SET vendor_id = ? WHERE category_id IN (2, 3)')->execute([$books]);
    db()->prepare('UPDATE products SET vendor_id = ? WHERE category_id IN (4, 5, 6, 8)')->execute([$kits]);

    $stock = db()->prepare('INSERT INTO store_stock (dark_store_id, product_id, quantity) VALUES (?, ?, ?)');
    foreach (db()->query('SELECT id, stock FROM products') as $product) {
        $qty = (int) $product['stock'];
        $kochiQty = $qty >= 4 ? 2 : 0;
        $stock->execute([$pune, (int) $product['id'], $qty - $kochiQty]);
        $stock->execute([$kochi, (int) $product['id'], $kochiQty]);
    }

    db()->prepare('UPDATE orders o JOIN dark_store_pins p ON p.pincode = o.pincode SET o.dark_store_id = p.dark_store_id WHERE o.dark_store_id IS NULL')->execute();

    fulfillment_staff($toys, 'Little Hands Vendor', 'vendor@storepreschoolapp.in', '9811111111', 'vendor', 'Vendor@123', $toys, null);
    fulfillment_staff($pune, 'Pune Dark Store', 'store@storepreschoolapp.in', '9844444444', 'store', 'Store@123', null, $pune);
    fulfillment_staff($kochi, 'Kochi Dark Store', 'store.kochi@storepreschoolapp.in', '9855555555', 'store', 'Store@123', null, $kochi);
}

function fulfillment_staff(int $unused, string $name, string $email, string $phone, string $role, string $password, ?int $vendorId, ?int $storeId): void
{
    unset($unused);
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return;
    }
    db()->prepare('INSERT INTO users (name, email, phone, password_hash, role, school_name, city, vendor_id, dark_store_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $name,
            $email,
            $phone,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $role === 'store' ? 'Dark store' : 'Vendor',
            'Pune',
            $vendorId,
            $storeId,
        ]);
}

function seed_school_account(): void
{
    $email = 'school@storepreschoolapp.in';
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return;
    }
    db()->prepare('INSERT INTO users (name, email, phone, password_hash, role, school_name, city) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            'Little Stars Preschool',
            $email,
            '9800000001',
            password_hash('School@123', PASSWORD_DEFAULT),
            'school',
            'Little Stars Preschool',
            'Pune',
        ]);
}

function school_referral_percent(): int
{
    $percent = defined('SCHOOL_REFERRAL_PERCENT') ? (int) SCHOOL_REFERRAL_PERCENT : 5;
    return max(0, min(20, $percent));
}

function make_referral_code(int $userId, string $label): string
{
    $base = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $label));
    $base = substr($base, 0, 12);
    if (strlen($base) < 4) {
        $base = 'SCHOOL' . $userId;
    }
    $code = $base;
    $try = 1;
    $stmt = db()->prepare('SELECT id FROM users WHERE referral_code = ? AND id <> ? LIMIT 1');
    while ($try < 100) {
        $stmt->execute([$code, $userId]);
        if (!$stmt->fetch()) {
            return $code;
        }
        $try++;
        $code = substr($base, 0, 10) . $try;
    }
    return 'S' . $userId;
}

function assign_missing_referral_codes(): void
{
    $rows = db()->query("SELECT id, school_name, name FROM users WHERE role = 'school' AND (referral_code IS NULL OR referral_code = '')")->fetchAll();
    $update = db()->prepare('UPDATE users SET referral_code = ? WHERE id = ?');
    foreach ($rows as $row) {
        $label = trim((string) $row['school_name']) !== '' ? (string) $row['school_name'] : (string) $row['name'];
        $update->execute([make_referral_code((int) $row['id'], $label), (int) $row['id']]);
    }
}

function school_by_referral(string $code): ?array
{
    $code = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    if ($code === '' || strlen($code) > 16) {
        return null;
    }
    $stmt = db()->prepare("SELECT id, name, email, school_name, referral_code FROM users WHERE referral_code = ? AND role = 'school' AND is_active = 1 LIMIT 1");
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function referral_share_url(string $code): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'store.preschoolapp.in');
    return ($https ? 'https://' : 'http://') . $host . url('index.php?ref=' . rawurlencode($code));
}

function remember_referral(): void
{
    $fromGet = array_key_exists('ref', $_GET);
    if (!$fromGet && !empty($_SESSION['referral_school_id'])) {
        return;
    }
    $raw = $fromGet ? (string) $_GET['ref'] : (string) ($_COOKIE['sp_ref'] ?? '');
    $school = school_by_referral($raw);
    if (!$school) {
        return;
    }
    $user = current_user();
    if ($user && (int) $user['id'] === (int) $school['id']) {
        return;
    }
    $_SESSION['referral_school_id'] = (int) $school['id'];
    $_SESSION['referral_school_name'] = (string) ($school['school_name'] !== '' ? $school['school_name'] : $school['name']);
    $_SESSION['referral_code'] = (string) $school['referral_code'];
    if ($fromGet && !headers_sent()) {
        setcookie('sp_ref', (string) $school['referral_code'], [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }
}

function current_referral(): ?array
{
    $id = (int) ($_SESSION['referral_school_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $user = current_user();
    if ($user && ((int) $user['id'] === $id || ($user['role'] ?? '') === 'school')) {
        return null;
    }
    return [
        'id' => $id,
        'school_name' => (string) ($_SESSION['referral_school_name'] ?? 'the school'),
        'code' => (string) ($_SESSION['referral_code'] ?? ''),
    ];
}

function referral_balance_of(int $userId, bool $lock = false): float
{
    $sql = 'SELECT referral_balance FROM users WHERE id = ? LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);
    return round(max(0, (float) $stmt->fetchColumn()), 2);
}

function attach_referral_preview(array $priced): array
{
    $spent = 0.0;
    $user = current_user();
    if ($user && ($user['role'] ?? '') === 'school' && ($priced['ok'] ?? false) !== false && isset($priced['total'])) {
        $balance = round(max(0, (float) ($user['referral_balance'] ?? 0)), 2);
        $spent = round(min($balance, (float) $priced['total']), 2);
        $priced['total'] = round((float) $priced['total'] - $spent, 2);
    }
    $priced['referral_spent'] = $spent;
    return $priced;
}

function lock_referral_for_order(array $priced, string $buyerEmail): array
{
    $empty = ['spent' => 0.0, 'earn' => 0.0, 'school_id' => null];
    $user = current_user();
    if ($user && ($user['role'] ?? '') === 'school') {
        $stmt = db()->prepare('SELECT referral_balance FROM users WHERE id = ? AND role = ? FOR UPDATE');
        $stmt->execute([(int) $user['id'], 'school']);
        $balance = round(max(0, (float) $stmt->fetchColumn()), 2);
        $spent = round(min($balance, (float) $priced['total']), 2);
        if ($spent <= 0) {
            return $empty;
        }
        $update = db()->prepare('UPDATE users SET referral_balance = referral_balance - ? WHERE id = ? AND referral_balance >= ?');
        $update->execute([$spent, (int) $user['id'], $spent]);
        if ($update->rowCount() !== 1) {
            return $empty;
        }
        return ['spent' => $spent, 'earn' => 0.0, 'school_id' => null];
    }

    $ref = current_referral();
    if (!$ref || school_referral_percent() <= 0) {
        return $empty;
    }
    $school = db()->prepare("SELECT id, email FROM users WHERE id = ? AND role = 'school' AND is_active = 1 FOR UPDATE");
    $school->execute([(int) $ref['id']]);
    $row = $school->fetch();
    if (!$row || strtolower((string) $row['email']) === strtolower(trim($buyerEmail))) {
        return $empty;
    }
    $base = round(max(0, (float) $priced['subtotal'] - (float) $priced['discount']), 2);
    $earn = round($base * school_referral_percent() / 100, 2);
    if ($earn <= 0) {
        return $empty;
    }
    db()->prepare('UPDATE users SET referral_balance = referral_balance + ? WHERE id = ?')->execute([$earn, (int) $row['id']]);
    return ['spent' => 0.0, 'earn' => $earn, 'school_id' => (int) $row['id']];
}

function reverse_referral(array $current, string $paymentStatus, string $deliveryStatus): void
{
    $dead = $deliveryStatus === 'cancelled' || in_array($paymentStatus, ['failed', 'refunded'], true);
    if (!$dead || (int) ($current['referral_void'] ?? 0) === 1) {
        return;
    }
    $earn = round((float) ($current['referral_earn'] ?? 0), 2);
    $spent = round((float) ($current['referral_spent'] ?? 0), 2);
    $schoolId = (int) ($current['referral_school_id'] ?? 0);
    if ($earn > 0 && $schoolId > 0) {
        db()->prepare('UPDATE users SET referral_balance = referral_balance - ? WHERE id = ?')->execute([$earn, $schoolId]);
    }
    if ($spent > 0 && !empty($current['user_id'])) {
        db()->prepare("UPDATE users SET referral_balance = referral_balance + ? WHERE id = ? AND role = 'school'")
            ->execute([$spent, (int) $current['user_id']]);
    }
    if ($earn > 0 || $spent > 0) {
        db()->prepare('UPDATE orders SET referral_void = 1 WHERE id = ?')->execute([(int) $current['id']]);
    }
}

function school_b2b_percent(): int
{
    $percent = defined('SCHOOL_B2B_PERCENT') ? (int) SCHOOL_B2B_PERCENT : 15;
    return max(0, min(50, $percent));
}

function is_school_buyer(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? '') === 'school';
}

function b2b_unit_price(float $price): float
{
    if (!is_school_buyer()) {
        return round($price, 2);
    }
    return round($price * (100 - school_b2b_percent()) / 100, 2);
}

function apply_b2b_prices(array $rows): array
{
    if (!is_school_buyer() || !$rows) {
        return $rows;
    }
    foreach ($rows as &$row) {
        if (isset($row['price'])) {
            $row['price'] = b2b_unit_price((float) $row['price']);
        }
    }
    unset($row);
    return $rows;
}

function role_home(array $user): string
{
    return match ($user['role'] ?? '') {
        'admin' => url('admin/index.php'),
        'vendor' => url('vendor/index.php'),
        'store' => url('store/index.php'),
        'school' => url('bulk-order.php'),
        default => url('account.php'),
    };
}

function require_vendor(): array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'vendor' || empty($user['vendor_id'])) {
        redirect(url('login.php?next=' . urlencode(current_path())));
    }
    return $user;
}

function require_store(): array
{
    $user = current_user();
    if (!$user || $user['role'] !== 'store' || empty($user['dark_store_id'])) {
        redirect(url('login.php?next=' . urlencode(current_path())));
    }
    return $user;
}

function vendors_all(): array
{
    return db()->query('SELECT * FROM vendors ORDER BY name')->fetchAll();
}

function dark_stores_all(): array
{
    return db()->query('SELECT * FROM dark_stores ORDER BY name')->fetchAll();
}

function dark_store_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM dark_stores WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function dark_store_for_pincode(string $pincode): ?array
{
    if (!preg_match('/^\d{6}$/', $pincode)) {
        return null;
    }
    $stmt = db()->prepare('SELECT s.* FROM dark_stores s JOIN dark_store_pins p ON p.dark_store_id = s.id WHERE p.pincode = ? AND s.is_active = 1 LIMIT 1');
    $stmt->execute([$pincode]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function delivery_areas(): array
{
    return db()->query('SELECT p.pincode, s.name, s.city
        FROM dark_store_pins p
        JOIN dark_stores s ON s.id = p.dark_store_id
        WHERE s.is_active = 1
        ORDER BY s.city, s.name, p.pincode')->fetchAll();
}

function delivery_pin(): string
{
    $pin = preg_replace('/\D/', '', (string) ($_SESSION['deliver_pin'] ?? '')) ?? '';
    return preg_match('/^\d{6}$/', $pin) ? $pin : '';
}

function current_delivery(): ?array
{
    $pin = delivery_pin();
    if ($pin === '') {
        return null;
    }
    $store = dark_store_for_pincode($pin);
    if (!$store) {
        unset($_SESSION['deliver_pin']);
        return null;
    }
    return ['pincode' => $pin, 'store' => $store];
}

function remember_delivery_from_user(?array $user): void
{
    if ($user === null || current_delivery()) {
        return;
    }
    $pin = preg_replace('/\D/', '', (string) ($user['pincode'] ?? '')) ?? '';
    if (dark_store_for_pincode($pin)) {
        $_SESSION['deliver_pin'] = $pin;
    }
}

function store_product_qty(int $storeId, int $productId): int
{
    $stmt = db()->prepare('SELECT quantity FROM store_stock WHERE dark_store_id = ? AND product_id = ?');
    $stmt->execute([$storeId, $productId]);
    return (int) $stmt->fetchColumn();
}

function overlay_store_stock(array $rows): array
{
    $delivery = current_delivery();
    if (!$delivery || !$rows) {
        return $rows;
    }
    $ids = [];
    foreach ($rows as $row) {
        $ids[] = (int) $row['id'];
    }
    $ids = array_values(array_unique(array_filter($ids)));
    if (!$ids) {
        return $rows;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT product_id, quantity FROM store_stock WHERE dark_store_id = ? AND product_id IN ($placeholders)");
    $stmt->execute(array_merge([(int) $delivery['store']['id']], $ids));
    $have = [];
    foreach ($stmt->fetchAll() as $row) {
        $have[(int) $row['product_id']] = (int) $row['quantity'];
    }
    foreach ($rows as &$row) {
        $row['stock'] = $have[(int) $row['id']] ?? 0;
    }
    unset($row);
    return $rows;
}

function trim_cart_for_delivery(): string
{
    if (!current_delivery()) {
        return '';
    }
    $changed = false;
    foreach (cart_lines() as $line) {
        $have = (int) $line['stock'];
        $qty = (int) $line['quantity'];
        if ($qty <= $have) {
            continue;
        }
        $changed = true;
        if ($have < 1) {
            db()->prepare('DELETE FROM cart_items WHERE id = ?')->execute([(int) $line['cart_id']]);
        } else {
            db()->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')->execute([$have, (int) $line['cart_id']]);
        }
    }
    if ($changed) {
        cart_summary(true);
        return ' Some cart quantities were reduced for this area.';
    }
    return '';
}

function set_delivery_pin(string $raw): array
{
    $pin = preg_replace('/\D/', '', $raw) ?? '';
    $store = dark_store_for_pincode($pin);
    if (!$store) {
        return ['ok' => false, 'error' => 'Choose an area we deliver to.'];
    }
    $_SESSION['deliver_pin'] = $pin;
    $note = trim_cart_for_delivery();
    return ['ok' => true, 'message' => 'Delivering to ' . $store['city'] . ' · ' . $store['name'] . ' (' . $pin . ').' . $note];
}

function store_pins(int $storeId): array
{
    $stmt = db()->prepare('SELECT pincode FROM dark_store_pins WHERE dark_store_id = ? ORDER BY pincode');
    $stmt->execute([$storeId]);
    return array_column($stmt->fetchAll(), 'pincode');
}

function store_stock_shortfall(int $storeId, array $items): ?string
{
    $stmt = db()->prepare('SELECT quantity FROM store_stock WHERE dark_store_id = ? AND product_id = ?');
    foreach ($items as $item) {
        $productId = (int) ($item['id'] ?? $item['product_id'] ?? 0);
        $qty = (int) ($item['qty'] ?? $item['quantity'] ?? 0);
        $stmt->execute([$storeId, $productId]);
        $have = (int) $stmt->fetchColumn();
        if ($have < $qty) {
            $name = (string) ($item['name'] ?? $item['product_name'] ?? 'An item');
            return $name . ' has only ' . $have . ' left at the dark store for this PIN code.';
        }
    }
    return null;
}

function add_store_stock(int $storeId, int $productId, int $qty): void
{
    db()->prepare('INSERT INTO store_stock (dark_store_id, product_id, quantity) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)')
        ->execute([$storeId, $productId, $qty]);
}

function receive_stock(int $storeId, int $productId, int $qty, int $userId, string $note, ?int $vendorId = null): array
{
    if ($qty < 1 || $qty > 500) {
        return ['ok' => false, 'error' => 'Enter a quantity from 1 to 500.'];
    }
    $store = dark_store_by_id($storeId);
    $productStmt = db()->prepare('SELECT id, name, vendor_id FROM products WHERE id = ? AND is_active = 1');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();
    if (!$store || !$store['is_active'] || !$product) {
        return ['ok' => false, 'error' => 'Choose an active dark store and product.'];
    }
    if ($vendorId !== null && (int) $product['vendor_id'] !== $vendorId) {
        return ['ok' => false, 'error' => 'That product belongs to another vendor.'];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        add_store_stock($storeId, $productId, $qty);
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$qty, $productId]);
        $pdo->prepare('INSERT INTO stock_moves (dark_store_id, product_id, vendor_id, quantity, note, user_id) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$storeId, $productId, $product['vendor_id'] ?: null, $qty, mb_substr($note !== '' ? $note : 'Stock received', 0, 180), $userId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'Could not add the stock.'];
    }
    return ['ok' => true];
}

function reassign_dark_store(array $order, int $newStoreId): array
{
    $currentId = (int) ($order['dark_store_id'] ?? 0);
    if ($newStoreId === $currentId) {
        return ['ok' => true];
    }
    if (!in_array($order['delivery_status'], ['placed', 'confirmed', 'packed'], true)) {
        return ['ok' => false, 'error' => 'This order is already with the rider. The dark store cannot be changed.'];
    }
    if ($newStoreId === 0) {
        if ((int) $order['stock_deducted'] === 1) {
            return ['ok' => false, 'error' => 'Choose another dark store. Stock is already reserved.'];
        }
        db()->prepare('UPDATE orders SET dark_store_id = NULL WHERE id = ?')->execute([(int) $order['id']]);
        return ['ok' => true];
    }
    $store = dark_store_by_id($newStoreId);
    if (!$store || !(int) $store['is_active']) {
        return ['ok' => false, 'error' => 'That dark store is not active.'];
    }

    $items = order_items((int) $order['id']);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ((int) $order['stock_deducted'] === 1) {
            $short = store_stock_shortfall($newStoreId, $items);
            if ($short) {
                throw new RuntimeException($short);
            }
            $take = $pdo->prepare('UPDATE store_stock SET quantity = quantity - ? WHERE dark_store_id = ? AND product_id = ? AND quantity >= ?');
            foreach ($items as $item) {
                if (!$item['product_id']) {
                    continue;
                }
                $qty = (int) $item['quantity'];
                $take->execute([$qty, $newStoreId, (int) $item['product_id'], $qty]);
                if ($take->rowCount() !== 1) {
                    throw new RuntimeException('Not enough stock at ' . $store['name'] . '.');
                }
                if ($currentId > 0) {
                    add_store_stock($currentId, (int) $item['product_id'], $qty);
                }
            }
        }
        $pdo->prepare('UPDATE orders SET dark_store_id = ? WHERE id = ?')->execute([$newStoreId, (int) $order['id']]);
        $pdo->prepare('INSERT INTO order_status_logs (order_id, status, note) VALUES (?, ?, ?)')
            ->execute([(int) $order['id'], 'store', 'Moved to ' . $store['name']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    return ['ok' => true];
}

function store_next_step(string $status): ?array
{
    return match ($status) {
        'placed' => ['confirmed', 'Accept order', 'Dark store accepted the order'],
        'confirmed' => ['packed', 'Mark packed', 'Packed at the dark store'],
        'packed' => ['out_for_delivery', 'Hand to rider', 'Handed to the delivery rider'],
        'shipped' => ['out_for_delivery', 'Out for delivery', 'Out for delivery from the dark store'],
        'out_for_delivery' => ['delivered', 'Mark delivered', 'Delivered to the school'],
        default => null,
    };
}

function store_advance_order(array $order, int $storeId): array
{
    if ((int) ($order['dark_store_id'] ?? 0) !== $storeId) {
        return ['ok' => false, 'error' => 'This order is for another dark store.'];
    }
    $step = store_next_step((string) $order['delivery_status']);
    if (!$step) {
        return ['ok' => false, 'error' => 'This order has no further dark-store step.'];
    }
    [$status, , $note] = $step;
    $payment = (string) $order['payment_status'];
    if ($status === 'delivered' && $order['payment_method'] === 'cod' && $payment === 'pending') {
        $payment = 'paid';
        $note = 'Delivered and cash collected';
    }
    return change_order_state($order, $payment, $status, $note);
}
