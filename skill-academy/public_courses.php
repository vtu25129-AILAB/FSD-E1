<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db.php';

$stmt = db()->query("SELECT c.id, c.title, c.slug, c.description, c.category, c.level, c.is_paid, c.price_inr, c.youtube_playlist_url, c.thumbnail_url, u.full_name AS teacher_name
                     FROM courses c
                     JOIN users u ON u.id = c.teacher_id
                     WHERE c.is_published = 1
                     ORDER BY c.is_paid ASC, c.created_at DESC");
$courses = $stmt->fetchAll();

render_header('Courses');
?>

<div class="grid">
    <div class="col-12">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Courses (15 Free + 15 Paid)</h1>
            <p class="muted">Teachers publish YouTube playlist-based courses. Students enroll after login to track progress and earn certificates.</p>
        </div>
    </div>

    <?php foreach ($courses as $c): ?>
        <div class="col-4" id="<?= e($c['slug']) ?>">
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
                    <?= e(mb_strimwidth($c['description'], 0, 140, '...')) ?>
                </p>
                <div class="row gap" style="margin-top:14px; flex-wrap:wrap">
                    <a class="btn" target="_blank" rel="noopener" href="<?= e($c['youtube_playlist_url']) ?>">Watch playlist</a>
                    <?php if (is_logged_in()): ?>
                        <a class="btn primary" href="<?= e(APP_BASE_URL) ?>/student/course.php?course_id=<?= (int)$c['id'] ?>">Open</a>
                    <?php else: ?>
                        <a class="btn primary" href="<?= e(APP_BASE_URL) ?>/login.php">Login to enroll</a>
                    <?php endif; ?>
                </div>
                <div class="help">Teacher: <?= e($c['teacher_name']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php render_footer(); ?>

