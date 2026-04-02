<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('student');
$user = current_user();

$studentId = (int)$user['id'];

$stmt = db()->prepare("SELECT COUNT(*) c FROM enrollments WHERE student_id = ?");
$stmt->execute([$studentId]);
$enrolledCount = (int)$stmt->fetch()['c'];

$stmt = db()->prepare("SELECT COUNT(*) c FROM enrollments WHERE student_id = ? AND completed_at IS NOT NULL");
$stmt->execute([$studentId]);
$completedCount = (int)$stmt->fetch()['c'];

$stmt = db()->prepare("SELECT e.id AS enrollment_id, e.progress_percent, e.enrolled_at, e.completed_at,
                              c.id AS course_id, c.title, c.slug, c.category, c.level, c.is_paid, c.price_inr, c.thumbnail_url
                       FROM enrollments e
                       JOIN courses c ON c.id = e.course_id
                       WHERE e.student_id = ?
                       ORDER BY e.enrolled_at DESC
                       LIMIT 6");
$stmt->execute([$studentId]);
$recent = $stmt->fetchAll();

render_header('Student Home');
?>

<div class="grid">
    <div class="col-8">
        <div class="card pad">
            <div class="badge">Student dashboard</div>
            <h1 class="h1" style="font-size:34px">Welcome, <?= e((string)$user['full_name']) ?></h1>
            <p class="muted">Continue your learning, track progress, and download certificates after completion.</p>
            <div class="row gap" style="flex-wrap:wrap; margin-top:12px">
                <a class="btn primary" href="<?= e(APP_BASE_URL) ?>/student/courses.php">Browse courses</a>
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/student/doubts.php">Ask a doubt</a>
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/student/certificates.php">My certificates</a>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card pad">
            <h2 class="h2">Your stats</h2>
            <table class="table">
                <tbody>
                <tr><td>Enrolled</td><td><strong><?= e((string)$enrolledCount) ?></strong></td></tr>
                <tr><td>Completed</td><td><strong><?= e((string)$completedCount) ?></strong></td></tr>
                </tbody>
            </table>
            <div class="help">Tip: complete all 10 modules to generate a certificate.</div>
        </div>
    </div>

    <div class="col-12">
        <div class="row between center" style="margin:6px 0 10px">
            <h2 class="h2" style="margin:0">Recent enrollments</h2>
        </div>

        <?php if (!$recent): ?>
            <div class="card pad">
                <p class="muted" style="margin:0">No enrollments yet. Start with a free course.</p>
                <div style="margin-top:12px">
                    <a class="btn primary" href="<?= e(APP_BASE_URL) ?>/student/courses.php">Browse courses</a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($recent as $r): ?>
                    <div class="col-4">
                        <a class="card pad" href="<?= e(APP_BASE_URL) ?>/student/course.php?course_id=<?= (int)$r['course_id'] ?>">
                            <img class="course-thumb" alt="" src="<?= e($r['thumbnail_url'] ?: 'https://via.placeholder.com/1280x720?text=Course') ?>">
                            <div class="course-title"><?= e($r['title']) ?></div>
                            <div class="course-meta">
                                <span><?= e($r['category']) ?></span>
                                <span>•</span>
                                <span><?= e($r['level']) ?></span>
                                <span>•</span>
                                <span><?= $r['is_paid'] ? ('Paid ₹' . (int)$r['price_inr']) : 'Free' ?></span>
                            </div>
                            <div class="help">Progress: <strong><?= (int)$r['progress_percent'] ?>%</strong></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php render_footer(); ?>

