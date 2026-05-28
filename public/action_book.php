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

$pdo = getDatabase();

// Verificar se a aula existe
$class = dbQuery('SELECT id, capacity FROM classes WHERE id = :id', ['id' => $classId])->fetch();

if (!$class) {
    $_SESSION['flash_error'] = 'Aula não encontrada.';
    header('Location: ' . pageUrl('schedule.php'));
    exit;
}

// Verificar inscrição duplicada
$alreadyEnrolled = dbQuery(
    'SELECT id FROM enrollments WHERE user_id = :uid AND class_id = :cid',
    ['uid' => $userId, 'cid' => $classId]
)->fetch();

if ($alreadyEnrolled) {
    $_SESSION['flash_error'] = 'Já está inscrito nesta aula.';
    header('Location: ' . pageUrl('schedule.php'));
    exit;
}

// Verificar capacidade disponível
$enrolled = (int)dbQuery(
    'SELECT COUNT(*) FROM enrollments WHERE class_id = :cid',
    ['cid' => $classId]
)->fetchColumn();

if ($enrolled >= $class['capacity']) {
    $_SESSION['flash_error'] = 'Esta aula já não tem vagas disponíveis.';
    header('Location: ' . pageUrl('schedule.php'));
    exit;
}

// Inscrever o utilizador
dbQuery(
    'INSERT INTO enrollments (user_id, class_id) VALUES (:uid, :cid)',
    ['uid' => $userId, 'cid' => $classId]
);

$_SESSION['flash_success'] = 'Inscrição realizada com sucesso!';
header('Location: ' . pageUrl('schedule.php'));
exit;
