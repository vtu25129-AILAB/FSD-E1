<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['_csrf'];
}

function csrf_field(): string
{
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf" value="' . $t . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['_csrf'] ?? '';
    $ok = is_string($sent) && hash_equals((string)($_SESSION['_csrf'] ?? ''), $sent);
    if (!$ok) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
}

