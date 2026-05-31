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
    respondScheduleEnrollment(false, 'Apenas membros podem cancelar inscrições.', null, 403);
}

verifyCsrf();

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$userId = (int) $_SESSION['user_id'];

if ($classId <= 0) {
    respondScheduleEnrollment(false, 'Aula inválida.', null, 422);
}

$enrollment = dbQuery(
    'SELECT id FROM enrollments WHERE user_id = :uid AND class_id = :cid',
    ['uid' => $userId, 'cid' => $classId]
)->fetch();

if (!$enrollment) {
    $state = classEnrollmentState($classId, $userId);
    respondScheduleEnrollment(false, 'Não tem inscrição nesta aula.', $state, 422);
}

dbQuery(
    'DELETE FROM enrollments WHERE user_id = :uid AND class_id = :cid',
    ['uid' => $userId, 'cid' => $classId]
);

$state = classEnrollmentState($classId, $userId);
respondScheduleEnrollment(true, 'Inscrição cancelada com sucesso.', $state);
