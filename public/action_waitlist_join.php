<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/schedule_enrollment.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(pageUrl('schedule.php'));
}

$user = requireLogin();
$errors = [];

verifyCsrf();

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$userId = (int) $_SESSION['user_id'];
$redirect = scheduleEnrollmentRedirectTarget();

if ($classId <= 0) {
    respondScheduleEnrollment(false, 'Aula inválida.', null, 422, $redirect);
}

if (!userCanJoinClassWaitlist($userId, $errors)) {
    respondScheduleEnrollment(false, $errors[0] ?? 'Apenas membros podem entrar na lista de espera.', null, 403, $redirect);
}

try {
    $class = dbQuery('SELECT id, capacity FROM classes WHERE id = :id', ['id' => $classId])->fetch();

    if (!$class) {
        respondScheduleEnrollment(false, 'Aula não encontrada.', null, 422, $redirect);
    }

    if ((bool) dbQuery(
        'SELECT 1 FROM enrollments WHERE user_id = :uid AND class_id = :cid LIMIT 1',
        ['uid' => $userId, 'cid' => $classId]
    )->fetch()) {
        $state = classEnrollmentState($classId, $userId);
        respondScheduleEnrollment(false, 'Já está inscrito nesta aula.', $state, 422, $redirect);
    }

    if (isMemberOnClassWaitlist($classId, $userId)) {
        $state = classEnrollmentState($classId, $userId);
        respondScheduleEnrollment(false, 'Já está na lista de espera desta aula.', $state, 422, $redirect);
    }

    $enrolled = (int) dbQuery(
        'SELECT COUNT(*) FROM enrollments WHERE class_id = :cid',
        ['cid' => $classId]
    )->fetchColumn();

    if ($enrolled < (int) $class['capacity']) {
        $state = classEnrollmentState($classId, $userId);
        respondScheduleEnrollment(false, 'Esta aula ainda tem vagas. Pode inscrever-se diretamente.', $state, 422, $redirect);
    }

    dbQuery(
        'INSERT INTO class_waitlist (user_id, class_id) VALUES (:uid, :cid)',
        ['uid' => $userId, 'cid' => $classId]
    );

    $state = classEnrollmentState($classId, $userId);
    $position = $state['waitlistPosition'] ?? 0;
    respondScheduleEnrollment(
        true,
        'Entrou na lista de espera na posição ' . $position . '.',
        $state,
        200,
        $redirect
    );
} catch (Throwable) {
    respondScheduleEnrollment(
        false,
        'Não foi possível entrar na lista de espera. Tenta novamente dentro de alguns instantes.',
        classEnrollmentState($classId, $userId),
        500,
        $redirect
    );
}
