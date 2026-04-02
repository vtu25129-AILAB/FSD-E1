<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_role('teacher');
header('Location: ' . APP_BASE_URL . '/teacher/dashboard.php');
exit;

