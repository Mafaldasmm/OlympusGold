<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/schedule_enrollment.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(pageUrl('schedule.php'));
}

$user = requireLogin();

if ($user['role'] !== 'member') {
    respondScheduleEnrollment(false, 'Apenas membros podem cancelar inscrições.', null, 403, scheduleEnrollmentRedirectTarget());
}

verifyCsrf();

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$userId = (int) $_SESSION['user_id'];
$redirect = scheduleEnrollmentRedirectTarget();

if ($classId <= 0) {
    respondScheduleEnrollment(false, 'Aula inválida.', null, 422, $redirect);
}

try {
    $pdo = getDatabase();
    $pdo->beginTransaction();

    $enrollment = $pdo->prepare(
        'SELECT id FROM enrollments WHERE user_id = :uid AND class_id = :cid'
    );
    $enrollment->execute(['uid' => $userId, 'cid' => $classId]);
    $row = $enrollment->fetch();

    if (!$row) {
        $pdo->rollBack();
        $state = classEnrollmentState($classId, $userId);
        respondScheduleEnrollment(false, 'Não tem inscrição nesta aula.', $state, 422, $redirect);
    }

    $pdo->prepare('DELETE FROM enrollments WHERE user_id = :uid AND class_id = :cid')
        ->execute(['uid' => $userId, 'cid' => $classId]);

    $promotion = promoteNextFromWaitlist($classId, $pdo);

    $pdo->commit();

    $state = classEnrollmentState($classId, $userId);
    $message = 'Inscrição cancelada com sucesso.';

    if ($promotion['promoted'] && $promotion['memberName'] !== null) {
        $message .= ' O próximo membro na lista de espera (' . $promotion['memberName'] . ') foi inscrito automaticamente.';
    }

    respondScheduleEnrollment(true, $message, $state, 200, $redirect);
} catch (Throwable) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respondScheduleEnrollment(
        false,
        'Não foi possível cancelar a inscrição. Tenta novamente dentro de alguns instantes.',
        classEnrollmentState($classId, $userId),
        500,
        $redirect
    );
}
