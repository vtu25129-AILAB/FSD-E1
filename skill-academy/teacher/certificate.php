<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/course.php';

require_role('teacher');
$teacher = current_user();
$teacherId = (int)$teacher['id'];

$enrollmentId = (int)($_GET['enrollment_id'] ?? 0);
if ($enrollmentId <= 0) {
    http_response_code(400);
    exit('Missing enrollment_id.');
}

$stmt = db()->prepare("SELECT e.*, c.title, c.category, c.level, c.teacher_id, t.full_name AS teacher_name,
                              s.full_name AS student_name
                       FROM enrollments e
                       JOIN courses c ON c.id = e.course_id
                       JOIN users t ON t.id = c.teacher_id
                       JOIN users s ON s.id = e.student_id
                       WHERE e.id = ? AND c.teacher_id = ?");
$stmt->execute([$enrollmentId, $teacherId]);
$enroll = $stmt->fetch();
if (!$enroll) {
    http_response_code(404);
    exit('Not found.');
}
if (empty($enroll['completed_at'])) {
    http_response_code(400);
    exit('Student has not completed this course yet.');
}

$cert = get_certificate_for_enrollment($enrollmentId);
if (!$cert) {
    ensure_certificate($enrollmentId);
    $cert = get_certificate_for_enrollment($enrollmentId);
}

render_header('Certificate');
?>

<div class="card pad" style="padding:26px; position:relative; overflow:hidden">
    <div class="no-print row between center">
        <a class="btn" href="<?= e(APP_BASE_URL) ?>/teacher/enrollments.php">Back</a>
        <button class="btn success" onclick="window.print()">Download / Print</button>
    </div>

    <div style="margin-top:18px; text-align:center">
        <div class="badge">Certificate of Completion</div>
        <h1 class="h1" style="margin-top:14px; font-size:42px"><?= e(APP_NAME) ?></h1>
        <div class="muted" style="margin-top:6px">This is to certify that</div>
        <div style="margin-top:16px; font-size:34px; font-weight:900"><?= e((string)$enroll['student_name']) ?></div>
        <div class="muted" style="margin-top:10px">has successfully completed the course</div>
        <div style="margin-top:14px; font-size:24px; font-weight:800"><?= e((string)$enroll['title']) ?></div>

        <div class="grid" style="margin-top:18px; text-align:left">
            <div class="col-4">
                <div class="card pad" style="background: rgba(16,26,46,.35)">
                    <div class="muted" style="font-size:13px">Category</div>
                    <div><strong><?= e((string)$enroll['category']) ?></strong></div>
                </div>
            </div>
            <div class="col-4">
                <div class="card pad" style="background: rgba(16,26,46,.35)">
                    <div class="muted" style="font-size:13px">Level</div>
                    <div><strong><?= e((string)$enroll['level']) ?></strong></div>
                </div>
            </div>
            <div class="col-4">
                <div class="card pad" style="background: rgba(16,26,46,.35)">
                    <div class="muted" style="font-size:13px">Issued</div>
                    <div><strong><?= e((string)$cert['issued_at']) ?></strong></div>
                </div>
            </div>
        </div>

        <div class="row between center" style="margin-top:26px; gap:12px; flex-wrap:wrap">
            <div class="card pad" style="min-width:260px; background: rgba(16,26,46,.35)">
                <div class="muted" style="font-size:13px">Certificate code</div>
                <div style="font-size:20px; font-weight:900; letter-spacing:1px"><?= e((string)$cert['certificate_code']) ?></div>
            </div>
            <div class="card pad" style="min-width:260px; background: rgba(16,26,46,.35); text-align:left">
                <div class="muted" style="font-size:13px">Teacher</div>
                <div style="font-size:18px; font-weight:800"><?= e((string)$enroll['teacher_name']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>

