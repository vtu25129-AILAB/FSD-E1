<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

function current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !ctype_digit((string)$id)) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, role, full_name, email, created_at, last_login_at FROM users WHERE id = ?');
    $stmt->execute([(int)$id]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . APP_BASE_URL . '/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    $u = current_user();
    if (!$u || $u['role'] !== $role) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function redirect_by_role(array $user): void
{
    if ($user['role'] === 'teacher') {
        header('Location: ' . APP_BASE_URL . '/teacher/dashboard.php');
        exit;
    }
    header('Location: ' . APP_BASE_URL . '/student/home.php');
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($msg) ? $msg : null;
}

