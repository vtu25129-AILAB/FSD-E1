<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function render_header(string $title): void
{
    $user = is_logged_in() ? current_user() : null;
    $role = $user['role'] ?? null;
    $fullName = $user ? $user['full_name'] : null;
    $activePath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

    $nav = [];
    if (!$user) {
        $nav = [
            ['label' => 'Home', 'href' => APP_BASE_URL . '/index.php'],
            ['label' => 'Courses', 'href' => APP_BASE_URL . '/public_courses.php'],
            ['label' => 'Contact', 'href' => APP_BASE_URL . '/contact.php'],
            ['label' => 'Login', 'href' => APP_BASE_URL . '/login.php'],
            ['label' => 'Register', 'href' => APP_BASE_URL . '/register.php'],
        ];
    } elseif ($role === 'student') {
        $nav = [
            ['label' => 'Home', 'href' => APP_BASE_URL . '/student/home.php'],
            ['label' => 'Courses', 'href' => APP_BASE_URL . '/student/courses.php'],
            ['label' => 'Doubts', 'href' => APP_BASE_URL . '/student/doubts.php'],
            ['label' => 'Certificates', 'href' => APP_BASE_URL . '/student/certificates.php'],
            ['label' => 'Profile', 'href' => APP_BASE_URL . '/student/profile.php'],
            ['label' => 'Logout', 'href' => APP_BASE_URL . '/logout.php'],
        ];
    } else {
        $nav = [
            ['label' => 'Dashboard', 'href' => APP_BASE_URL . '/teacher/dashboard.php'],
            ['label' => 'Courses', 'href' => APP_BASE_URL . '/teacher/courses.php'],
            ['label' => 'Enrollments', 'href' => APP_BASE_URL . '/teacher/enrollments.php'],
            ['label' => 'Doubts', 'href' => APP_BASE_URL . '/teacher/doubts.php'],
            ['label' => 'Profile', 'href' => APP_BASE_URL . '/teacher/profile.php'],
            ['label' => 'Logout', 'href' => APP_BASE_URL . '/logout.php'],
        ];
    }

    $pageTitle = APP_NAME . ' — ' . $title;
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($pageTitle) ?></title>
        <link rel="stylesheet" href="<?= e(APP_BASE_URL) ?>/assets/app.css">
    </head>
    <body>
    <header class="site-header">
        <div class="container row between center gap">
            <a class="brand" href="<?= e(APP_BASE_URL) ?>/index.php"><?= e(APP_NAME) ?></a>
            <nav class="nav">
                <?php foreach ($nav as $item): ?>
                    <?php $isActive = ($activePath === parse_url($item['href'], PHP_URL_PATH)); ?>
                    <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="user-chip">
                <?php if ($fullName): ?>
                    <span><?= e($fullName) ?></span>
                    <span class="pill"><?= e((string)$role) ?></span>
                <?php else: ?>
                    <span class="muted">Guest</span>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container">
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    <footer class="site-footer">
        <div class="container row between center">
            <div class="muted">© <?= date('Y') ?> <?= e(APP_NAME) ?></div>
        </div>
    </footer>
    </body>
    </html>
    <?php
}

