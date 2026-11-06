<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string
{
    return (string) ($_SESSION['csrf'] ?? '');
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($token) && $token !== '' && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function require_csrf(): void
{
    if (csrf_valid()) {
        return;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($uri, '/api/') || str_contains($accept, 'application/json')) {
        json_out(['ok' => false, 'error' => 'Session expired. Refresh the page and try again.'], 419);
    }
    http_response_code(419);
    $pageTitle = 'Session expired';
    require __DIR__ . '/header.php';
    echo '<div class="wrap page-narrow"><h1>Session expired</h1><p>Go back, refresh the page, and submit the form again.</p></div>';
    require __DIR__ . '/footer.php';
    exit;
}

function flash(?string $key = null, ?string $message = null): mixed
{
    if ($key === null) {
        return null;
    }
    if ($message === null) {
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }
    $_SESSION['flash'][$key] = $message;
    return null;
}

function remember_old(array $source, array $keys): void
{
    $old = [];
    foreach ($keys as $key) {
        $value = $source[$key] ?? '';
        $old[$key] = is_scalar($value) ? (string) $value : '';
    }
    $_SESSION['old'] = $old;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function money(mixed $amount): string
{
    $amount = round((float) $amount, 2);
    $hasPaise = abs($amount - floor($amount)) > 0.001;
    return '₹' . number_format($amount, $hasPaise ? 2 : 0);
}

function discount_percent(mixed $price, mixed $mrp): int
{
    $price = (float) $price;
    $mrp = (float) $mrp;
    if ($mrp <= 0 || $price >= $mrp) {
        return 0;
    }
    return (int) round((($mrp - $price) / $mrp) * 100);
}

function when(?string $timestamp): string
{
    if (!$timestamp) {
        return '';
    }
    $time = strtotime($timestamp);
    return $time ? date('d M Y, h:i A', $time) : $timestamp;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

function unique_slug(string $name, string $table, int $ignoreId = 0): string
{
    if (!in_array($table, ['products', 'categories'], true)) {
        throw new InvalidArgumentException('Unknown table');
    }
    $base = slugify($name);
    $slug = $base;
    $i = 2;
    $stmt = db()->prepare("SELECT id FROM {$table} WHERE slug = ? AND id <> ?");
    while (true) {
        $stmt->execute([$slug, $ignoreId]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone) ?? '';
    if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
        $digits = substr($digits, 2);
    }
    if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }
    return $digits;
}

function valid_phone(string $phone): bool
{
    return (bool) preg_match('/^[6-9]\d{9}$/', $phone);
}

function valid_password(string $password): bool
{
    return strlen($password) >= 8
        && strlen($password) <= 72
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/\d/', $password);
}

function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTime && $date->format('Y-m-d') === $value;
}

function like_escape(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

function current_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? url();
    return is_string($uri) ? $uri : url();
}

function safe_return(?string $target, string $fallback): string
{
    if (!is_string($target) || $target === '' || strlen($target) > 300) {
        return $fallback;
    }
    $parts = parse_url($target);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
        return $fallback;
    }
    $path = $parts['path'] ?? '';
    $base = rtrim(BASE_URL, '/');
    if ($base !== '' && !str_starts_with($path, $base)) {
        return $fallback;
    }
    if ($base === '' && !str_starts_with($path, '/')) {
        return $fallback;
    }
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    return $path . $query;
}

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row || !(int) $row['is_active']) {
        unset($_SESSION['user_id']);
        return null;
    }
    $user = $row;
    return $user;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function require_login(): void
{
    if (!current_user()) {
        redirect(url('login.php?next=' . urlencode(current_path())));
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        redirect(url('admin/login.php'));
    }
}

function attempt_login(string $email, string $password, bool $adminOnly = false): array
{
    $lock = (int) ($_SESSION['login_lock'] ?? 0);
    if ($lock > time()) {
        $mins = (int) ceil(($lock - time()) / 60);
        return ['ok' => false, 'error' => 'Too many attempts. Try again in ' . $mins . ' minute' . ($mins === 1 ? '' : 's') . '.'];
    }

    $email = strtolower(trim($email));
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $dummy = '$2y$10$xEvaCt.hhjIf/Qfxykm7WOVmn5mzjV0aLdbxd2hRmcYZJGsWuWcHO';
    $hash = $user['password_hash'] ?? $dummy;
    $valid = password_verify($password, $hash);

    if (!$user || !$valid) {
        $_SESSION['login_fails'] = (int) ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_lock'] = time() + 600;
            $_SESSION['login_fails'] = 0;
        }
        return ['ok' => false, 'error' => 'Email or password is incorrect.'];
    }
    if (!(int) $user['is_active']) {
        return ['ok' => false, 'error' => 'This account is disabled. Contact the store.'];
    }
    if ($adminOnly && $user['role'] !== 'admin') {
        return ['ok' => false, 'error' => 'This login is for store administrators.'];
    }

    $oldSession = session_id();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['login_fails'] = 0;
    unset($_SESSION['login_lock']);
    if (function_exists('merge_cart')) {
        merge_cart((int) $user['id'], $oldSession);
    }
    if (function_exists('remember_delivery_from_user')) {
        remember_delivery_from_user($user);
        trim_cart_for_delivery();
    }
    return ['ok' => true, 'user' => $user];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    session_start();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function category_tint(int $categoryId): string
{
    $tints = [
        1 => '#fff4d6',
        2 => '#e7f0ff',
        3 => '#fde8f3',
        4 => '#f3e8ff',
        5 => '#e8f6ef',
        6 => '#e7f7f5',
        7 => '#fff0e6',
        8 => '#e5f6e8',
    ];
    return $tints[$categoryId] ?? '#f3f4f6';
}

function status_badge(string $status): string
{
    $map = [
        'placed' => 'warn',
        'confirmed' => 'info',
        'packed' => 'info',
        'shipped' => 'info',
        'out_for_delivery' => 'info',
        'delivered' => 'ok',
        'cancelled' => 'bad',
        'pending' => 'warn',
        'paid' => 'ok',
        'failed' => 'bad',
        'refunded' => 'bad',
        'retail' => 'info',
        'bulk' => 'info',
    ];
    $class = $map[$status] ?? 'info';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="pill pill-' . $class . '">' . e($label) . '</span>';
}

function delivery_statuses(): array
{
    return [
        'placed' => 'Placed',
        'confirmed' => 'Confirmed',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
}

function payment_statuses(): array
{
    return [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];
}

function store_upload(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'file' => null];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'The image could not be uploaded.'];
    }
    if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Image must be under 2 MB.'];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'The image could not be uploaded.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($map[$mime])) {
        return ['ok' => false, 'error' => 'Upload a JPG, PNG, WEBP or GIF image.'];
    }
    $handle = fopen($tmp, 'rb');
    $signature = $handle ? (string) fread($handle, 12) : '';
    if ($handle) {
        fclose($handle);
    }
    $valid = ($mime === 'image/jpeg' && str_starts_with($signature, "\xFF\xD8\xFF"))
        || ($mime === 'image/png' && str_starts_with($signature, "\x89PNG"))
        || ($mime === 'image/gif' && (str_starts_with($signature, 'GIF87a') || str_starts_with($signature, 'GIF89a')))
        || ($mime === 'image/webp' && str_starts_with($signature, 'RIFF') && substr($signature, 8, 4) === 'WEBP');
    if (!$valid) {
        return ['ok' => false, 'error' => 'The file is not a valid image.'];
    }
    $name = bin2hex(random_bytes(8)) . '.' . $map[$mime];
    $dir = dirname(__DIR__) . '/uploads/products';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'The upload folder is not available.'];
    }
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        return ['ok' => false, 'error' => 'Could not save the image.'];
    }
    return ['ok' => true, 'file' => $name];
}

function delete_upload(?string $name): void
{
    if (!$name) {
        return;
    }
    $name = basename($name);
    if (!preg_match('/^[a-f0-9]{16}\.(jpg|png|webp|gif)$/', $name)) {
        return;
    }
    $path = dirname(__DIR__) . '/uploads/products/' . $name;
    if (is_file($path)) {
        unlink($path);
    }
}

function render_setup_screen(string $detail = ''): never
{
    http_response_code(503);
    $debug = APP_DEBUG ? $detail : '';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Set up Store Preschool</title>';
    echo '<style>body{font-family:Nunito,sans-serif;background:#f4f6f5;color:#1a1a1a;margin:0;padding:32px}';
    echo '.card{max-width:560px;margin:40px auto;background:#fff;border-radius:16px;padding:28px;border:1px solid #e5e7eb}';
    echo 'a{display:inline-block;margin-top:12px;background:#0c831f;color:#fff;text-decoration:none;padding:10px 16px;border-radius:10px;font-weight:700}</style></head><body>';
    echo '<div class="card"><h1>Database not ready</h1><p>Store Preschool needs the MySQL database before the shop can open.</p>';
    echo '<a href="' . e(url('install.php')) . '">Run the installer</a>';
    if ($debug !== '') {
        echo '<p style="color:#6b7280;font-size:14px">' . e($debug) . '</p>';
    }
    echo '</div></body></html>';
    exit;
}

function validate_customer_fields(array $post): array
{
    $errors = [];
    $data = [];

    $name = trim((string) ($post['name'] ?? ''));
    if (!preg_match("/^[\\p{L}\\s.'-]{2,80}$/u", $name)) {
        $errors[] = 'Enter the parent or contact name.';
    }
    $data['name'] = $name;

    $email = strtolower(trim((string) ($post['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
        $errors[] = 'Enter a valid email address.';
    }
    $data['email'] = $email;

    $school = trim((string) ($post['school_name'] ?? ''));
    if (mb_strlen($school) < 2 || mb_strlen($school) > 180) {
        $errors[] = 'Enter the school name.';
    }
    $data['school_name'] = $school;

    $phone = normalize_phone((string) ($post['mobile'] ?? ''));
    if (!valid_phone($phone)) {
        $errors[] = 'Enter a valid 10-digit mobile number.';
    }
    $data['mobile'] = $phone;

    $address = trim((string) ($post['address'] ?? ''));
    if (mb_strlen($address) < 10 || mb_strlen($address) > 500) {
        $errors[] = 'Enter a complete delivery address (at least 10 characters).';
    }
    $data['address'] = $address;

    $city = trim((string) ($post['city'] ?? ''));
    if (!preg_match("/^[\\p{L}\\s.'-]{2,60}$/u", $city)) {
        $errors[] = 'Enter a valid city.';
    }
    $data['city'] = $city;

    $pin = preg_replace('/\D/', '', (string) ($post['pincode'] ?? '')) ?? '';
    if (!preg_match('/^\d{6}$/', $pin)) {
        $errors[] = 'Enter a valid 6-digit PIN code.';
    }
    $data['pincode'] = $pin;

    $notes = trim((string) ($post['notes'] ?? ''));
    if (mb_strlen($notes) > 500) {
        $errors[] = 'Notes must be under 500 characters.';
    }
    $data['notes'] = $notes;

    return [$errors, $data];
}
