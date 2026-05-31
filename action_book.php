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
    respondScheduleEnrollment(false, 'Apenas membros podem inscrever-se em aulas.', null, 403, scheduleEnrollmentRedirectTarget());
}

verifyCsrf();

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$userId = (int) $_SESSION['user_id'];
$redirect = scheduleEnrollmentRedirectTarget();

if ($classId <= 0) {
    respondScheduleEnrollment(false, 'Aula inválida.', null, 422, $redirect);
}

$class = dbQuery('SELECT id, capacity FROM classes WHERE id = :id', ['id' => $classId])->fetch();

if (!$class) {
    respondScheduleEnrollment(false, 'Aula não encontrada.', null, 422, $redirect);
}

$alreadyEnrolled = dbQuery(
    'SELECT id FROM enrollments WHERE user_id = :uid AND class_id = :cid',
    ['uid' => $userId, 'cid' => $classId]
)->fetch();

if ($alreadyEnrolled) {
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

if ($enrolled >= (int) $class['capacity']) {
    $state = classEnrollmentState($classId, $userId);
    respondScheduleEnrollment(false, 'Esta aula já não tem vagas disponíveis.', $state, 422, $redirect);
}

dbQuery(
    'INSERT INTO enrollments (user_id, class_id) VALUES (:uid, :cid)',
    ['uid' => $userId, 'cid' => $classId]
);

$state = classEnrollmentState($classId, $userId);
respondScheduleEnrollment(true, 'Inscrição realizada com sucesso!', $state, 200, $redirect);
