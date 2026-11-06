<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$job = print_job_by_id((int) ($_GET['job'] ?? 0));
$path = $job && can_download_print($job) ? print_file_path($job) : null;
if (!$job || !$path) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', (string) $job['original_name']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
