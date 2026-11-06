<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect(url('admin/login.php'));
}
logout_user();
redirect(url('admin/login.php'));
