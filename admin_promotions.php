<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/promotions.php';

requireRole('admin');

$pdo = getDatabase();

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

        <details class="admin-card admin-details" id="promotions-classes-panel" open>
            <summary class="admin-details__summary">
                <span class="admin-details__title">
                    Aulas em destaque
                    <span class="admin-promotions-count" id="promotions-class-count"><?= $featuredClassN ?>/<?= MAX_FEATURED_CLASSES ?></span>
                </span>
            </summary>
            <div class="admin-details__body" data-promotion-panel="class">
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
                                <tr class="<?= $isFeatured ? 'row--featured' : '' ?>"
                                    data-promotion-row
                                    data-promotion-type="class"
                                    data-entity-id="<?= (int) $class['id'] ?>"
                                    data-featured="<?= $isFeatured ? '1' : '0' ?>">
                                    <td>
                                        <form method="POST"
                                              action="<?= e(pageUrl('action_admin_promotion.php')) ?>"
                                              class="inline-form admin-promotion-form"
                                              data-promotion-type="class">
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
            </div>
        </details>

        <details class="admin-card admin-details" id="promotions-trainers-panel">
            <summary class="admin-details__summary">
                <span class="admin-details__title">
                    Treinadores em destaque
                    <span class="admin-promotions-count" id="promotions-trainer-count"><?= $featuredTrainerN ?>/<?= MAX_FEATURED_TRAINERS ?></span>
                </span>
            </summary>
            <div class="admin-details__body" data-promotion-panel="trainer">
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
                                <tr class="<?= $isFeatured ? 'row--featured' : '' ?><?= !$isActive ? ' row--inactive' : '' ?>"
                                    data-promotion-row
                                    data-promotion-type="trainer"
                                    data-entity-id="<?= (int) $trainer['id'] ?>"
                                    data-featured="<?= $isFeatured ? '1' : '0' ?>"
                                    data-active="<?= $isActive ? '1' : '0' ?>">
                                    <td>
                                        <form method="POST"
                                              action="<?= e(pageUrl('action_admin_promotion.php')) ?>"
                                              class="inline-form admin-promotion-form"
                                              data-promotion-type="trainer">
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
            </div>
        </details>

    </div>
</main>

<?php
$extraScripts = ['admin-promotions.js'];
require INCLUDES_PATH . '/footer.php';
?>
