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
$prefCourseId = (int)($_GET['course_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $courseId = (int)($_POST['course_id'] ?? 0);
    $subject = trim((string)($_POST['subject'] ?? ''));
    $question = trim((string)($_POST['question'] ?? ''));

    if ($courseId <= 0) {
        $errors[] = 'Select a course.';
    }
    if ($subject === '' || mb_strlen($subject) < 5) {
        $errors[] = 'Subject must be at least 5 characters.';
    }
    if ($question === '' || mb_strlen($question) < 10) {
        $errors[] = 'Question must be at least 10 characters.';
    }

    // Only allow doubts for enrolled courses
    if (!$errors) {
        $stmt = db()->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$studentId, $courseId]);
        if (!$stmt->fetch()) {
            $errors[] = 'You can ask doubts only for courses you enrolled in.';
        }
    }

    if (!$errors) {
        db()->prepare("INSERT INTO doubts (student_id, course_id, subject, question) VALUES (?,?,?,?)")
            ->execute([$studentId, $courseId, $subject, $question]);
        flash_set('success', 'Doubt submitted. A teacher will reply soon.');
        header('Location: ' . APP_BASE_URL . '/student/doubts.php');
        exit;
    }
}

$success = flash_get('success');

$stmt = db()->prepare("SELECT e.course_id, c.title
                       FROM enrollments e
                       JOIN courses c ON c.id = e.course_id
                       WHERE e.student_id = ?
                       ORDER BY e.enrolled_at DESC");
$stmt->execute([$studentId]);
$enrolledCourses = $stmt->fetchAll();

$stmt = db()->prepare("SELECT d.*, c.title AS course_title
                       FROM doubts d
                       JOIN courses c ON c.id = d.course_id
                       WHERE d.student_id = ?
                       ORDER BY d.created_at DESC");
$stmt->execute([$studentId]);
$doubts = $stmt->fetchAll();

render_header('Student Doubts');
?>

<div class="grid">
    <div class="col-6">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Ask a doubt</h1>
            <p class="muted">Ask your question related to an enrolled course.</p>

            <?php if ($success): ?>
                <div class="success-note" style="margin:12px 0"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error" style="margin:12px 0">
                    <ul style="margin:0; padding-left:18px">
                        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="no-print">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Select…</option>
                        <?php foreach ($enrolledCourses as $c): ?>
                            <option value="<?= (int)$c['course_id'] ?>" <?= ($prefCourseId === (int)$c['course_id']) ? 'selected' : '' ?>>
                                <?= e($c['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="subject">Subject</label>
                    <input id="subject" name="subject" placeholder="e.g., Stacks - push/pop confusion" required>
                </div>
                <div class="field">
                    <label for="question">Question</label>
                    <textarea id="question" name="question" placeholder="Describe your doubt clearly..." required></textarea>
                </div>

                <button class="btn primary block" type="submit">Submit doubt</button>
            </form>

            <?php if (!$enrolledCourses): ?>
                <div class="help">You haven’t enrolled in any course yet. <a class="btn" href="<?= e(APP_BASE_URL) ?>/student/courses.php">Enroll now</a></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-6">
        <div class="card pad">
            <h2 class="h2">Your doubts</h2>
            <?php if (!$doubts): ?>
                <p class="muted">No doubts yet.</p>
            <?php else: ?>
                <?php foreach ($doubts as $d): ?>
                    <div class="card pad" style="margin:12px 0; background: rgba(16,26,46,.35)">
                        <div class="row between center">
                            <div class="badge"><?= e($d['course_title']) ?></div>
                            <span class="pill"><?= e($d['status']) ?></span>
                        </div>
                        <div style="margin-top:10px"><strong><?= e($d['subject']) ?></strong></div>
                        <div class="muted" style="margin-top:8px; white-space:pre-wrap"><?= e($d['question']) ?></div>
                        <?php if (!empty($d['teacher_reply'])): ?>
                            <div class="card pad" style="margin-top:12px; border-color:rgba(34,197,94,.35)">
                                <strong>Teacher reply</strong>
                                <div class="muted" style="margin-top:8px; white-space:pre-wrap"><?= e((string)$d['teacher_reply']) ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="help">Asked: <?= e((string)$d['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php render_footer(); ?>

