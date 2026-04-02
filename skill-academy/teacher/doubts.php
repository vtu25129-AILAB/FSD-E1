<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';

require_role('teacher');
$teacher = current_user();
$teacherId = (int)$teacher['id'];

$errors = [];
$ok = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $doubtId = (int)($_POST['doubt_id'] ?? 0);
    $reply = trim((string)($_POST['reply'] ?? ''));
    if ($doubtId <= 0) {
        $errors[] = 'Invalid doubt.';
    }
    if ($reply === '' || mb_strlen($reply) < 5) {
        $errors[] = 'Reply must be at least 5 characters.';
    }

    if (!$errors) {
        // Ensure this doubt belongs to teacher's course
        $stmt = db()->prepare("SELECT d.id
                               FROM doubts d
                               JOIN courses c ON c.id = d.course_id
                               WHERE d.id = ? AND c.teacher_id = ?");
        $stmt->execute([$doubtId, $teacherId]);
        if (!$stmt->fetch()) {
            $errors[] = 'You cannot reply to this doubt.';
        } else {
            db()->prepare("UPDATE doubts
                           SET teacher_reply = ?, status = 'answered', answered_at = NOW()
                           WHERE id = ?")
                ->execute([$reply, $doubtId]);
            flash_set('success', 'Reply sent.');
            header('Location: ' . APP_BASE_URL . '/teacher/doubts.php');
            exit;
        }
    }
}

$stmt = db()->prepare("SELECT d.*, c.title AS course_title, s.full_name AS student_name, s.email AS student_email
                       FROM doubts d
                       JOIN courses c ON c.id = d.course_id
                       JOIN users s ON s.id = d.student_id
                       WHERE c.teacher_id = ?
                       ORDER BY (d.status = 'open') DESC, d.created_at DESC");
$stmt->execute([$teacherId]);
$doubts = $stmt->fetchAll();

render_header('Teacher Doubts');
?>

<div class="grid">
    <div class="col-12">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Doubts</h1>
            <p class="muted">Answer student doubts from your courses.</p>

            <?php if ($ok): ?>
                <div class="success-note" style="margin-top:12px"><?= e($ok) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error" style="margin-top:12px">
                    <ul style="margin:0; padding-left:18px">
                        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12">
        <?php if (!$doubts): ?>
            <div class="card pad">
                <p class="muted" style="margin:0">No doubts yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($doubts as $d): ?>
                <div class="card pad" style="margin-bottom:14px">
                    <div class="row between center gap" style="flex-wrap:wrap">
                        <div class="badge"><?= e($d['course_title']) ?></div>
                        <span class="pill"><?= e($d['status']) ?></span>
                    </div>
                    <div style="margin-top:10px">
                        <strong><?= e($d['subject']) ?></strong>
                        <div class="help">Student: <?= e($d['student_name']) ?> (<?= e($d['student_email']) ?>)</div>
                        <div class="muted" style="margin-top:10px; white-space:pre-wrap"><?= e((string)$d['question']) ?></div>
                    </div>

                    <?php if (!empty($d['teacher_reply'])): ?>
                        <div class="card pad" style="margin-top:12px; border-color:rgba(34,197,94,.35)">
                            <strong>Your reply</strong>
                            <div class="muted" style="margin-top:8px; white-space:pre-wrap"><?= e((string)$d['teacher_reply']) ?></div>
                            <div class="help">Answered: <?= e((string)$d['answered_at']) ?></div>
                        </div>
                    <?php else: ?>
                        <form method="post" class="no-print" style="margin-top:12px">
                            <?= csrf_field() ?>
                            <input type="hidden" name="doubt_id" value="<?= (int)$d['id'] ?>">
                            <div class="field">
                                <label for="reply-<?= (int)$d['id'] ?>">Reply</label>
                                <textarea id="reply-<?= (int)$d['id'] ?>" name="reply" required></textarea>
                            </div>
                            <button class="btn success" type="submit">Send reply</button>
                        </form>
                    <?php endif; ?>

                    <div class="help">Asked: <?= e((string)$d['created_at']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php render_footer(); ?>

