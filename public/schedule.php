<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

$pdo  = getDatabase();
$user = currentUser();

/* -------------------------------------------------------------------------
 * Filters — whitelist validation
 * ---------------------------------------------------------------------- */
$allowedTypes = ['yoga', 'pilates', 'hiit', 'zumba', 'cycling', 'boxing', 'other'];
$allowedDays  = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

$filterType    = $_GET['type']    ?? '';
$filterDay     = $_GET['day']     ?? '';
$filterTrainer = (int) ($_GET['trainer'] ?? 0);

if (!in_array($filterType, $allowedTypes, true)) $filterType    = '';
if (!in_array($filterDay,  $allowedDays,  true)) $filterDay     = '';
if ($filterTrainer < 0)                          $filterTrainer = 0;

/* -------------------------------------------------------------------------
 * Build query with optional filters
 * ---------------------------------------------------------------------- */
$where  = [];
$params = [];

if ($filterType !== '') {
    $where[]        = 'c.type = :type';
    $params['type'] = $filterType;
}
if ($filterDay !== '') {
    $where[]       = 'c.day_of_week = :day';
    $params['day'] = $filterDay;
}
if ($filterTrainer > 0) {
    $where[]            = 'c.trainer_id = :trainer_id';
    $params['trainer_id'] = $filterTrainer;
}

$sql = '
    SELECT c.*, u.name AS trainer_name, u.id AS trainer_user_id,
           (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id) AS enrolled_count
    FROM classes c
    LEFT JOIN users u ON u.id = c.trainer_id
    ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . '
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
';

$classes = dbQuery($sql, $params)->fetchAll();

/* -------------------------------------------------------------------------
 * Fetch trainers for filter dropdown (only active trainers)
 * ---------------------------------------------------------------------- */
$trainers = dbQuery(
    "SELECT id, name FROM users WHERE role = 'trainer' AND active = 1 ORDER BY name"
)->fetchAll();

/* -------------------------------------------------------------------------
 * Fetch enrollments of current user (if logged in)
 * ---------------------------------------------------------------------- */
$myEnrollments = [];
if (isAuthenticated()) {
    $rows = dbQuery(
        'SELECT class_id FROM enrollments WHERE user_id = :uid',
        ['uid' => $_SESSION['user_id']]
    )->fetchAll();
    $myEnrollments = array_column($rows, 'class_id');
}

/* -------------------------------------------------------------------------
 * Flash messages
 * ---------------------------------------------------------------------- */
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$csrfToken = csrfToken();

/* -------------------------------------------------------------------------
 * Labels
 * ---------------------------------------------------------------------- */
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

$currentPage      = 'schedule';
$pageTitle        = 'Horário de Aulas - Olympus Gold Gym';
$extraStylesheets = ['pages/schedule.css', 'pages/class.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>

<main>
    <section class="schedule-section">
        <div class="schedule-header">
            <h2><span class="highlight">HORÁRIOS</span> DAS AULAS</h2>
            <p>Filtre por modalidade, treinador ou dia para encontrar o seu treino ideal.</p>

            <form class="filters-bar" method="GET" action="<?= e(pageUrl('schedule.php')) ?>">
                <select name="type" aria-label="Filtrar por Modalidade">
                    <option value="">Todas as Modalidades</option>
                    <?php foreach ($typeLabels as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $filterType === $val ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="day" aria-label="Filtrar por Dia">
                    <option value="">Qualquer Dia</option>
                    <?php foreach ($dayLabels as $val => $label): ?>
                        <option value="<?= e($val) ?>" <?= $filterDay === $val ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="trainer" aria-label="Filtrar por Treinador">
                    <option value="">Todos os Treinadores</option>
                    <?php foreach ($trainers as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= $filterTrainer === (int) $t['id'] ? 'selected' : '' ?>>
                            <?= e($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">Filtrar</button>
                <?php if ($filterType !== '' || $filterDay !== '' || $filterTrainer > 0): ?>
                    <a href="<?= e(pageUrl('schedule.php')) ?>" class="btn-clear">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($flashSuccess !== ''): ?>
            <p class="flash flash-success"><?= e($flashSuccess) ?></p>
        <?php endif; ?>
        <?php if ($flashError !== ''): ?>
            <p class="flash flash-error"><?= e($flashError) ?></p>
        <?php endif; ?>

        <?php if (empty($classes)): ?>
            <p class="empty-schedule">Nenhuma aula encontrada para os filtros selecionados.</p>
        <?php else: ?>
        <div class="card-grid">
            <?php foreach ($classes as $class):
                $spots      = (int) $class['capacity'] - (int) $class['enrolled_count'];
                $isFull     = $spots <= 0;
                $isEnrolled = in_array($class['id'], $myEnrollments, true);
                $day        = $dayLabels[$class['day_of_week']] ?? $class['day_of_week'];
            ?>
                <article class="card">
                    <div class="card-header">
                        <span class="tag <?= e($class['type'] ?? 'other') ?>">
                            <?= e($typeLabels[$class['type']] ?? $class['type']) ?>
                        </span>
                        <h3><?= e($class['title']) ?></h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> <?= e($class['trainer_name'] ?? 'N/D') ?></p>
                        <p><strong>Horário:</strong>
                            <?= e($day) ?> | <?= e($class['start_time']) ?> - <?= e($class['end_time']) ?>
                        </p>
                        <p><strong>Vagas:</strong>
                            <?php if ($isFull): ?>
                                <span class="full">CHEIO</span>
                            <?php else: ?>
                                <?= $spots ?> / <?= (int) $class['capacity'] ?>
                            <?php endif; ?>
                        </p>

                    </div>
                    <div class="card-footer">
                        <?php if (!isAuthenticated()): ?>
                            <a href="<?= e(pageUrl('login.php')) ?>" class="btn-primary">
                                Iniciar sessão para inscrever
                            </a>

                        <?php elseif ($user['role'] === 'member'): ?>
                            <?php if ($isEnrolled): ?>
                                <form method="POST" action="<?= e(pageUrl('action_cancel.php')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="class_id"   value="<?= (int) $class['id'] ?>">
                                    <button type="submit" class="btn-primary btn-cancel">Cancelar Inscrição</button>
                                </form>
                            <?php elseif ($isFull): ?>
                                <button class="btn-primary disabled" disabled>Aula Cheia</button>
                            <?php else: ?>
                                <form method="POST" action="<?= e(pageUrl('action_book.php')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="class_id"   value="<?= (int) $class['id'] ?>">
                                    <button type="submit" class="btn-primary">Inscrever</button>
                                </form>
                            <?php endif; ?>

                        <?php endif; ?>

                        <a href="<?= e(pageUrl('class.php?id=' . (int) $class['id'])) ?>" class="btn-secondary" style="display:block; text-align:center;">
                            Ver Detalhes
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>