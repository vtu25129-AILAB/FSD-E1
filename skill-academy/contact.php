<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/db.php';

$errors = [];
$sent = false;
$values = [
    'name' => '',
    'email' => '',
    'message' => '',
];

if (is_logged_in()) {
    $u = current_user();
    if ($u) {
        $values['name'] = (string)$u['full_name'];
        $values['email'] = (string)$u['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $values['name'] = trim((string)($_POST['name'] ?? ''));
    $values['email'] = trim((string)($_POST['email'] ?? ''));
    $values['message'] = trim((string)($_POST['message'] ?? ''));

    if ($values['name'] === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (mb_strlen($values['message']) < 10) {
        $errors[] = 'Message must be at least 10 characters.';
    }

    if (!$errors) {
        db()->prepare('INSERT INTO contacts (name, email, message) VALUES (?,?,?)')->execute([
            $values['name'],
            mb_strtolower($values['email']),
            $values['message'],
        ]);
        $sent = true;
        $values['message'] = '';
    }
}

render_header('Contact');
?>

<div class="grid">
    <div class="col-6">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Contact</h1>
            <p class="muted">Send us a message. We’ll respond through your email.</p>

            <?php if ($sent): ?>
                <div class="success-note" style="margin:12px 0">Message sent successfully.</div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="error" style="margin:12px 0">
                    <ul style="margin:0; padding-left:18px">
                        <?php foreach ($errors as $er): ?>
                            <li><?= e($er) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="no-print">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" name="name" value="<?= e($values['name']) ?>" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="<?= e($values['email']) ?>" required>
                </div>
                <div class="field">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" required><?= e($values['message']) ?></textarea>
                </div>
                <button class="btn primary block" type="submit">Send</button>
            </form>
        </div>
    </div>

    <div class="col-6">
        <div class="card pad">
            <h2 class="h2">Help</h2>
            <div class="muted" style="line-height:1.5">
                - Students: use the Doubts page for course questions.<br>
                - Teachers: answer doubts from your dashboard.<br>
                - Certificates: available after course completion.<br>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>

