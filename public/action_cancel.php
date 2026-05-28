<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . pageUrl('schedule.php'));
    exit;
}

// Utilizador tem de estar autenticado
requireLogin();

// Validar CSRF
verifyCsrf();

$classId = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$userId  = (int)$_SESSION['user_id'];

if ($classId <= 0) {
    $_SESSION['flash_error'] = 'Aula inválida.';
    header('Location: ' . pageUrl('schedule.php'));
    exit;
}

// Verificar se a inscrição existe antes de apagar
$enrollment = dbQuery(
    'SELECT id FROM enrollments WHERE user_id = :uid AND class_id = :cid',
    ['uid' => $userId, 'cid' => $classId]
)->fetch();

if (!$enrollment) {
    $_SESSION['flash_error'] = 'Não tem inscrição nesta aula.';
    header('Location: ' . pageUrl('schedule.php'));
    exit;
}

// Cancelar inscrição
dbQuery(
    'DELETE FROM enrollments WHERE user_id = :uid AND class_id = :cid',
    ['uid' => $userId, 'cid' => $classId]
);

$_SESSION['flash_success'] = 'Inscrição cancelada com sucesso.';
header('Location: ' . pageUrl('schedule.php'));
exit;
