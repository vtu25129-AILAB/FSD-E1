<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/course.php';

require_role('student');
$user = current_user();
$studentId = (int)$user['id'];

$courseId = (int)($_GET['course_id'] ?? 0);
$course = $courseId > 0 ? get_course_by_id($courseId) : null;
if (!$course) {
    http_response_code(404);
    exit('Course not found.');
}

$enrollment = get_enrollment($studentId, $courseId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'enroll') {
        $enrollment = enroll_student($studentId, $courseId);
        header('Location: ' . APP_BASE_URL . '/student/course.php?course_id=' . $courseId);
        exit;
    }

    if (!$enrollment) {
        http_response_code(400);
        exit('Enroll first.');
    }

    if ($action === 'complete_module') {
        $module = (int)($_POST['module'] ?? 0);
        progress_mark_module_complete((int)$enrollment['id'], $module);
        header('Location: ' . APP_BASE_URL . '/student/course.php?course_id=' . $courseId);
        exit;
    }
}

// Refetch enrollment for latest progress.
if ($enrollment) {
    $enrollment = get_enrollment($studentId, $courseId);
}

$playlistUrl = (string)$course['youtube_playlist_url'];

// Build a robust embeddable YouTube URL that works for:
// - Playlist links: https://www.youtube.com/playlist?list=...
// - Watch links:   https://www.youtube.com/watch?v=...
// - Short links:   https://youtu.be/VIDEO_ID
$embedUrl = $playlistUrl;
$urlParts = parse_url($playlistUrl);
$host = strtolower((string)($urlParts['host'] ?? ''));
$path = (string)($urlParts['path'] ?? '');
$query = [];
if (!empty($urlParts['query'])) {
    parse_str((string)$urlParts['query'], $query);
}

// If a playlist id is present, always use the playlist embed.
if (!empty($query['list']) && is_string($query['list'])) {
    $embedUrl = 'https://www.youtube.com/embed/videoseries?list=' . rawurlencode($query['list']);
} elseif ($host === 'www.youtube.com' || $host === 'youtube.com' || $host === 'm.youtube.com') {
    // Handle standard watch URLs: https://www.youtube.com/watch?v=VIDEO_ID
    if ($path === '/watch' && !empty($query['v']) && is_string($query['v'])) {
        $embedUrl = 'https://www.youtube.com/embed/' . rawurlencode($query['v']);
    } elseif (str_starts_with($path, '/embed/')) {
        // Already an embed URL, keep as‑is.
        $embedUrl = $playlistUrl;
    }
} elseif ($host === 'youtu.be') {
    // Handle short URLs: https://youtu.be/VIDEO_ID
    $videoId = ltrim($path, '/');
    if ($videoId !== '') {
        $embedUrl = 'https://www.youtube.com/embed/' . rawurlencode($videoId);
    }
}

// Add player parameters:
// - rel=0: do not show unrelated videos
// - autoplay=1: start automatically after enrollment
$paramSeparator = str_contains($embedUrl, '?') ? '&' : '?';
$baseParams = 'rel=0';
if ($enrollment) {
    $baseParams .= '&autoplay=1';
}
$embedUrlWithParams = $embedUrl . $paramSeparator . $baseParams;

$completedKeys = [];
if ($enrollment) {
    $stmt = db()->prepare("SELECT item_key FROM course_progress WHERE enrollment_id = ?");
    $stmt->execute([(int)$enrollment['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $completedKeys[(string)$row['item_key']] = true;
    }
}

$cert = null;
if ($enrollment && !empty($enrollment['completed_at'])) {
    $cert = get_certificate_for_enrollment((int)$enrollment['id']);
}

render_header('Course');
?>

<div class="grid">
    <div class="col-8">
        <div class="card pad">
            <div class="badge"><?= e($course['category']) ?> • <?= e($course['level']) ?> • <?= $course['is_paid'] ? ('Paid ₹' . (int)$course['price_inr']) : 'Free' ?></div>
            <h1 class="h1" style="font-size:34px"><?= e($course['title']) ?></h1>
            <p class="muted"><?= e((string)$course['description']) ?></p>
            <div class="help">Teacher: <?= e((string)$course['teacher_name']) ?></div>

            <div style="margin-top:14px">
                <?php if ($enrollment): ?>
                    <div class="card" style="overflow:hidden; border-radius:16px">
                        <iframe width="100%" height="360" src="<?= e($embedUrlWithParams) ?>" title="Course video"
                                frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <div class="card pad" style="border-radius:16px; text-align:center">
                        <p class="muted" style="margin:0 0 10px">Enroll in this course to start watching the full video inside Skill Academy.</p>
                        <div class="row gap" style="justify-content:center; flex-wrap:wrap">
                            <a class="btn" target="_blank" rel="noopener" href="<?= e($playlistUrl) ?>">Preview on YouTube</a>
                            <form method="post" class="no-print" style="margin:0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="enroll">
                                <button class="btn primary" type="submit">Enroll &amp; start course</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($enrollment): ?>
                    <div class="row gap" style="margin-top:12px; flex-wrap:wrap">
                        <span class="pill">Enrolled</span>
                        <span class="pill">Progress <?= (int)$enrollment['progress_percent'] ?>%</span>
                        <?php if (!empty($enrollment['completed_at']) && $cert): ?>
                            <a class="btn success" href="<?= e(APP_BASE_URL) ?>/student/certificate.php?enrollment_id=<?= (int)$enrollment['id'] ?>">Download certificate</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($enrollment): ?>
            <div class="card pad" style="margin-top:16px">
                <h2 class="h2">Modules (mark complete)</h2>
                <p class="muted">This course uses a 10-module checklist. Complete all modules to generate a certificate.</p>
                <div class="grid">
                    <?php for ($i = 1; $i <= COURSE_MODULES_TOTAL; $i++): ?>
                        <?php $key = 'module-' . $i; $done = !empty($completedKeys[$key]); ?>
                        <div class="col-6">
                            <div class="card pad" style="background: rgba(16,26,46,.35)">
                                <div class="row between center gap">
                                    <div>
                                        <strong>Module <?= $i ?></strong>
                                        <div class="muted" style="font-size:13px">Watch and understand playlist part <?= $i ?></div>
                                    </div>
                                    <?php if ($done): ?>
                                        <span class="pill" style="border-color:rgba(34,197,94,.55)">Completed</span>
                                    <?php else: ?>
                                        <form method="post" class="no-print" style="margin:0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="complete_module">
                                            <input type="hidden" name="module" value="<?= $i ?>">
                                            <button class="btn success" type="submit">Mark complete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-4">
        <div class="card pad">
            <h2 class="h2">Actions</h2>
            <div class="row gap" style="flex-wrap:wrap">
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/student/courses.php">All courses</a>
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/student/doubts.php?course_id=<?= (int)$courseId ?>">Ask doubt</a>
            </div>
            <?php if ($enrollment): ?>
                <div style="margin-top:14px">
                    <div class="badge">Progress</div>
                    <div class="card pad" style="margin-top:10px">
                        <div><strong><?= (int)$enrollment['progress_percent'] ?>%</strong> completed</div>
                        <div class="help">
                            Enrolled: <?= e((string)$enrollment['enrolled_at']) ?><br>
                            <?php if (!empty($enrollment['completed_at'])): ?>
                                Completed: <?= e((string)$enrollment['completed_at']) ?>
                            <?php else: ?>
                                Complete all modules to unlock certificate.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php render_footer(); ?>

