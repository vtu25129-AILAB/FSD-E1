<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('student');
$user = current_user();
$studentId = (int)$user['id'];

$stmt = db()->prepare("SELECT cert.certificate_code, cert.issued_at,
                              e.id AS enrollment_id, e.completed_at,
                              c.title, c.category, c.level, c.thumbnail_url
                       FROM certificates cert
                       JOIN enrollments e ON e.id = cert.enrollment_id
                       JOIN courses c ON c.id = e.course_id
                       WHERE e.student_id = ?
                       ORDER BY cert.issued_at DESC");
$stmt->execute([$studentId]);
$certs = $stmt->fetchAll();

render_header('Certificates');
?>

<div class="grid">
    <div class="col-12">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">My certificates</h1>
            <p class="muted">Certificates are generated after completing all modules of a course.</p>
        </div>
    </div>

    <?php if (!$certs): ?>
        <div class="col-12">
            <div class="card pad">
                <p class="muted" style="margin:0">No certificates yet. Complete a course to unlock certificate download.</p>
                <div style="margin-top:12px">
                    <a class="btn primary" href="<?= e(APP_BASE_URL) ?>/student/courses.php">Go to courses</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($certs as $c): ?>
            <div class="col-4">
                <div class="card pad">
                    <img class="course-thumb" alt="" src="<?= e($c['thumbnail_url'] ?: 'https://via.placeholder.com/1280x720?text=Course') ?>">
                    <div class="course-title"><?= e($c['title']) ?></div>
                    <div class="course-meta">
                        <span><?= e($c['category']) ?></span>
                        <span>•</span>
                        <span><?= e($c['level']) ?></span>
                    </div>
                    <div class="help">Certificate code: <strong><?= e($c['certificate_code']) ?></strong></div>
                    <div class="row gap" style="margin-top:12px; flex-wrap:wrap">
                        <a class="btn success" href="<?= e(APP_BASE_URL) ?>/student/certificate.php?enrollment_id=<?= (int)$c['enrollment_id'] ?>">Download</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php render_footer(); ?>

