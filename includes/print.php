<?php
declare(strict_types=1);

function ensure_print_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    db()->exec("CREATE TABLE IF NOT EXISTS print_jobs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NULL,
        session_id VARCHAR(128) NULL,
        order_id INT UNSIGNED NULL,
        kind ENUM('document', 'photo') NOT NULL,
        file_name VARCHAR(80) NOT NULL,
        original_name VARCHAR(180) NOT NULL,
        pages INT UNSIGNED NOT NULL,
        copies INT UNSIGNED NOT NULL,
        color TINYINT(1) NOT NULL DEFAULT 0,
        unit_price DECIMAL(10,2) NOT NULL,
        line_total DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_print_session (session_id, order_id),
        KEY idx_print_user (user_id, order_id),
        KEY idx_print_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function print_jobs_open(bool $lock = false): array
{
    [$userId, $sessionId] = cart_owner();
    $sql = 'SELECT * FROM print_jobs WHERE order_id IS NULL AND ';
    if ($userId) {
        $sql .= 'user_id = ?';
        $param = $userId;
    } else {
        $sql .= 'session_id = ? AND user_id IS NULL';
        $param = $sessionId;
    }
    $sql .= ' ORDER BY id' . ($lock ? ' FOR UPDATE' : '');
    $stmt = db()->prepare($sql);
    $stmt->execute([$param]);
    return $stmt->fetchAll();
}

function print_jobs_for_order(int $orderId): array
{
    $stmt = db()->prepare('SELECT * FROM print_jobs WHERE order_id = ? ORDER BY id');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function print_quote(string $kind, bool $color, int $pages, int $copies): array
{
    $pages = $kind === 'photo' ? 1 : $pages;
    $rate = $kind === 'photo' ? 15.0 : ($color ? 5.0 : 2.0);
    $unit = round($rate * $pages, 2);
    return [
        'unit_price' => $unit,
        'line_total' => round($unit * $copies, 2),
    ];
}

function print_label(array $job): string
{
    $copies = (int) $job['copies'] . ' ' . ((int) $job['copies'] === 1 ? 'copy' : 'copies');
    if ($job['kind'] === 'photo') {
        return 'Photo print · ' . $copies;
    }
    $tone = (int) $job['color'] ? 'Colour' : 'Black and white';
    $pages = (int) $job['pages'] . ' ' . ((int) $job['pages'] === 1 ? 'page' : 'pages');
    return $tone . ' document · ' . $pages . ' × ' . $copies;
}

function print_add(array $post, array $file): array
{
    $kind = ($post['kind'] ?? '') === 'photo' ? 'photo' : 'document';
    $color = $kind === 'photo' || !empty($post['color']);
    $pages = $kind === 'photo' ? 1 : (int) ($post['pages'] ?? 0);
    $copies = (int) ($post['copies'] ?? 0);
    if ($pages < 1 || $pages > 100) {
        return ['ok' => false, 'error' => 'Enter the number of pages, from 1 to 100.'];
    }
    if ($copies < 1 || $copies > 50) {
        return ['ok' => false, 'error' => 'Enter copies from 1 to 50.'];
    }
    if (count(print_jobs_open()) >= 5) {
        return ['ok' => false, 'error' => 'The cart already has 5 print files. Place the order or remove one.'];
    }
    $saved = print_save_file($file);
    if (!$saved['ok']) {
        return $saved;
    }
    $quote = print_quote($kind, $color, $pages, $copies);
    [$userId, $sessionId] = cart_owner();
    db()->prepare('INSERT INTO print_jobs (user_id, session_id, kind, file_name, original_name, pages, copies, color, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $userId,
            $sessionId,
            $kind,
            $saved['file'],
            $saved['original'],
            $pages,
            $copies,
            $color ? 1 : 0,
            $quote['unit_price'],
            $quote['line_total'],
        ]);
    cart_summary(true);
    return ['ok' => true];
}

function print_save_file(array $file): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Choose a PDF or a photo to print.'];
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'The file must be under 8 MB.'];
    }
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'The file could not be uploaded.'];
    }
    if ((int) ($file['size'] ?? 0) > 8 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'The file must be under 8 MB.'];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'The file could not be uploaded.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $map = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($map[$mime])) {
        return ['ok' => false, 'error' => 'Upload a PDF, JPG, PNG or WEBP file.'];
    }
    $handle = fopen($tmp, 'rb');
    $signature = $handle ? (string) fread($handle, 12) : '';
    if ($handle) {
        fclose($handle);
    }
    $valid = ($mime === 'application/pdf' && str_starts_with($signature, '%PDF'))
        || ($mime === 'image/jpeg' && str_starts_with($signature, "\xFF\xD8\xFF"))
        || ($mime === 'image/png' && str_starts_with($signature, "\x89PNG"))
        || ($mime === 'image/webp' && str_starts_with($signature, 'RIFF') && substr($signature, 8, 4) === 'WEBP');
    if (!$valid) {
        return ['ok' => false, 'error' => 'The file is not a valid PDF or photo.'];
    }
    $name = bin2hex(random_bytes(16)) . '.' . $map[$mime];
    $dir = dirname(__DIR__) . '/uploads/prints';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'The upload folder is not available.'];
    }
    if (!@move_uploaded_file($tmp, $dir . '/' . $name)) {
        return ['ok' => false, 'error' => 'Could not save the file.'];
    }
    $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? 'print')));
    $original = preg_replace('/[^\\p{L}\\p{N} ._()-]+/u', '', $original) ?? 'print';
    $original = trim($original);
    if ($original === '') {
        $original = 'print.' . $map[$mime];
    }
    return ['ok' => true, 'file' => $name, 'original' => mb_substr($original, 0, 180)];
}

function print_job_remove(int $id): array
{
    $job = print_job_by_id($id);
    if (!$job || $job['order_id'] !== null || !print_job_owned($job)) {
        return ['ok' => false, 'error' => 'That print is not in your cart.'];
    }
    db()->prepare('DELETE FROM print_jobs WHERE id = ? AND order_id IS NULL')->execute([$id]);
    print_delete_file((string) $job['file_name']);
    cart_summary(true);
    return ['ok' => true];
}

function print_job_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM print_jobs WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function print_job_owned(array $job): bool
{
    [$userId, $sessionId] = cart_owner();
    if ($userId && (int) $job['user_id'] === $userId) {
        return true;
    }
    return $job['user_id'] === null && (string) $job['session_id'] === $sessionId;
}

function can_download_print(array $job): bool
{
    if (is_admin()) {
        return true;
    }
    $user = current_user();
    if (!empty($job['order_id'])) {
        $order = find_order_by_id((int) $job['order_id']);
        if (!$order) {
            return false;
        }
        if ($user && $user['role'] === 'store' && (int) ($order['dark_store_id'] ?? 0) === (int) ($user['dark_store_id'] ?? 0)) {
            return true;
        }
        return can_view_order($order);
    }
    return print_job_owned($job);
}

function print_delete_file(string $name): void
{
    $name = basename($name);
    if (!preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png|webp)$/', $name)) {
        return;
    }
    $path = dirname(__DIR__) . '/uploads/prints/' . $name;
    if (is_file($path)) {
        unlink($path);
    }
}

function print_file_path(array $job): ?string
{
    $name = basename((string) $job['file_name']);
    if (!preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png|webp)$/', $name)) {
        return null;
    }
    $path = dirname(__DIR__) . '/uploads/prints/' . $name;
    return is_file($path) ? $path : null;
}

function claim_print_jobs(int $userId, string $oldSessionId, string $newSessionId): void
{
    db()->prepare('UPDATE print_jobs SET user_id = ?, session_id = ? WHERE session_id = ? AND user_id IS NULL AND order_id IS NULL')
        ->execute([$userId, $newSessionId, $oldSessionId]);
}

function attach_print_jobs(int $orderId, array $jobs): void
{
    if (!$jobs) {
        return;
    }
    $item = db()->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (?, NULL, ?, ?, ?, ?)');
    $claim = db()->prepare('UPDATE print_jobs SET order_id = ? WHERE id = ? AND order_id IS NULL');
    foreach ($jobs as $job) {
        $name = 'Print · ' . print_label($job);
        if (mb_strlen($name) > 180) {
            $name = mb_substr($name, 0, 180);
        }
        $item->execute([$orderId, $name, $job['unit_price'], (int) $job['copies'], $job['line_total']]);
        $claim->execute([$orderId, (int) $job['id']]);
    }
}
