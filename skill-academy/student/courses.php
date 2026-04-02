<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/course.php';

require_role('student');
$user = current_user();
$studentId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $courseId = (int)($_POST['course_id'] ?? 0);
    if ($courseId > 0) {
        enroll_student($studentId, $courseId);
        header('Location: ' . APP_BASE_URL . '/student/course.php?course_id=' . $courseId);
        exit;
    }
}

$stmt = db()->prepare("SELECT c.id, c.title, c.slug, c.description, c.category, c.level, c.is_paid, c.price_inr, c.youtube_playlist_url, c.thumbnail_url,
                              u.full_name AS teacher_name,
                              e.id AS enrollment_id, e.progress_percent, e.completed_at
                       FROM courses c
                       JOIN users u ON u.id = c.teacher_id
                       LEFT JOIN enrollments e ON e.course_id = c.id AND e.student_id = ?
                       WHERE c.is_published = 1
                       ORDER BY c.is_paid ASC, c.created_at DESC");
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll();

render_header('Student Courses');
?>

<div class="grid">
    <div class="col-12">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Courses</h1>
            <p class="muted">Enroll to track progress and get certificates.</p>
        </div>
    </div>

    <?php foreach ($courses as $c): ?>
        <div class="col-4">
            <div class="card pad">
                <img class="course-thumb" alt="" src="<?= e($c['thumbnail_url'] ?: 'https://via.placeholder.com/1280x720?text=Course') ?>">
                <div class="course-title"><?= e($c['title']) ?></div>
                <div class="course-meta">
                    <span><?= e($c['category']) ?></span>
                    <span>•</span>
                    <span><?= e($c['level']) ?></span>
                    <span>•</span>
                    <span><?= $c['is_paid'] ? ('Paid ₹' . (int)$c['price_inr']) : 'Free' ?></span>
                </div>
                <p class="muted" style="margin:10px 0 0; font-size:14px; line-height:1.35">
                    <?= e(mb_strimwidth($c['description'], 0, 120, '...')) ?>
                </p>

                <div class="row gap" style="margin-top:14px; flex-wrap:wrap">
                    <a class="btn" target="_blank" rel="noopener" href="<?= e($c['youtube_playlist_url']) ?>">Playlist</a>

                    <?php if ($c['enrollment_id']): ?>
                        <a class="btn primary" href="<?= e(APP_BASE_URL) ?>/student/course.php?course_id=<?= (int)$c['id'] ?>">Open</a>
                    <?php else: ?>
                        <form method="post" style="margin:0" class="no-print">
                            <?= csrf_field() ?>
                            <input type="hidden" name="course_id" value="<?= (int)$c['id'] ?>">
                            <button class="btn primary" type="submit">Enroll</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="help">
                    Teacher: <?= e($c['teacher_name']) ?>
                    <?php if ($c['enrollment_id']): ?>
                        <br>Progress: <strong><?= (int)$c['progress_percent'] ?>%</strong>
                        <?php if ($c['completed_at']): ?>
                            <span class="pill" style="margin-left:8px">Completed</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php render_footer(); ?>

