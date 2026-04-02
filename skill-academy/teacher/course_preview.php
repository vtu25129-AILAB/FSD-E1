<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/course.php';

require_role('teacher');
$courseId = (int)($_GET['course_id'] ?? 0);
$course = $courseId > 0 ? get_course_by_id($courseId) : null;
if (!$course) {
    http_response_code(404);
    exit('Course not found.');
}

$playlistUrl = (string)$course['youtube_playlist_url'];
$listId = null;
parse_str((string)parse_url($playlistUrl, PHP_URL_QUERY), $qs);
if (!empty($qs['list']) && is_string($qs['list'])) {
    $listId = $qs['list'];
}
$embedUrl = $listId ? 'https://www.youtube.com/embed/videoseries?list=' . rawurlencode($listId) : $playlistUrl;

render_header('Course Preview');
?>

<div class="grid">
    <div class="col-8">
        <div class="card pad">
            <div class="badge"><?= e($course['category']) ?> • <?= e($course['level']) ?> • <?= $course['is_paid'] ? ('Paid ₹' . (int)$course['price_inr']) : 'Free' ?></div>
            <h1 class="h1" style="font-size:34px"><?= e($course['title']) ?></h1>
            <p class="muted"><?= e((string)$course['description']) ?></p>
            <div class="help">Teacher: <?= e((string)$course['teacher_name']) ?></div>

            <div style="margin-top:14px">
                <div class="card" style="overflow:hidden; border-radius:16px">
                    <iframe width="100%" height="360" src="<?= e($embedUrl) ?>" title="YouTube playlist"
                            frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
                <div class="row gap" style="margin-top:12px; flex-wrap:wrap">
                    <a class="btn" target="_blank" rel="noopener" href="<?= e($playlistUrl) ?>">Open on YouTube</a>
                    <a class="btn" href="<?= e(APP_BASE_URL) ?>/teacher/enrollments.php?course_id=<?= (int)$courseId ?>">View enrollments</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card pad">
            <h2 class="h2">Actions</h2>
            <div class="row gap" style="flex-wrap:wrap">
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/teacher/courses.php">Back</a>
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/public_courses.php#<?= e($course['slug']) ?>">Public view</a>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>

