<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/admin_analytics.php';

requireRole('admin');

$loadError = false;
$data      = [
    'activity'          => ['active_members' => 0, 'total_enrollments' => 0, 'total_reviews' => 0, 'total_classes' => 0],
    'reviews'           => ['avg_rating' => null, 'review_count' => 0],
    'popular_classes'   => [],
    'classes'           => [],
    'top_rated_classes' => [],
    'low_rated_classes' => [],
    'equipment'         => ['available' => 0, 'in_use' => 0, 'maintenance' => 0, 'total' => 0],
    'summary'           => ['avg_utilization_pct' => 0, 'total_capacity' => 0, 'total_enrolled' => 0],
];

try {
    $data = loadAdminAnalytics(getDatabase());
} catch (Throwable) {
    $loadError = true;
}

$activity       = $data['activity'];
$reviews        = $data['reviews'];
$popularClasses = $data['popular_classes'];
$classes        = $data['classes'];
$topRated       = $data['top_rated_classes'];
$lowRated       = $data['low_rated_classes'];
$equipment      = $data['equipment'];
$summary        = $data['summary'];

$popularMax = 1;
foreach ($popularClasses as $row) {
    $popularMax = max($popularMax, (int) $row['enrolled_count']);
}

$dayLabelsShort = [
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

$dayLabels = [
    'monday'    => 'Segunda-feira',
    'tuesday'   => 'Terça-feira',
    'wednesday' => 'Quarta-feira',
    'thursday'  => 'Quinta-feira',
    'friday'    => 'Sexta-feira',
    'saturday'  => 'Sábado',
    'sunday'    => 'Domingo',
];

$trainerFilterOptions = [];
$typeFilterOptions    = [];
foreach ($classes as $class) {
    $typeFilterOptions[$class['type']] = $typeLabels[$class['type']] ?? $class['type'];
    if ($class['trainer_id'] !== null) {
        $trainerFilterOptions[(int) $class['trainer_id']] = $class['trainer_name'] !== ''
            ? $class['trainer_name']
            : 'Treinador #' . $class['trainer_id'];
    }
}
asort($trainerFilterOptions, SORT_NATURAL | SORT_FLAG_CASE);
asort($typeFilterOptions, SORT_NATURAL | SORT_FLAG_CASE);

$adminHubLinks = [
    ['href' => pageUrl('admin_promotions.php'), 'label' => 'Destaques'],
    ['href' => pageUrl('admin_users.php'), 'label' => 'Utilizadores'],
    ['href' => pageUrl('admin_classes.php'), 'label' => 'Aulas'],
    ['href' => pageUrl('admin_equipment.php'), 'label' => 'Equipamentos'],
];

$currentPage      = 'admin';
$pageTitle        = 'Analytics Admin | Olympus Gold Gym';
$extraStylesheets = [
    'pages/trainer.css',
    'pages/admin.css',
    'pages/equipment.css',
    'pages/trainer-analytics.css',
    'pages/admin-analytics.css',
];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>

<main class="admin-main admin-analytics-main">
    <div class="admin-container">

        <div class="admin-header">
            <div>
                <h1 class="admin-title">Analytics <span>Ginásio</span></h1>
                <p class="admin-analytics-subtitle">Métricas globais — todas as aulas, membros e equipamentos</p>
            </div>
            <div class="admin-header-links">
                <?php foreach ($adminHubLinks as $link): ?>
                    <a href="<?= e($link['href']) ?>" class="btn-admin-secondary"><?= e($link['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($loadError): ?>
            <div class="admin-card trainer-analytics-alert" role="alert">
                <p>Os analytics estão temporariamente indisponíveis. Tenta novamente dentro de momentos.</p>
            </div>
        <?php else: ?>

            <section class="trainer-analytics-summary" aria-label="Atividade de membros">
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $activity['active_members'] ?></span>
                    <span class="stat-label">Membros ativos</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $activity['total_enrollments'] ?></span>
                    <span class="stat-label">Inscrições</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $reviews['review_count'] ?></span>
                    <span class="stat-label">Avaliações</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= e(formatAdminAvgRating($reviews['avg_rating'], $reviews['review_count'])) ?></span>
                    <span class="stat-label">Média global</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $activity['total_classes'] ?></span>
                    <span class="stat-label">Aulas</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $summary['avg_utilization_pct'] ?>%</span>
                    <span class="stat-label">Ocupação média</span>
                </div>
            </section>

            <details class="admin-card admin-details trainer-analytics-details admin-analytics-details" open>
                <summary class="admin-details__summary">
                    <span class="admin-details__title">Visão Geral</span>
                </summary>
                <div class="admin-details__body">
                    <?php if ($classes === []): ?>
                        <p class="admin-analytics-empty">Ainda não existem aulas registadas.</p>
                    <?php else: ?>
                        <div class="admin-filters" role="search">
                            <input type="search"
                                   id="class-analytics-search"
                                   placeholder="Pesquisar aula, treinador, horário…"
                                   aria-label="Pesquisar aulas">
                            <select id="class-analytics-filter-type" aria-label="Filtrar por categoria">
                                <option value="">Todas as categorias</option>
                                <?php foreach ($typeFilterOptions as $typeKey => $typeLabel): ?>
                                    <option value="<?= e($typeKey) ?>"><?= e($typeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="class-analytics-filter-day" aria-label="Filtrar por dia">
                                <option value="">Todos os dias</option>
                                <?php foreach ($dayLabels as $dayKey => $dayLabel): ?>
                                    <option value="<?= e($dayKey) ?>"><?= e($dayLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="class-analytics-filter-trainer" aria-label="Filtrar por treinador">
                                <option value="">Todos os treinadores</option>
                                <?php foreach ($trainerFilterOptions as $trainerId => $trainerName): ?>
                                    <option value="<?= (int) $trainerId ?>"><?= e($trainerName) ?></option>
                                <?php endforeach; ?>
                                <option value="0">Sem treinador</option>
                            </select>
                            <button type="button" class="btn-admin-filter-clear" id="class-analytics-filter-clear">Limpar</button>
                            <span class="admin-filter-count" id="class-analytics-filter-count"></span>
                        </div>

                        <div class="members-table-wrap">
                            <table class="members-table trainer-analytics-table admin-analytics-table" id="class-analytics-table">
                                <thead>
                                    <tr>
                                        <th>Aula</th>
                                        <th>Treinador</th>
                                        <th>Horário</th>
                                        <th>Inscritos</th>
                                        <th>Capacidade</th>
                                        <th>Ocupação</th>
                                        <th>Média</th>
                                        <th>Avaliações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <?php
                                        $trainerDisplay = $class['trainer_name'] !== '' ? $class['trainer_name'] : '—';
                                        $dayLabel       = $dayLabelsShort[$class['day_of_week']] ?? $class['day_of_week'];
                                        $typeLabel      = $typeLabels[$class['type']] ?? $class['type'];
                                        $searchBlob     = strtolower(implode(' ', array_filter([
                                            $class['title'],
                                            $trainerDisplay,
                                            $typeLabel,
                                            $dayLabel,
                                            $dayLabels[$class['day_of_week']] ?? '',
                                            $class['start_time'],
                                            $class['end_time'],
                                        ])));
                                        $trainerIdAttr = $class['trainer_id'] !== null ? (string) (int) $class['trainer_id'] : '0';
                                        ?>
                                        <tr data-class-row
                                            data-search="<?= e($searchBlob) ?>"
                                            data-type="<?= e($class['type']) ?>"
                                            data-day="<?= e($class['day_of_week']) ?>"
                                            data-trainer="<?= e($trainerIdAttr) ?>">
                                            <td data-label="Aula">
                                                <strong><?= e($class['title']) ?></strong>
                                                <span class="tag tag--<?= e($class['type']) ?>">
                                                    <?= e($typeLabels[$class['type']] ?? $class['type']) ?>
                                                </span>
                                            </td>
                                            <td data-label="Treinador"><?= e($class['trainer_name'] !== '' ? $class['trainer_name'] : '—') ?></td>
                                            <td data-label="Horário">
                                                <?= e($dayLabelsShort[$class['day_of_week']] ?? $class['day_of_week']) ?>
                                                <?= e($class['start_time']) ?>–<?= e($class['end_time']) ?>
                                            </td>
                                            <td data-label="Inscritos"><?= (int) $class['enrolled_count'] ?></td>
                                            <td data-label="Capacidade"><?= (int) $class['capacity'] ?></td>
                                            <td data-label="Ocupação"><?= (int) $class['utilization_pct'] ?>%</td>
                                            <td data-label="Média"><?= e(formatAdminAvgRating($class['avg_rating'], $class['review_count'])) ?></td>
                                            <td data-label="Avaliações"><?= (int) $class['review_count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="admin-analytics-empty classes-filter-empty" hidden>Nenhuma aula corresponde aos filtros.</p>
                    <?php endif; ?>
                </div>
            </details>

            <details class="admin-card admin-details trainer-analytics-details admin-analytics-details">
                <summary class="admin-details__summary">
                    <span class="admin-details__title">Aulas mais populares</span>
                </summary>
                <div class="admin-details__body">
                    <?php if ($popularClasses === []): ?>
                        <p class="admin-analytics-empty">Sem aulas para mostrar.</p>
                    <?php else: ?>
                        <p class="trainer-analytics-chart-note">Top <?= count($popularClasses) ?> por inscrições</p>
                        <ul class="trainer-analytics-chart">
                            <?php foreach ($popularClasses as $class): ?>
                                <?php
                                $count  = (int) $class['enrolled_count'];
                                $barPct = (int) round(($count / $popularMax) * 100);
                                ?>
                                <li class="trainer-analytics-chart__row">
                                    <div class="trainer-analytics-chart__label">
                                        <span class="trainer-analytics-chart__title"><?= e($class['title']) ?></span>
                                        <span class="trainer-analytics-chart__meta"><?= $count ?> inscrições</span>
                                    </div>
                                    <div class="trainer-analytics-chart__bar" role="img"
                                         aria-label="<?= e($class['title']) ?>: <?= $count ?> inscrições">
                                        <div class="trainer-analytics-chart__fill" data-utilization="<?= $barPct ?>"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </details>

            <details class="admin-card admin-details trainer-analytics-details admin-analytics-details">
                <summary class="admin-details__summary">
                    <span class="admin-details__title">Ocupação</span>
                </summary>
                <div class="admin-details__body trainer-analytics-chart-card">
                    <?php if ($classes === []): ?>
                        <p class="admin-analytics-empty">Sem dados de ocupação.</p>
                    <?php else: ?>
                        <p class="trainer-analytics-chart-note">
                            <?= (int) $summary['total_enrolled'] ?> inscrições em
                            <?= (int) $summary['total_capacity'] ?> vagas (máx. 100% por aula)
                        </p>
                        <ul class="trainer-analytics-chart">
                            <?php foreach ($classes as $class): ?>
                                <li class="trainer-analytics-chart__row">
                                    <div class="trainer-analytics-chart__label">
                                        <span class="trainer-analytics-chart__title"><?= e($class['title']) ?></span>
                                        <span class="trainer-analytics-chart__meta">
                                            <?= (int) $class['enrolled_count'] ?>/<?= (int) $class['capacity'] ?>
                                            (<?= (int) $class['utilization_pct'] ?>%)
                                        </span>
                                    </div>
                                    <div class="trainer-analytics-chart__bar" role="img"
                                         aria-label="<?= e($class['title']) ?>: <?= (int) $class['utilization_pct'] ?>% de ocupação">
                                        <div class="trainer-analytics-chart__fill <?= $class['utilization_pct'] >= 100 ? 'is-full' : '' ?>"
                                             data-utilization="<?= (int) $class['utilization_pct'] ?>"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </details>

            <details class="admin-card admin-details trainer-analytics-details admin-analytics-details">
                <summary class="admin-details__summary">
                    <span class="admin-details__title">Avaliações</span>
                </summary>
                <div class="admin-details__body">
                    <p class="trainer-analytics-chart-note">
                        Total: <?= (int) $reviews['review_count'] ?>
                        — Média global: <?= e(formatAdminAvgRating($reviews['avg_rating'], $reviews['review_count'])) ?>
                    </p>

                    <?php if ($reviews['review_count'] === 0): ?>
                        <p class="admin-analytics-empty">Ainda não existem avaliações no sistema.</p>
                    <?php else: ?>
                        <div class="admin-analytics-ratings-grid">
                            <div>
                                <h3>Melhor classificadas</h3>
                                <?php if ($topRated === []): ?>
                                    <p class="admin-analytics-empty">Sem dados.</p>
                                <?php else: ?>
                                    <ul class="admin-analytics-ratings-list">
                                        <?php foreach ($topRated as $class): ?>
                                            <li>
                                                <span><?= e($class['title']) ?></span>
                                                <span class="admin-analytics-ratings-list__meta">
                                                    <?= e(number_format($class['avg_rating'], 1, ',', '')) ?> / 5
                                                    (<?= (int) $class['review_count'] ?>)
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3>Menor classificadas</h3>
                                <?php if ($lowRated === []): ?>
                                    <p class="admin-analytics-empty">Sem dados.</p>
                                <?php else: ?>
                                    <ul class="admin-analytics-ratings-list">
                                        <?php foreach ($lowRated as $class): ?>
                                            <li>
                                                <span><?= e($class['title']) ?></span>
                                                <span class="admin-analytics-ratings-list__meta">
                                                    <?= e(number_format($class['avg_rating'], 1, ',', '')) ?> / 5
                                                    (<?= (int) $class['review_count'] ?>)
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </details>

            <details class="admin-card admin-details trainer-analytics-details admin-analytics-details">
                <summary class="admin-details__summary">
                    <span class="admin-details__title">Equipamentos</span>
                </summary>
                <div class="admin-details__body">
                    <?php if ($equipment['total'] === 0): ?>
                        <p class="admin-analytics-empty">Sem equipamentos na área principal.</p>
                    <?php else: ?>
                        <div class="equipment-summary admin-analytics-equipment-summary" aria-label="Estado dos equipamentos">
                            <div class="equipment-summary__item equipment-summary__item--available">
                                <span class="equipment-summary__count"><?= (int) $equipment['available'] ?></span>
                                <span class="equipment-summary__label">Disponível</span>
                            </div>
                            <div class="equipment-summary__item equipment-summary__item--in-use">
                                <span class="equipment-summary__count"><?= (int) $equipment['in_use'] ?></span>
                                <span class="equipment-summary__label">Em uso</span>
                            </div>
                            <div class="equipment-summary__item equipment-summary__item--maintenance">
                                <span class="equipment-summary__count"><?= (int) $equipment['maintenance'] ?></span>
                                <span class="equipment-summary__label">Em manutenção</span>
                            </div>
                        </div>
                        <p class="trainer-analytics-chart-note">
                            Total: <?= (int) $equipment['total'] ?> equipamentos (área principal)
                        </p>
                    <?php endif; ?>
                </div>
            </details>

        <?php endif; ?>

    </div>
</main>

<?php if (!$loadError && $classes !== []): ?>
<script src="<?= e(asset('js/admin-analytics.js')) ?>"></script>
<?php endif; ?>
<?php
$extraScripts = ['chart-bars.js'];
require INCLUDES_PATH . '/footer.php';
?>
