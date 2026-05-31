<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/reviews.php';

function respondReview(bool $success, string $message, int $classId, int $status = 200): never
{
    if (requestWantsJson()) {
        if ($status !== 200) {
            http_response_code($status);
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success'   => $success,
            'message'   => $message,
            'classId'   => $classId,
            'csrfToken' => csrfToken(),
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    if ($success) {
        $_SESSION['flash_success'] = $message;
    } else {
        $_SESSION['flash_error'] = $message;
    }

    redirect(pageUrl('class.php?id=' . $classId));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(pageUrl('schedule.php'));
}

$user   = requireLogin();
$userId = (int) $user['id'];
$classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

if ($user['role'] !== 'member') {
    respondReview(false, 'Apenas membros podem deixar avaliações.', max(0, $classId), 403);
}

if (!userCanLeaveReviews($userId)) {
    respondReview(false, 'Não tens permissão para deixar avaliações.', max(0, $classId), 403);
}

verifyCsrf();

$rating  = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$comment = trim($_POST['comment'] ?? '');

if ($classId <= 0) {
    respondReview(false, 'Aula inválida.', 0, 400);
}

if ($rating < 1 || $rating > 5) {
    respondReview(false, 'A classificação tem de ser entre 1 e 5 estrelas.', $classId, 400);
}

if (strlen($comment) > 300) {
    respondReview(false, 'O comentário não pode ter mais de 300 caracteres.', $classId, 400);
}

$class = dbQuery('SELECT id FROM classes WHERE id = :id', ['id' => $classId])->fetch();

if (!$class) {
    respondReview(false, 'Aula não encontrada.', $classId, 404);
}

if (!userIsEnrolledInClass($userId, $classId)) {
    respondReview(false, 'Só podes avaliar aulas em que estejas inscrito.', $classId, 400);
}

if (userHasClassReview($userId, $classId)) {
    respondReview(false, 'Já avaliaste esta aula.', $classId, 400);
}

dbQuery(
    'INSERT INTO reviews (user_id, class_id, rating, comment) VALUES (:uid, :cid, :rating, :comment)',
    [
        'uid'     => $userId,
        'cid'     => $classId,
        'rating'  => $rating,
        'comment' => $comment === '' ? null : $comment,
    ]
);

respondReview(true, 'Avaliação submetida com sucesso!', $classId);
