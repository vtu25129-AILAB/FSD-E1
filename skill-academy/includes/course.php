<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const COURSE_MODULES_TOTAL = 10;

function get_course_by_id(int $courseId): ?array
{
    $stmt = db()->prepare("SELECT c.*, u.full_name AS teacher_name
                           FROM courses c
                           JOIN users u ON u.id = c.teacher_id
                           WHERE c.id = ? AND c.is_published = 1");
    $stmt->execute([$courseId]);
    $c = $stmt->fetch();
    return $c ?: null;
}

function get_enrollment(int $studentId, int $courseId): ?array
{
    $stmt = db()->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$studentId, $courseId]);
    $e = $stmt->fetch();
    return $e ?: null;
}

function enroll_student(int $studentId, int $courseId): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $stmt->execute([$studentId, $courseId]);
        $enrollId = (int)$pdo->lastInsertId();
        $pdo->commit();
        $stmt2 = $pdo->prepare("SELECT * FROM enrollments WHERE id = ?");
        $stmt2->execute([$enrollId]);
        return (array)$stmt2->fetch();
    } catch (PDOException $e) {
        $pdo->rollBack();
        // If already enrolled, return existing.
        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
            $existing = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
            $existing->execute([$studentId, $courseId]);
            $row = $existing->fetch();
            return $row ? (array)$row : [];
        }
        throw $e;
    }
}

function progress_completed_count(int $enrollmentId): int
{
    $stmt = db()->prepare("SELECT COUNT(*) c FROM course_progress WHERE enrollment_id = ?");
    $stmt->execute([$enrollmentId]);
    return (int)$stmt->fetch()['c'];
}

function progress_recalculate(int $enrollmentId): void
{
    $completed = progress_completed_count($enrollmentId);
    $percent = (int)floor(min(100, ($completed / COURSE_MODULES_TOTAL) * 100));
    $completedAt = ($percent >= 100) ? 'NOW()' : 'NULL';

    $sql = "UPDATE enrollments
            SET progress_percent = ?, completed_at = {$completedAt}
            WHERE id = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute([$percent, $enrollmentId]);

    if ($percent >= 100) {
        ensure_certificate($enrollmentId);
    }
}

function progress_mark_module_complete(int $enrollmentId, int $moduleNumber): void
{
    $moduleNumber = max(1, min(COURSE_MODULES_TOTAL, $moduleNumber));
    $key = 'module-' . $moduleNumber;
    db()->prepare("INSERT IGNORE INTO course_progress (enrollment_id, item_key) VALUES (?, ?)")
        ->execute([$enrollmentId, $key]);
    progress_recalculate($enrollmentId);
}

function ensure_certificate(int $enrollmentId): void
{
    $pdo = db();
    $exists = $pdo->prepare("SELECT id FROM certificates WHERE enrollment_id = ?");
    $exists->execute([$enrollmentId]);
    if ($exists->fetch()) {
        return;
    }
    $code = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
    $pdo->prepare("INSERT INTO certificates (enrollment_id, certificate_code) VALUES (?, ?)")
        ->execute([$enrollmentId, $code]);
}

function get_certificate_for_enrollment(int $enrollmentId): ?array
{
    $stmt = db()->prepare("SELECT * FROM certificates WHERE enrollment_id = ?");
    $stmt->execute([$enrollmentId]);
    $c = $stmt->fetch();
    return $c ?: null;
}

