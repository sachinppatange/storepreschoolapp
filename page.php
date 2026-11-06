<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';

$pages = [
    'about' => [
        'About',
        '<p>Store Preschool is the shop for Store.PreschoolApp.in. Parents and schools can order toys, books, stationery, art materials, furniture and class kits for Nursery, Jr. KG and Sr. KG.</p><p>Orders can be delivered to a school office or a home address. Schools that need a full class list can use bulk order.</p>',
    ],
    'shipping' => [
        'Delivery',
        '<p>Delivery is free when the order total after discount is ' . money(FREE_DELIVERY_ABOVE) . ' or more. Smaller orders have a delivery fee of ' . money(DELIVERY_FEE) . '.</p><p>School deliveries are handed to the office named at checkout. Most orders leave the store within 1–2 working days and reach schools in 3–7 days, depending on the city.</p>',
    ],
    'privacy' => [
        'Privacy',
        '<p>We store the name, email, mobile number, school and delivery address you give us so we can fulfil the order and show it in your account.</p><p>Passwords are stored as a one-way hash. Payment card details are handled by Razorpay and are not saved in this store.</p>',
    ],
    'terms' => [
        'Terms',
        '<p>Prices and stock on the product page are confirmed again when the order is placed. If an item sells out, the order is not completed for that item.</p><p>You can cancel an order while it is still placed or confirmed. After it is packed, contact the store from the contact page.</p>',
    ],
];

$key = (string) ($_GET['p'] ?? '');
if ($key === 'contact') {
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = normalize_phone((string) ($_POST['mobile'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors[] = 'Enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (!valid_phone($phone)) {
            $errors[] = 'Enter a valid mobile number.';
        }
        if (mb_strlen($subject) < 3 || mb_strlen($subject) > 160) {
            $errors[] = 'Enter a subject.';
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 1000) {
            $errors[] = 'Write a message of at least 10 characters.';
        }
        if (!$errors) {
            db()->prepare('INSERT INTO messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)')
                ->execute([$name, $email, $phone, $subject, $message]);
            flash('success', 'Message sent. The store team can see it in the admin panel.');
            redirect(url('page.php?p=contact'));
        }
        remember_old($_POST, ['name', 'email', 'mobile', 'subject', 'message']);
    }
    $pageTitle = 'Contact';
    $contactUser = current_user();
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="wrap" style="max-width:640px">
        <div class="page-head"><h1>Contact</h1><p class="muted">Questions about a school order, a kit or a delivery.</p></div>
        <?php if ($errors): ?><ul class="errors"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <form method="post" class="panel">
            <?= csrf_field() ?>
            <label class="field">Name<input name="name" required value="<?= old('name', (string) ($contactUser['name'] ?? '')) ?>"></label>
            <label class="field">Email<input type="email" name="email" required value="<?= old('email', (string) ($contactUser['email'] ?? '')) ?>"></label>
            <label class="field">Mobile<input name="mobile" required value="<?= old('mobile', (string) ($contactUser['phone'] ?? '')) ?>"></label>
            <label class="field">Subject<input name="subject" required value="<?= old('subject') ?>"></label>
            <label class="field">Message<textarea name="message" rows="5" required><?= old('message') ?></textarea></label>
            <button class="btn btn-green" type="submit">Send message</button>
        </form>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

if (!isset($pages[$key])) {
    http_response_code(404);
    $pageTitle = 'Page not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="wrap"><div class="empty"><h1>Page not found</h1></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $pages[$key][0];
require __DIR__ . '/includes/header.php';
?>
<div class="wrap" style="max-width:760px">
    <div class="page-head"><h1><?= e($pages[$key][0]) ?></h1></div>
    <div class="panel"><?= $pages[$key][1] ?></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
