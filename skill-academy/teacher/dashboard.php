<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('teacher');
$user = current_user();
$teacherId = (int)$user['id'];

$stmt = db()->prepare("SELECT COUNT(*) c FROM courses WHERE teacher_id = ? AND is_published = 1");
$stmt->execute([$teacherId]);
$myCourses = (int)$stmt->fetch()['c'];

$students = (int)db()->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch()['c'];

$stmt = db()->prepare("SELECT COUNT(*) c
                       FROM enrollments e
                       JOIN courses c ON c.id = e.course_id
                       WHERE c.teacher_id = ?");
$stmt->execute([$teacherId]);
$myEnrollments = (int)$stmt->fetch()['c'];

$stmt = db()->prepare("SELECT COUNT(*) c
                       FROM enrollments e
                       JOIN courses c ON c.id = e.course_id
                       WHERE c.teacher_id = ? AND e.completed_at IS NOT NULL");
$stmt->execute([$teacherId]);
$myCompletions = (int)$stmt->fetch()['c'];

$stmt = db()->prepare("SELECT COUNT(*) c
                       FROM doubts d
                       JOIN courses c ON c.id = d.course_id
                       WHERE c.teacher_id = ? AND d.status = 'open'");
$stmt->execute([$teacherId]);
$openDoubts = (int)$stmt->fetch()['c'];

$stmt = db()->prepare("SELECT c.id, c.title, c.category, c.level, c.is_paid, c.price_inr,
                              COUNT(e.id) AS enrolled_count,
                              SUM(CASE WHEN e.completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_count
                       FROM courses c
                       LEFT JOIN enrollments e ON e.course_id = c.id
                       WHERE c.teacher_id = ?
                       GROUP BY c.id
                       ORDER BY c.created_at DESC
                       LIMIT 6");
$stmt->execute([$teacherId]);
$courseRows = $stmt->fetchAll();

render_header('Teacher Dashboard');
?>

<div class="grid">
    <div class="col-8">
        <div class="card pad">
            <div class="badge">Teacher dashboard</div>
            <h1 class="h1" style="font-size:34px">Welcome, <?= e((string)$user['full_name']) ?></h1>
            <p class="muted">Manage courses, track enrollments/completions, answer doubts, and share certificate links.</p>
            <div class="row gap" style="flex-wrap:wrap; margin-top:12px">
                <a class="btn primary" href="<?= e(APP_BASE_URL) ?>/teacher/courses.php">Add / manage courses</a>
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/teacher/enrollments.php">View enrollments</a>
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/teacher/doubts.php">Answer doubts</a>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card pad">
            <h2 class="h2">Stats</h2>
            <table class="table">
                <tbody>
                <tr><td>Students registered</td><td><strong><?= e((string)$students) ?></strong></td></tr>
                <tr><td>Your published courses</td><td><strong><?= e((string)$myCourses) ?></strong></td></tr>
                <tr><td>Your enrollments</td><td><strong><?= e((string)$myEnrollments) ?></strong></td></tr>
                <tr><td>Your completions</td><td><strong><?= e((string)$myCompletions) ?></strong></td></tr>
                <tr><td>Open doubts</td><td><strong><?= e((string)$openDoubts) ?></strong></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12">
        <div class="row between center" style="margin:6px 0 10px">
            <h2 class="h2" style="margin:0">Your courses (latest)</h2>
            <a class="btn" href="<?= e(APP_BASE_URL) ?>/teacher/courses.php">Manage all</a>
        </div>

        <?php if (!$courseRows): ?>
            <div class="card pad">
                <p class="muted" style="margin:0">No courses yet. Add your first course.</p>
                <div style="margin-top:12px"><a class="btn primary" href="<?= e(APP_BASE_URL) ?>/teacher/courses.php">Add course</a></div>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Course</th>
                    <th>Type</th>
                    <th>Enrolled</th>
                    <th>Completed</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($courseRows as $r): ?>
                    <tr>
                        <td>
                            <strong><?= e($r['title']) ?></strong>
                            <div class="muted" style="font-size:13px"><?= e($r['category']) ?> • <?= e($r['level']) ?></div>
                        </td>
                        <td><?= $r['is_paid'] ? ('Paid ₹' . (int)$r['price_inr']) : 'Free' ?></td>
                        <td><?= (int)$r['enrolled_count'] ?></td>
                        <td><?= (int)$r['completed_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php render_footer(); ?>

