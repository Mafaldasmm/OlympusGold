<?php

declare(strict_types=1);

require_once __DIR__ . '/class_waitlist.php';

/**
 * Helpers for schedule enrollment actions (book / cancel / waitlist).
 */

/**
 * @return array<string, mixed>|null
 */
function classEnrollmentState(int $classId, int $userId): ?array
{
    $class = dbQuery(
        'SELECT id, capacity FROM classes WHERE id = :id',
        ['id' => $classId]
    )->fetch();

    if (!$class) {
        return null;
    }

    $enrolledCount = (int) dbQuery(
        'SELECT COUNT(*) FROM enrollments WHERE class_id = :cid',
        ['cid' => $classId]
    )->fetchColumn();

    $isEnrolled = (bool) dbQuery(
        'SELECT 1 FROM enrollments WHERE user_id = :uid AND class_id = :cid LIMIT 1',
        ['uid' => $userId, 'cid' => $classId]
    )->fetch();

    $isOnWaitlist = isMemberOnClassWaitlist($classId, $userId);
    $waitlistPosition = $isOnWaitlist ? memberWaitlistPosition($classId, $userId) : null;

    $capacity = (int) $class['capacity'];
    $spots = max(0, $capacity - $enrolledCount);

    return [
        'classId' => $classId,
        'capacity' => $capacity,
        'enrolledCount' => $enrolledCount,
        'spots' => $spots,
        'isEnrolled' => $isEnrolled,
        'isFull' => $spots <= 0,
        'isOnWaitlist' => $isOnWaitlist,
        'waitlistPosition' => $waitlistPosition,
        'waitlistCount' => classWaitlistCount($classId),
    ];
}

/**
 * @param array<string, mixed>|null $state
 */
function respondScheduleEnrollment(
    bool $success,
    string $message,
    ?array $state = null,
    int $status = 200,
    ?string $redirectPage = null
): never {
    if (requestWantsJson()) {
        if ($status !== 200) {
            http_response_code($status);
        }

        header('Content-Type: application/json; charset=UTF-8');

        $payload = [
            'success' => $success,
            'message' => $message,
            'csrfToken' => csrfToken(),
        ];

        if ($state !== null) {
            $payload = array_merge($payload, $state);
        }

        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }

    if ($success) {
        $_SESSION['flash_success'] = $message;
    } else {
        $_SESSION['flash_error'] = $message;
    }

    $target = $redirectPage ?? 'schedule.php';
    if (!str_contains($target, '.php')) {
        $target = 'schedule.php';
    }

    redirect(pageUrl($target));
}

/**
 * Validates redirect target is a safe relative page under the site root.
 */
function scheduleEnrollmentRedirectTarget(): string
{
    $redirect = trim((string) ($_POST['redirect'] ?? ''));

    if ($redirect === '') {
        return 'schedule.php';
    }

    if (!preg_match('/^[a-z_]+\.php(\?id=\d+)?$/', $redirect)) {
        return 'schedule.php';
    }

    return $redirect;
}
