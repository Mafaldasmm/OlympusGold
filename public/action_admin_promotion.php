<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/promotions.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respondAdminPromotion(getDatabase(), false, 'Método não permitido.', [], 405);
}

$pdo = getDatabase();

$user = currentUser();
if ($user === null) {
    respondAdminPromotion($pdo, false, 'Tens de iniciar sessão.', [], 401);
}
if ($user['role'] !== 'admin') {
    respondAdminPromotion($pdo, false, 'Sem permissões para esta ação.', [], 403);
}

verifyCsrf();

$action = $_POST['action'] ?? '';

if ($action === 'toggle_class_featured') {
    $classId  = (int) ($_POST['class_id'] ?? 0);
    $featured = ($_POST['featured'] ?? '') === '1';

    if ($classId <= 0) {
        respondAdminPromotion($pdo, false, 'Aula inválida.', [], 422);
    }

    $result = setClassFeatured($pdo, $classId, $featured);
    if (!$result['ok']) {
        respondAdminPromotion($pdo, false, $result['error'] ?? 'Não foi possível atualizar a aula.', [], 422);
    }

    respondAdminPromotion($pdo, true, $featured ? 'Aula adicionada aos destaques.' : 'Aula removida dos destaques.', [
        'entityType' => 'class',
        'entityId'   => $classId,
        'featured'   => $featured,
    ]);
}

if ($action === 'toggle_trainer_featured') {
    $trainerId = (int) ($_POST['trainer_id'] ?? 0);
    $featured  = ($_POST['featured'] ?? '') === '1';

    if ($trainerId <= 0) {
        respondAdminPromotion($pdo, false, 'Treinador inválido.', [], 422);
    }

    $result = setTrainerFeatured($pdo, $trainerId, $featured);
    if (!$result['ok']) {
        respondAdminPromotion($pdo, false, $result['error'] ?? 'Não foi possível atualizar o treinador.', [], 422);
    }

    respondAdminPromotion($pdo, true, $featured ? 'Treinador adicionado aos destaques.' : 'Treinador removido dos destaques.', [
        'entityType' => 'trainer',
        'entityId'   => $trainerId,
        'featured'   => $featured,
    ]);
}

respondAdminPromotion($pdo, false, 'Pedido inválido.', [], 400);
