<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/promotions.php';

requireRole('admin');

$pdo     = getDatabase();
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_class_featured') {
        $classId  = (int) ($_POST['class_id'] ?? 0);
        $featured = ($_POST['featured'] ?? '') === '1';

        if ($classId <= 0) {
            $errors[] = 'Aula inválida.';
        } else {
            $result = setClassFeatured($pdo, $classId, $featured);
            if ($result['ok']) {
                $success = $featured ? 'Aula adicionada aos destaques.' : 'Aula removida dos destaques.';
            } else {
                $errors[] = $result['error'] ?? 'Não foi possível atualizar a aula.';
            }
        }
    }

    if ($action === 'toggle_trainer_featured') {
        $trainerId = (int) ($_POST['trainer_id'] ?? 0);
        $featured  = ($_POST['featured'] ?? '') === '1';

        if ($trainerId <= 0) {
            $errors[] = 'Treinador inválido.';
        } else {
            $result = setTrainerFeatured($pdo, $trainerId, $featured);
            if ($result['ok']) {
                $success = $featured ? 'Treinador adicionado aos destaques.' : 'Treinador removido dos destaques.';
            } else {
                $errors[] = $result['error'] ?? 'Não foi possível atualizar o treinador.';
            }
        }
    }
}

$classes         = fetchAllClassesForPromotionAdmin($pdo);
$trainers        = fetchTrainersForPromotionAdmin($pdo);
$featuredClassN  = countFeaturedClasses($pdo);
$featuredTrainerN = countFeaturedTrainers($pdo);

$dayLabels = [
    'monday'    => 'Seg',
    'tuesday'   => 'Ter',
    'wednesday' => 'Qua',
    'thursday'  => 'Qui',
    'friday'    => 'Sex',
    'saturday'  => 'Sáb',
    'sunday'    => 'Dom',
];

$typeLabels = [
    'yoga'    => 'Yoga',
    'pilates' => 'Pilates',
    'hiit'    => 'HIIT',
    'zumba'   => 'Zumba',
    'cycling' => 'Cycling',
    'boxing'  => 'Boxe',
    'other'   => 'Outro',
];

$adminHubLinks = [
    ['href' => pageUrl('admin_analytics.php'), 'label' => 'Analytics'],
    ['href' => pageUrl('admin_users.php'), 'label' => 'Utilizadores'],
    ['href' => pageUrl('admin_classes.php'), 'label' => 'Aulas'],
    ['href' => pageUrl('admin_equipment.php'), 'label' => 'Equipamentos'],
];

$currentPage      = 'admin';
$pageTitle        = 'Destaques Homepage | Admin';
$extraStylesheets = ['pages/admin.css', 'pages/admin-promotions.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>

<main class="admin-main">
    <div class="admin-container">

        <div class="admin-header">
            <div>
                <h1 class="admin-title">Destaques na <span>Homepage</span></h1>
                <p class="admin-promotions-subtitle">
                    Máximo de <?= MAX_FEATURED_CLASSES ?> aulas e <?= MAX_FEATURED_TRAINERS ?> treinadores em destaque
                </p>
            </div>
            <div class="admin-header-links">
                <?php foreach ($adminHubLinks as $link): ?>
                    <a href="<?= e($link['href']) ?>" class="btn-admin-secondary"><?= e($link['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="admin-card">
            <h2>Aulas em destaque <span class="admin-promotions-count"><?= $featuredClassN ?>/<?= MAX_FEATURED_CLASSES ?></span></h2>
            <?php if ($classes === []): ?>
                <p class="admin-promotions-empty">Não existem aulas no catálogo.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-promotions-table">
                        <thead>
                            <tr>
                                <th>Destaque</th>
                                <th>Aula</th>
                                <th>Horário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <?php $isFeatured = (int) ($class['featured'] ?? 0) === 1; ?>
                                <tr class="<?= $isFeatured ? 'row--featured' : '' ?>">
                                    <td>
                                        <form method="POST" action="<?= e(pageUrl('admin_promotions.php')) ?>" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="toggle_class_featured">
                                            <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                            <input type="hidden" name="featured" value="<?= $isFeatured ? '0' : '1' ?>">
                                            <button type="submit"
                                                    class="btn-admin-secondary admin-promotions-toggle"
                                                    <?= !$isFeatured && $featuredClassN >= MAX_FEATURED_CLASSES ? 'disabled title="Limite de destaques atingido"' : '' ?>>
                                                <?= $isFeatured ? 'Remover' : 'Destacar' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <strong><?= e($class['title']) ?></strong>
                                        <span class="tag tag--<?= e($class['type'] ?? 'other') ?>">
                                            <?= e($typeLabels[$class['type']] ?? $class['type'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= e($dayLabels[$class['day_of_week']] ?? $class['day_of_week'] ?? '—') ?>
                                        <?= e($class['start_time'] ?? '') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="admin-card">
            <h2>Treinadores em destaque <span class="admin-promotions-count"><?= $featuredTrainerN ?>/<?= MAX_FEATURED_TRAINERS ?></span></h2>
            <?php if ($trainers === []): ?>
                <p class="admin-promotions-empty">Não existem treinadores registados.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-promotions-table">
                        <thead>
                            <tr>
                                <th>Destaque</th>
                                <th>Treinador</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trainers as $trainer): ?>
                                <?php
                                $isFeatured = (int) ($trainer['featured'] ?? 0) === 1;
                                $isActive   = (int) ($trainer['active'] ?? 0) === 1;
                                ?>
                                <tr class="<?= $isFeatured ? 'row--featured' : '' ?><?= !$isActive ? ' row--inactive' : '' ?>">
                                    <td>
                                        <form method="POST" action="<?= e(pageUrl('admin_promotions.php')) ?>" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="toggle_trainer_featured">
                                            <input type="hidden" name="trainer_id" value="<?= (int) $trainer['id'] ?>">
                                            <input type="hidden" name="featured" value="<?= $isFeatured ? '0' : '1' ?>">
                                            <button type="submit"
                                                    class="btn-admin-secondary admin-promotions-toggle"
                                                    <?= (!$isActive || (!$isFeatured && $featuredTrainerN >= MAX_FEATURED_TRAINERS)) ? 'disabled' : '' ?>
                                                    <?= !$isActive ? ' title="Treinador inativo"' : (!$isFeatured && $featuredTrainerN >= MAX_FEATURED_TRAINERS ? ' title="Limite de destaques atingido"' : '') ?>>
                                                <?= $isFeatured ? 'Remover' : 'Destacar' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td><strong><?= e($trainer['name'] ?? $trainer['username']) ?></strong></td>
                                    <td>
                                        <span class="status-dot <?= $isActive ? 'status-dot--on' : 'status-dot--off' ?>">
                                            <?= $isActive ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
