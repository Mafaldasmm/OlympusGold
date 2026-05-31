<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

$trainer = requireRole('trainer');

$pdo = getDatabase();

/* -------------------------------------------------------------------------
 * Fetch trainer's assigned classes with enrollment count
 * ---------------------------------------------------------------------- */
$classStmt = $pdo->prepare('
    SELECT
        c.*,
        COUNT(e.id) AS enrolled_count
    FROM classes c
    LEFT JOIN enrollments e ON e.class_id = c.id
    WHERE c.trainer_id = :trainer_id
    GROUP BY c.id
    ORDER BY
        CASE c.day_of_week
            WHEN "monday"    THEN 1
            WHEN "tuesday"   THEN 2
            WHEN "wednesday" THEN 3
            WHEN "thursday"  THEN 4
            WHEN "friday"    THEN 5
            WHEN "saturday"  THEN 6
            WHEN "sunday"    THEN 7
        END,
        c.start_time
');
$classStmt->execute(['trainer_id' => $trainer['id']]);
$classes = $classStmt->fetchAll();

/* -------------------------------------------------------------------------
 * Fetch enrolled members for each class
 * ---------------------------------------------------------------------- */
$enrollments = [];
foreach ($classes as $class) {
    $stmt = $pdo->prepare('
        SELECT u.id, u.name, u.username, u.email, e.created_at
        FROM enrollments e
        JOIN users u ON u.id = e.user_id
        WHERE e.class_id = :class_id
        ORDER BY u.name
    ');
    $stmt->execute(['class_id' => $class['id']]);
    $enrollments[$class['id']] = $stmt->fetchAll();
}

$dayLabels = [
    'monday'    => 'Segunda-feira',
    'tuesday'   => 'Terça-feira',
    'wednesday' => 'Quarta-feira',
    'thursday'  => 'Quinta-feira',
    'friday'    => 'Sexta-feira',
    'saturday'  => 'Sábado',
    'sunday'    => 'Domingo',
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
$pageTitle        = 'Dashboard Treinador | Olympus Gold Gym';
$extraStylesheets = ['pages/trainer.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>

<main class="trainer-main">
    <div class="trainer-container">

        <div class="trainer-header">
            <div>
                <h1 class="trainer-title">Olá, <span><?= e($trainer['name'] ?? $trainer['username']) ?></span></h1>
                <p class="trainer-sub">As tuas aulas e inscrições</p>
            </div>
            <div class="trainer-stats">
                <div class="stat-box">
                    <span class="stat-number"><?= count($classes) ?></span>
                    <span class="stat-label">Aulas</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?= array_sum(array_column($classes, 'enrolled_count')) ?></span>
                    <span class="stat-label">Inscritos</span>
                </div>
            </div>
        </div>

        <section class="trainer-card trainer-dashboard-action-card">
            <div class="trainer-dashboard-action-card__content">
                <h2>Perfil público</h2>
                <p>Edita a tua biografia, especializações e certificações visíveis no teu perfil.</p>
            </div>
            <a href="<?= e(pageUrl('trainer_manage_profile.php')) ?>" class="trainer-dashboard-btn">
                Gerir perfil público
            </a>
        </section>

        <section class="trainer-card trainer-dashboard-action-card">
            <div class="trainer-dashboard-action-card__content">
                <h2>Equipamentos</h2>
                <p>Atualiza o estado dos equipamentos na área principal de treino.</p>
            </div>
            <a href="<?= e(pageUrl('manage_equipment.php')) ?>" class="trainer-dashboard-btn">
                Gerir equipamentos
            </a>
        </section>

        <?php if (empty($classes)): ?>
            <div class="trainer-card">
                <p class="empty-state">Ainda não tens aulas atribuídas. Contacta um administrador.</p>
            </div>
        <?php else: ?>
            <?php foreach ($classes as $class): ?>
                <div class="trainer-card">
                    <div class="class-header">
                        <div class="class-info">
                            <h2><?= e($class['title']) ?></h2>
                            <div class="class-meta">
                                <span class="tag tag--<?= e($class['type'] ?? 'other') ?>">
                                    <?= e($typeLabels[$class['type']] ?? $class['type']) ?>
                                </span>
                                <span class="meta-item">📅 <?= e($dayLabels[$class['day_of_week']] ?? $class['day_of_week']) ?></span>
                                <span class="meta-item">🕐 <?= e($class['start_time']) ?>–<?= e($class['end_time']) ?></span>
                                <span class="meta-item">👥 <?= (int) $class['enrolled_count'] ?>/<?= (int) $class['capacity'] ?> inscritos</span>
                            </div>
                            <?php if ($class['description']): ?>
                                <p class="class-desc"><?= e($class['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="capacity-bar-wrap">
                            <?php
                            $pct  = $class['capacity'] > 0
                                ? min(100, round(($class['enrolled_count'] / $class['capacity']) * 100))
                                : 0;
                            $full = $class['enrolled_count'] >= $class['capacity'];
                            ?>
                            <div class="capacity-label">
                                <?= $full ? '<span class="full-badge">LOTADO</span>' : "{$pct}% ocupado" ?>
                            </div>
                            <div class="capacity-bar">
                                <div class="capacity-bar-fill <?= $full ? 'full' : '' ?>"
                                     style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="members-section">
                        <h3>Membros Inscritos
                            <span class="badge-count"><?= count($enrollments[$class['id']]) ?></span>
                        </h3>

                        <?php if (empty($enrollments[$class['id']])): ?>
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
                                        <?php foreach ($enrollments[$class['id']] as $member): ?>
                                            <tr>
                                                <td><strong><?= e($member['name'] ?? '—') ?></strong></td>
                                                <td><?= e($member['username']) ?></td>
                                                <td><?= e($member['email'] ?? '—') ?></td>
                                                <td><?= e(substr($member['created_at'], 0, 10)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>