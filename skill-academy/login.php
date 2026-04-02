<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    $u = current_user();
    if ($u) {
        redirect_by_role($u);
    }
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Enter your password.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, role, full_name, email, password_hash FROM users WHERE email = ?');
        $stmt->execute([mb_strtolower($email)]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            $errors[] = 'Invalid email or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
            redirect_by_role($user);
        }
    }
}

$success = flash_get('success');

render_header('Login');
?>

<div class="grid">
    <div class="col-6">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Welcome back</h1>
            <p class="muted">Login as student or teacher.</p>

            <?php if ($success): ?>
                <div class="success-note" style="margin:12px 0">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="error" style="margin:12px 0">
                    <strong>Fix these issues:</strong>
                    <ul style="margin:8px 0 0; padding-left:18px">
                        <?php foreach ($errors as $er): ?>
                            <li><?= e($er) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="no-print">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="<?= e($email) ?>" required>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>

                <button class="btn primary block" type="submit">Login</button>
            </form>

            <p class="help" style="margin-top:14px">New here? <a class="btn" href="<?= e(APP_BASE_URL) ?>/register.php">Register</a></p>
        </div>
    </div>

    <div class="col-6">
        <div class="card pad">
            <h2 class="h2">Demo account</h2>
            <p class="muted">After importing `database/schema.sql`, you can use:</p>
            <div class="card pad">
                <div><strong>Email:</strong> teacher@skillacademy.local</div>
                <div><strong>Password:</strong> Teacher@123</div>
            </div>
            <p class="help">You can also register new student/teacher accounts.</p>
        </div>
    </div>
</div>

<?php render_footer(); ?>

