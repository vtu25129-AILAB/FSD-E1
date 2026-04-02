<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/db.php';

$stmt = db()->query("SELECT id, title, slug, category, level, is_paid, price_inr, thumbnail_url FROM courses WHERE is_published = 1 ORDER BY created_at DESC LIMIT 6");
$courses = $stmt->fetchAll();

render_header('Home');
?>

<section class="grid">
    <div class="col-8">
        <div class="card pad">
            <div class="badge">Marketplace • BTech Courses • Certificates</div>
            <h1 class="h1">Learn skills. Track progress. Get certified.</h1>
            <p class="muted">Skill Academy is a student/teacher learning marketplace. Teachers publish courses with YouTube playlists. Students enroll, complete modules, and download certificates.</p>
            <div class="row gap no-print" style="margin-top:14px; flex-wrap:wrap;">
                <a class="btn primary" href="<?= e(APP_BASE_URL) ?>/register.php">Create account</a>
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/public_courses.php">Browse courses</a>
                <a class="btn" href="<?= e(APP_BASE_URL) ?>/login.php">Login</a>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card pad">
            <h2 class="h2">Quick stats</h2>
            <?php
            $stats = [
                'Students' => (int)db()->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch()['c'],
                'Teachers' => (int)db()->query("SELECT COUNT(*) c FROM users WHERE role='teacher'")->fetch()['c'],
                'Courses' => (int)db()->query("SELECT COUNT(*) c FROM courses WHERE is_published=1")->fetch()['c'],
                'Enrollments' => (int)db()->query("SELECT COUNT(*) c FROM enrollments")->fetch()['c'],
            ];
            ?>
            <table class="table">
                <tbody>
                <?php foreach ($stats as $k => $v): ?>
                    <tr>
                        <td style="width:60%"><?= e($k) ?></td>
                        <td><strong><?= e((string)$v) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="help">Tip: Use the seeded teacher login `teacher@skillacademy.local` / `Teacher@123` after importing the database.</p>
        </div>
    </div>
</section>

<section style="margin-top:18px">
    <div class="row between center">
        <h2 class="h2" style="margin:0">Latest courses</h2>
        <a class="btn" href="<?= e(APP_BASE_URL) ?>/public_courses.php">View all</a>
    </div>
    <div class="grid" style="margin-top:14px">
        <?php foreach ($courses as $c): ?>
            <div class="col-4">
                <a class="card pad" href="<?= e(APP_BASE_URL) ?>/public_courses.php#<?= e($c['slug']) ?>">
                    <img class="course-thumb" alt="" src="<?= e($c['thumbnail_url'] ?: 'https://via.placeholder.com/1280x720?text=Course') ?>">
                    <div class="course-title"><?= e($c['title']) ?></div>
                    <div class="course-meta">
                        <span><?= e($c['category']) ?></span>
                        <span>•</span>
                        <span><?= e($c['level']) ?></span>
                        <span>•</span>
                        <span><?= $c['is_paid'] ? ('Paid ₹' . (int)$c['price_inr']) : 'Free' ?></span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php render_footer(); ?>

