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
$user = requireLogin();
$userId = (int) $user['id'];

// Validar CSRF
verifyCsrf();

$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$rating  = isset($_POST['rating'])   ? (int) $_POST['rating']   : 0;
$comment = trim($_POST['comment'] ?? '');

// Validações básicas
if ($classId <= 0) {
    $_SESSION['flash_error'] = 'Aula inválida.';
    header('Location: ' . pageUrl('schedule.php'));
    exit;
}

if ($rating < 1 || $rating > 5) {
    $_SESSION['flash_error'] = 'A classificação tem de ser entre 1 e 5 estrelas.';
    header('Location: ' . pageUrl('class.php?id=' . $classId));
    exit;
}

if (mb_strlen($comment) > 300) {
    $_SESSION['flash_error'] = 'O comentário não pode ter mais de 300 caracteres.';
    header('Location: ' . pageUrl('class.php?id=' . $classId));
    exit;
}

// Verificar se a aula existe
$class = dbQuery(
    'SELECT id FROM classes WHERE id = :id',
    ['id' => $classId]
)->fetch();

if (!$class) {
    $_SESSION['flash_error'] = 'Aula não encontrada.';
    header('Location: ' . pageUrl('schedule.php'));
    exit;
}

// Verificar se o utilizador está inscrito na aula
$isEnrolled = dbQuery(
    'SELECT id FROM enrollments WHERE user_id = :uid AND class_id = :cid',
    ['uid' => $userId, 'cid' => $classId]
)->fetch();

if (!$isEnrolled) {
    $_SESSION['flash_error'] = 'Só podes avaliar aulas em que estejas inscrito.';
    header('Location: ' . pageUrl('class.php?id=' . $classId));
    exit;
}

// Verificar review duplicada
$alreadyReviewed = dbQuery(
    'SELECT id FROM reviews WHERE user_id = :uid AND class_id = :cid',
    ['uid' => $userId, 'cid' => $classId]
)->fetch();

if ($alreadyReviewed) {
    $_SESSION['flash_error'] = 'Já avaliaste esta aula.';
    header('Location: ' . pageUrl('class.php?id=' . $classId));
    exit;
}

// Inserir a review
dbQuery(
    'INSERT INTO reviews (user_id, class_id, rating, comment) VALUES (:uid, :cid, :rating, :comment)',
    [
        'uid'     => $userId,
        'cid'     => $classId,
        'rating'  => $rating,
        'comment' => $comment === '' ? null : $comment,
    ]
);

$_SESSION['flash_success'] = 'Avaliação submetida com sucesso!';
header('Location: ' . pageUrl('class.php?id=' . $classId));
exit;
