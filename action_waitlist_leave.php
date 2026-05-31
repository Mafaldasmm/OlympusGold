<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/schedule_enrollment.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(pageUrl('schedule.php'));
}

$user = requireLogin();

verifyCsrf();

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$userId = (int) $_SESSION['user_id'];
$redirect = scheduleEnrollmentRedirectTarget();
$errors = [];

if ($classId <= 0) {
    respondScheduleEnrollment(false, 'Aula inválida.', null, 422, $redirect);
}

if (!userCanJoinClassWaitlist($userId, $errors)) {
    respondScheduleEnrollment(false, $errors[0] ?? 'Apenas membros podem sair da lista de espera.', null, 403, $redirect);
}

try {
    $deleted = dbQuery(
        'DELETE FROM class_waitlist WHERE user_id = :uid AND class_id = :cid',
        ['uid' => $userId, 'cid' => $classId]
    );

    if ($deleted->rowCount() === 0) {
        $state = classEnrollmentState($classId, $userId);
        respondScheduleEnrollment(false, 'Não está na lista de espera desta aula.', $state, 422, $redirect);
    }

    $state = classEnrollmentState($classId, $userId);
    respondScheduleEnrollment(true, 'Saiu da lista de espera com sucesso.', $state, 200, $redirect);
} catch (Throwable) {
    respondScheduleEnrollment(
        false,
        'Não foi possível sair da lista de espera. Tenta novamente dentro de alguns instantes.',
        classEnrollmentState($classId, $userId),
        500,
        $redirect
    );
}
