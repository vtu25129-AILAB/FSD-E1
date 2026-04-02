<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';

require_role('student');
$user = current_user();
$studentId = (int)$user['id'];

$errors = [];
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        if ($fullName === '' || mb_strlen($fullName) < 3) {
            $errors[] = 'Full name must be at least 3 characters.';
        }
        if (!$errors) {
            db()->prepare("UPDATE users SET full_name = ? WHERE id = ?")->execute([$fullName, $studentId]);
            $ok = 'Profile updated.';
            $user = current_user() ?: $user;
        }
    }

    if ($action === 'change_password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (mb_strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (!$errors) {
            $stmt = db()->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$studentId]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($current, (string)$row['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $studentId]);
                $ok = 'Password updated.';
            }
        }
    }
}

render_header('Student Profile');
?>

<div class="grid">
    <div class="col-6">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Profile</h1>

            <?php if ($ok): ?>
                <div class="success-note" style="margin:12px 0"><?= e($ok) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error" style="margin:12px 0">
                    <ul style="margin:0; padding-left:18px">
                        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card pad" style="margin:12px 0">
                <div><strong>Email:</strong> <?= e((string)$user['email']) ?></div>
                <div><strong>Role:</strong> Student</div>
                <div class="help">Joined: <?= e((string)$user['created_at']) ?></div>
            </div>

            <form method="post" class="no-print">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" value="<?= e((string)$user['full_name']) ?>" required>
                </div>
                <button class="btn primary" type="submit">Update profile</button>
            </form>
        </div>
    </div>

    <div class="col-6">
        <div class="card pad">
            <h2 class="h2">Change password</h2>
            <form method="post" class="no-print">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">
                <div class="field">
                    <label for="current_password">Current password</label>
                    <input id="current_password" type="password" name="current_password" required>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label for="new_password">New password</label>
                        <input id="new_password" type="password" name="new_password" minlength="8" required>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm new password</label>
                        <input id="confirm_password" type="password" name="confirm_password" minlength="8" required>
                    </div>
                </div>
                <button class="btn" type="submit">Update password</button>
            </form>
        </div>
    </div>
</div>

<?php render_footer(); ?>

