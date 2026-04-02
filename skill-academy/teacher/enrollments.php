<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('teacher');
$user = current_user();
$teacherId = (int)$user['id'];

$filterCourseId = (int)($_GET['course_id'] ?? 0);

$coursesStmt = db()->prepare("SELECT id, title FROM courses WHERE teacher_id = ? ORDER BY created_at DESC");
$coursesStmt->execute([$teacherId]);
$myCourses = $coursesStmt->fetchAll();

$params = [$teacherId];
$where = "c.teacher_id = ?";
if ($filterCourseId > 0) {
    $where .= " AND c.id = ?";
    $params[] = $filterCourseId;
}

$stmt = db()->prepare("SELECT e.id AS enrollment_id, e.progress_percent, e.enrolled_at, e.completed_at,
                              s.id AS student_id, s.full_name AS student_name, s.email AS student_email,
                              c.id AS course_id, c.title AS course_title, c.is_paid, c.price_inr,
                              cert.certificate_code
                       FROM enrollments e
                       JOIN users s ON s.id = e.student_id
                       JOIN courses c ON c.id = e.course_id
                       LEFT JOIN certificates cert ON cert.enrollment_id = e.id
                       WHERE {$where}
                       ORDER BY e.enrolled_at DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Teacher Enrollments');
?>

<div class="grid">
    <div class="col-12">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Enrollments</h1>
            <p class="muted">See how many students enrolled and who completed (with certificate link).</p>
            <form method="get" class="no-print row gap center" style="margin-top:12px; flex-wrap:wrap">
                <div style="min-width:260px">
                    <select name="course_id">
                        <option value="0">All my courses</option>
                        <?php foreach ($myCourses as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $filterCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn" type="submit">Filter</button>
            </form>
        </div>
    </div>

    <div class="col-12">
        <?php if (!$rows): ?>
            <div class="card pad">
                <p class="muted" style="margin:0">No enrollments yet.</p>
            </div>
        <?php else: ?>
            <div class="card pad">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Certificate</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <strong><?= e($r['student_name']) ?></strong>
                                <div class="muted" style="font-size:13px"><?= e($r['student_email']) ?></div>
                            </td>
                            <td>
                                <strong><?= e($r['course_title']) ?></strong>
                                <div class="muted" style="font-size:13px"><?= $r['is_paid'] ? ('Paid ₹' . (int)$r['price_inr']) : 'Free' ?></div>
                            </td>
                            <td><strong><?= (int)$r['progress_percent'] ?>%</strong></td>
                            <td>
                                <?php if (!empty($r['completed_at'])): ?>
                                    <span class="pill" style="border-color:rgba(34,197,94,.55)">Completed</span>
                                <?php else: ?>
                                    <span class="pill">In progress</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['certificate_code'])): ?>
                                    <a class="btn success" href="<?= e(APP_BASE_URL) ?>/teacher/certificate.php?enrollment_id=<?= (int)$r['enrollment_id'] ?>">View/Download</a>
                                    <div class="help">Code: <strong><?= e($r['certificate_code']) ?></strong></div>
                                <?php else: ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php render_footer(); ?>

