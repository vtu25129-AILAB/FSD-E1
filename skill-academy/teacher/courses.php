<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';

require_role('teacher');
$user = current_user();
$teacherId = (int)$user['id'];

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/i', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? $s : 'course';
}

function unique_slug(PDO $pdo, string $base): string
{
    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

$errors = [];
$ok = flash_get('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_course') {
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $level = (string)($_POST['level'] ?? 'Beginner');
        $isPaid = (int)($_POST['is_paid'] ?? 0) === 1;
        $price = (int)($_POST['price_inr'] ?? 0);
        $playlist = trim((string)($_POST['youtube_playlist_url'] ?? ''));
        $thumb = trim((string)($_POST['thumbnail_url'] ?? ''));

        if ($title === '' || mb_strlen($title) < 5) $errors[] = 'Title must be at least 5 characters.';
        if ($description === '' || mb_strlen($description) < 20) $errors[] = 'Description must be at least 20 characters.';
        if ($category === '') $errors[] = 'Category is required.';
        if (!in_array($level, ['Beginner','Intermediate','Advanced'], true)) $errors[] = 'Invalid level.';
        if ($isPaid && $price <= 0) $errors[] = 'Enter price for paid courses.';
        if (!filter_var($playlist, FILTER_VALIDATE_URL)) $errors[] = 'Enter a valid YouTube playlist URL.';
        if ($thumb !== '' && !filter_var($thumb, FILTER_VALIDATE_URL)) $errors[] = 'Thumbnail URL must be a valid URL.';

        if (!$errors) {
            $pdo = db();
            $slugBase = slugify($title);
            $slug = unique_slug($pdo, $slugBase);
            $stmt = $pdo->prepare("INSERT INTO courses
                (teacher_id, title, slug, description, category, level, is_paid, price_inr, youtube_playlist_url, thumbnail_url, is_published)
                VALUES (?,?,?,?,?,?,?,?,?,?,1)");
            $stmt->execute([
                $teacherId,
                $title,
                $slug,
                $description,
                $category,
                $level,
                $isPaid ? 1 : 0,
                $isPaid ? $price : 0,
                $playlist,
                $thumb !== '' ? $thumb : null,
            ]);
            flash_set('success', 'Course added successfully.');
            header('Location: ' . APP_BASE_URL . '/teacher/courses.php');
            exit;
        }
    }
}

$stmt = db()->prepare("SELECT c.*,
                              COUNT(e.id) AS enrolled_count,
                              SUM(CASE WHEN e.completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_count
                       FROM courses c
                       LEFT JOIN enrollments e ON e.course_id = c.id
                       WHERE c.teacher_id = ?
                       GROUP BY c.id
                       ORDER BY c.created_at DESC");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll();

render_header('Teacher Courses');
?>

<div class="grid">
    <div class="col-6">
        <div class="card pad">
            <h1 class="h1" style="font-size:34px">Add new course</h1>
            <p class="muted">Add BTech courses with a YouTube playlist.</p>

            <?php if ($ok): ?>
                <div class="success-note" style="margin:12px 0"><?= e($ok) ?></div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="error" style="margin:12px 0">
                    <ul style="margin:0; padding-left:18px">
                        <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="no-print">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_course">

                <div class="field">
                    <label for="title">Course title</label>
                    <input id="title" name="title" placeholder="e.g., DBMS Fundamentals" required>
                </div>

                <div class="field">
                    <label for="category">Category</label>
                    <input id="category" name="category" placeholder="e.g., BTech Core / Web / Placement" required>
                </div>

                <div class="field">
                    <label for="level">Level</label>
                    <select id="level" name="level" required>
                        <option>Beginner</option>
                        <option>Intermediate</option>
                        <option>Advanced</option>
                    </select>
                </div>

                <div class="field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="What will students learn?" required></textarea>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label for="is_paid">Type</label>
                        <select id="is_paid" name="is_paid">
                            <option value="0">Free</option>
                            <option value="1">Paid</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="price_inr">Price (INR)</label>
                        <input id="price_inr" name="price_inr" type="number" min="0" value="0">
                    </div>
                </div>

                <div class="field">
                    <label for="youtube_playlist_url">YouTube playlist URL</label>
                    <input id="youtube_playlist_url" name="youtube_playlist_url" placeholder="https://www.youtube.com/playlist?list=..." required>
                </div>

                <div class="field">
                    <label for="thumbnail_url">Thumbnail URL (optional)</label>
                    <input id="thumbnail_url" name="thumbnail_url" placeholder="https://.../image.jpg">
                </div>

                <button class="btn primary block" type="submit">Add course</button>
            </form>
        </div>
    </div>

    <div class="col-6">
        <div class="card pad">
            <h2 class="h2">Your courses</h2>
            <?php if (!$courses): ?>
                <p class="muted">No courses yet.</p>
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
                    <?php foreach ($courses as $c): ?>
                        <tr>
                            <td>
                                <strong><?= e($c['title']) ?></strong>
                                <div class="muted" style="font-size:13px"><?= e($c['category']) ?> • <?= e($c['level']) ?></div>
                                <div class="help" style="margin-top:6px">
                                    <a class="btn" href="<?= e(APP_BASE_URL) ?>/teacher/course_preview.php?course_id=<?= (int)$c['id'] ?>">Preview</a>
                                </div>
                            </td>
                            <td><?= $c['is_paid'] ? ('Paid ₹' . (int)$c['price_inr']) : 'Free' ?></td>
                            <td><?= (int)$c['enrolled_count'] ?></td>
                            <td><?= (int)$c['completed_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php render_footer(); ?>

