<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/trainer_analytics.php';
require_once INCLUDES_PATH . '/class_waitlist.php';

$user = requireLogin();

if ($user['role'] === 'admin') {
    redirect(pageUrl('admin_analytics.php'));
}

$trainer = requireRole('trainer');

$loadError   = false;
$enrollments = [];
$waitlists   = [];
$analytics   = [
    'classes' => [],
    'summary' => [
        'class_count'         => 0,
        'total_enrollments'   => 0,
        'avg_utilization_pct' => 0,
        'avg_rating'          => null,
        'review_count'        => 0,
    ],
];

try {
    $pdo       = getDatabase();
    $trainerId = (int) $trainer['id'];
    $analytics = loadTrainerAnalytics($pdo, $trainerId);

    if ($analytics['classes'] !== []) {
        $enrollments = fetchTrainerClassEnrollmentsByClass($pdo, $trainerId);
        foreach ($analytics['classes'] as $class) {
            $classId             = (int) $class['id'];
            $waitlists[$classId] = annotateWaitlistPositions(fetchClassWaitlist($classId));
        }
    }
} catch (Throwable) {
    $loadError = true;
}

$classes = $analytics['classes'];
$summary = $analytics['summary'];

$dayLabels = [
    'monday'    => 'Segunda-feira',
    'tuesday'   => 'Terça-feira',
    'wednesday' => 'Quarta-feira',
    'thursday'  => 'Quinta-feira',
    'friday'    => 'Sexta-feira',
    'saturday'  => 'Sábado',
    'sunday'    => 'Domingo',
];

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

$currentPage      = 'trainer';
$pageTitle        = 'Analytics Treinador | Olympus Gold Gym';
$extraStylesheets = ['pages/trainer.css', 'pages/admin.css', 'pages/trainer-analytics.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>

<main class="trainer-main trainer-analytics-main">
    <div class="trainer-container">

        <div class="trainer-header trainer-analytics-header">
            <div>
                <p class="trainer-analytics-back">
                    <a href="<?= e(pageUrl('trainer_dashboard.php')) ?>">← Dashboard</a>
                </p>
                <h1 class="trainer-title">Analytics <span><?= e($trainer['name'] ?? $trainer['username']) ?></span></h1>
                <p class="trainer-sub">Métricas, lista de espera e inscritos — só das tuas aulas</p>
            </div>
        </div>

        <?php if ($loadError): ?>
            <div class="trainer-card trainer-analytics-alert" role="alert">
                <p>Os analytics estão temporariamente indisponíveis. Tenta novamente dentro de momentos.</p>
            </div>
        <?php elseif (empty($classes)): ?>
            <div class="trainer-card">
                <p class="empty-state">Ainda não tens aulas atribuídas. Quando um administrador te atribuir aulas, as métricas aparecerão aqui.</p>
            </div>
        <?php else: ?>
            <section class="trainer-analytics-summary" aria-label="Resumo geral">
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $summary['class_count'] ?></span>
                    <span class="stat-label">Aulas</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $summary['total_enrollments'] ?></span>
                    <span class="stat-label">Inscrições</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $summary['avg_utilization_pct'] ?>%</span>
                    <span class="stat-label">Ocupação média</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= e(formatTrainerAvgRating($summary['avg_rating'], $summary['review_count'])) ?></span>
                    <span class="stat-label">Média avaliações</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= (int) $summary['review_count'] ?></span>
                    <span class="stat-label">Avaliações</span>
                </div>
            </section>

            <details class="trainer-card admin-details trainer-analytics-details" open>
                <summary class="admin-details__summary">
                    <span class="admin-details__title">Visão Geral</span>
                </summary>
                <div class="admin-details__body">
                    <div class="members-table-wrap">
                        <table class="members-table trainer-analytics-table">
                            <thead>
                                <tr>
                                    <th>Aula</th>
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
                                    <tr>
                                        <td data-label="Aula">
                                            <strong><?= e($class['title']) ?></strong>
                                            <span class="tag tag--<?= e($class['type']) ?>">
                                                <?= e($typeLabels[$class['type']] ?? $class['type']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Horário">
                                            <?= e($dayLabelsShort[$class['day_of_week']] ?? $class['day_of_week']) ?>
                                            <?= e($class['start_time']) ?>–<?= e($class['end_time']) ?>
                                        </td>
                                        <td data-label="Inscritos"><?= (int) $class['enrolled_count'] ?></td>
                                        <td data-label="Capacidade"><?= (int) $class['capacity'] ?></td>
                                        <td data-label="Ocupação"><?= (int) $class['utilization_pct'] ?>%</td>
                                        <td data-label="Média">
                                            <?= e(formatTrainerAvgRating($class['avg_rating'], $class['review_count'])) ?>
                                        </td>
                                        <td data-label="Avaliações"><?= (int) $class['review_count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>

            <details class="trainer-card admin-details trainer-analytics-details">
                <summary class="admin-details__summary">
                    <span class="admin-details__title">Ocupação</span>
                </summary>
                <div class="admin-details__body trainer-analytics-chart-card">
                    <p class="trainer-analytics-chart-note">Percentagem de vagas ocupadas (inscrições ÷ capacidade, máx. 100%)</p>
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
                </div>
            </details>

            <details class="trainer-card admin-details trainer-analytics-details">
                <summary class="admin-details__summary">
                    <span class="admin-details__title">Lista de espera e inscritos</span>
                </summary>
                <div class="admin-details__body trainer-analytics-operations">
                <?php foreach ($classes as $class): ?>
                    <?php
                    $classId      = (int) $class['id'];
                    $classMembers = $enrollments[$classId] ?? [];
                    $classWaitlist = $waitlists[$classId] ?? [];
                    ?>
                    <details class="admin-details trainer-analytics-class-details">
                        <summary class="admin-details__summary trainer-analytics-class-details__summary">
                            <span class="admin-details__title trainer-analytics-class-details__title">
                                <span class="trainer-analytics-class-details__name"><?= e($class['title']) ?></span>
                                <span class="class-meta">
                                    <span class="tag tag--<?= e($class['type']) ?>">
                                        <?= e($typeLabels[$class['type']] ?? $class['type']) ?>
                                    </span>
                                    <span class="meta-item">📅 <?= e($dayLabels[$class['day_of_week']] ?? $class['day_of_week']) ?></span>
                                    <span class="meta-item">🕐 <?= e($class['start_time']) ?>–<?= e($class['end_time']) ?></span>
                                </span>
                            </span>
                        </summary>

                        <div class="admin-details__body trainer-analytics-class-details__body">
                        <div class="members-section">
                            <h4>Lista de espera
                                <span class="badge-count"><?= count($classWaitlist) ?></span>
                            </h4>

                            <?php if ($classWaitlist === []): ?>
                                <p class="empty-state-small">Ninguém na lista de espera.</p>
                            <?php else: ?>
                                <ol class="trainer-waitlist-list">
                                    <?php foreach ($classWaitlist as $waitlisted): ?>
                                        <li>
                                            <span class="waitlist-position">#<?= (int) $waitlisted['position'] ?></span>
                                            <?= e((string) ($waitlisted['member_name'] ?? $waitlisted['username'])) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endif; ?>
                        </div>

                        <div class="members-section">
                            <h4>Membros inscritos
                                <span class="badge-count"><?= count($classMembers) ?></span>
                            </h4>

                            <?php if ($classMembers === []): ?>
                                <p class="empty-state-small">Nenhum membro inscrito nesta aula.</p>
                            <?php else: ?>
                                <div class="members-table-wrap">
                                    <table class="members-table">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Inscrito em</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($classMembers as $member): ?>
                                                <tr>
                                                    <td data-label="Nome"><strong><?= e($member['name'] !== '' ? $member['name'] : '—') ?></strong></td>
                                                    <td data-label="Username"><?= e($member['username']) ?></td>
                                                    <td data-label="Email"><?= e($member['email'] !== '' ? $member['email'] : '—') ?></td>
                                                    <td data-label="Inscrito em"><?= e(substr($member['created_at'], 0, 10)) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        </div>
                    </details>
                <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>

    </div>
</main>

<?php
$extraScripts = ['chart-bars.js'];
require INCLUDES_PATH . '/footer.php';
?>
