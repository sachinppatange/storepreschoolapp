<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    db()->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
    flash('success', 'Message marked as read.');
    redirect(url('admin/messages.php'));
}

$messages = db()->query('SELECT * FROM messages ORDER BY id DESC')->fetchAll();
$pageTitle = 'Messages';
$active = 'messages';
require __DIR__ . '/includes/header.php';
?>
<?php if (!$messages): ?><div class="panel">No messages yet. The contact form on the store sends them here.</div><?php endif; ?>
<?php foreach ($messages as $message): ?>
    <article class="panel" style="margin-bottom:10px;<?= (int) $message['is_read'] ? '' : 'border-color:#0c831f' ?>">
        <strong><?= e($message['subject']) ?></strong>
        <p><?= nl2br(e($message['message'])) ?></p>
        <p><?= e($message['name']) ?> · <?= e($message['email']) ?> · <?= e($message['phone']) ?> · <?= e(when($message['created_at'])) ?></p>
        <?php if (!(int) $message['is_read']): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $message['id'] ?>"><button class="btn" type="submit">Mark read</button></form>
        <?php endif; ?>
    </article>
<?php endforeach; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
