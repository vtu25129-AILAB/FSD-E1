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
$values = [
    'role' => 'student',
    'full_name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $role = (string)($_POST['role'] ?? '');
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    $values['role'] = $role;
    $values['full_name'] = $fullName;
    $values['email'] = $email;

    if (!in_array($role, ['student', 'teacher'], true)) {
        $errors[] = 'Role must be student or teacher.';
    }
    if ($fullName === '' || mb_strlen($fullName) < 3) {
        $errors[] = 'Full name must be at least 3 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = db()->prepare('INSERT INTO users (role, full_name, email, password_hash) VALUES (?,?,?,?)');
            $stmt->execute([$role, $fullName, mb_strtolower($email), $hash]);
            flash_set('success', 'Registration successful. Please login.');
            header('Location: ' . APP_BASE_URL . '/login.php');
            exit;
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062) {
                $errors[] = 'This email is already registered. Please login.';
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

render_header('Register');
?>

<div class="grid">
    <div class="col-6">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Create your account</h1>
            <p class="muted">This system supports only <strong>students</strong> and <strong>teachers</strong>.</p>

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
                    <label for="role">I am a</label>
                    <select id="role" name="role" required>
                        <option value="student" <?= $values['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="teacher" <?= $values['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                    </select>
                </div>

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" value="<?= e($values['full_name']) ?>" required>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="<?= e($values['email']) ?>" required>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" minlength="8" required>
                        <div class="help">Minimum 8 characters.</div>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm password</label>
                        <input id="confirm_password" type="password" name="confirm_password" minlength="8" required>
                    </div>
                </div>

                <button class="btn primary block" type="submit">Register</button>
            </form>

            <p class="help" style="margin-top:14px">Already have an account? <a class="btn" href="<?= e(APP_BASE_URL) ?>/login.php">Login</a></p>
        </div>
    </div>

    <div class="col-6">
        <div class="card pad">
            <h2 class="h2">What you can do</h2>
            <div class="grid">
                <div class="col-6">
                    <div class="card pad">
                        <strong>Student</strong>
                        <div class="muted" style="margin-top:8px; font-size:14px; line-height:1.35">
                            Enroll in courses, mark modules complete, raise doubts, and download certificates.
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card pad">
                        <strong>Teacher</strong>
                        <div class="muted" style="margin-top:8px; font-size:14px; line-height:1.35">
                            Add new courses, view enrollments and completions, answer student doubts, and share certificate links.
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card pad">
                        <div class="badge">Security</div>
                        <div class="muted" style="margin-top:10px">
                            Passwords are stored using secure hashing (`password_hash`), and forms are protected with CSRF tokens.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>

