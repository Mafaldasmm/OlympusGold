<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/class_waitlist.php';

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
$myWaitlistPositions = [];
if (isAuthenticated()) {
    $memberId = (int) $_SESSION['user_id'];
    $rows = dbQuery(
        'SELECT class_id FROM enrollments WHERE user_id = :uid',
        ['uid' => $memberId]
    )->fetchAll();
    $myEnrollments = array_column($rows, 'class_id');

    if ($user !== null && $user['role'] === 'member') {
        $myWaitlistPositions = fetchMemberWaitlistPositions($memberId);
    }
}

$waitlistJoinUrl  = pageUrl('action_waitlist_join.php');
$waitlistLeaveUrl = pageUrl('action_waitlist_leave.php');

/* -------------------------------------------------------------------------
 * Flash messages
 * ---------------------------------------------------------------------- */
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$csrfToken = csrfToken();
$isScheduleMember = $user !== null && $user['role'] === 'member';
$hasScheduleFlash = $flashSuccess !== '' || $flashError !== '';

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

            <details class="schedule-filters-panel" data-mobile-collapse data-open-desktop="true">
                <summary class="schedule-filters-panel__summary">Filtros de pesquisa</summary>
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
            </details>
        </div>

        <?php if (empty($classes)): ?>
            <p class="empty-schedule">Nenhuma aula encontrada para os filtros selecionados.</p>
        <?php else: ?>
        <div class="schedule-list-wrap">
        <div class="card-grid">
            <?php foreach ($classes as $class):
                $spots      = (int) $class['capacity'] - (int) $class['enrolled_count'];
                $isFull     = $spots <= 0;
                $isEnrolled = in_array($class['id'], $myEnrollments, true);
                $waitlistPosition = $myWaitlistPositions[(int) $class['id']] ?? null;
                $isOnWaitlist = $waitlistPosition !== null;
                $day        = $dayLabels[$class['day_of_week']] ?? $class['day_of_week'];
            ?>
                <article class="card class-card"
                         data-class-id="<?= (int) $class['id'] ?>"
                         data-capacity="<?= (int) $class['capacity'] ?>"
                         data-enrolled="<?= (int) $class['enrolled_count'] ?>">
                    <div class="card-header">
                        <span class="tag <?= e($class['type'] ?? 'other') ?>">
                            <?= e($typeLabels[$class['type']] ?? $class['type']) ?>
                        </span>
                        <h3><?= e($class['title']) ?></h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong>
                            <?php if (!empty($class['trainer_user_id'])): ?>
                                <a href="<?= e(pageUrl('trainer.php?id=' . (int) $class['trainer_user_id'])) ?>"
                                   class="trainer-profile-link">
                                    <?= e($class['trainer_name'] ?? 'N/D') ?>
                                </a>
                            <?php else: ?>
                                <?= e($class['trainer_name'] ?? 'N/D') ?>
                            <?php endif; ?>
                        </p>
                        <p><strong>Horário:</strong>
                            <?= e($day) ?> | <?= e($class['start_time']) ?> - <?= e($class['end_time']) ?>
                        </p>
                        <p class="class-spots">
                            <strong>Vagas:</strong>
                            <span class="class-spots-value">
<?php if ($isFull): ?>
                                <span class="full">CHEIO</span>
<?php else: ?>
                                <?= $spots ?> / <?= (int) $class['capacity'] ?>
<?php endif; ?>
                            </span>
                        </p>

                    </div>
                    <div class="card-footer">
                        <?php if (!isAuthenticated()): ?>
                            <a href="<?= e(pageUrl('login.php')) ?>" class="btn-primary">
                                Iniciar sessão para inscrever
                            </a>

                        <?php elseif ($user['role'] === 'member'): ?>
                            <div class="class-enrollment-actions">
                            <?php if ($isEnrolled): ?>
                                <form class="enrollment-form"
                                      method="POST"
                                      action="<?= e(pageUrl('action_cancel.php')) ?>"
                                      data-enrollment-action="cancel">
                                    <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                    <input type="hidden" name="redirect" value="schedule.php">
                                    <button type="submit" class="btn-primary btn-cancel">Cancelar Inscrição</button>
                                </form>
                            <?php elseif ($isOnWaitlist): ?>
                                <p class="waitlist-status">Lista de espera — posição <strong><?= (int) $waitlistPosition ?></strong></p>
                                <form class="enrollment-form"
                                      method="POST"
                                      action="<?= e($waitlistLeaveUrl) ?>"
                                      data-enrollment-action="waitlist-leave">
                                    <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                    <input type="hidden" name="redirect" value="schedule.php">
                                    <button type="submit" class="btn-primary btn-cancel">Sair da lista de espera</button>
                                </form>
                            <?php elseif ($isFull): ?>
                                <form class="enrollment-form"
                                      method="POST"
                                      action="<?= e($waitlistJoinUrl) ?>"
                                      data-enrollment-action="waitlist-join">
                                    <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                    <input type="hidden" name="redirect" value="schedule.php">
                                    <button type="submit" class="btn-primary btn-waitlist">Entrar na lista de espera</button>
                                </form>
                            <?php else: ?>
                                <form class="enrollment-form"
                                      method="POST"
                                      action="<?= e(pageUrl('action_book.php')) ?>"
                                      data-enrollment-action="book">
                                    <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                    <input type="hidden" name="redirect" value="schedule.php">
                                    <button type="submit" class="btn-primary">Inscrever</button>
                                </form>
                            <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <a href="<?= e(pageUrl('class.php?id=' . (int) $class['id'])) ?>" class="btn-secondary" style="display:block; text-align:center;">
                            Ver Detalhes
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        </div>
        <?php endif; ?>
    </section>
</main>

<?php if ($flashSuccess !== ''): ?>
    <span class="visually-hidden" data-toast-on-load data-toast-type="success"><?= e($flashSuccess) ?></span>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
    <span class="visually-hidden" data-toast-on-load data-toast-type="error"><?= e($flashError) ?></span>
<?php endif; ?>

<?php if ($isScheduleMember): ?>
<script>
window.__ENROLLMENT_CONFIG = {
    bookUrl: <?= json_encode(pageUrl('action_book.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    cancelUrl: <?= json_encode(pageUrl('action_cancel.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    waitlistJoinUrl: <?= json_encode($waitlistJoinUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    waitlistLeaveUrl: <?= json_encode($waitlistLeaveUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    redirect: 'schedule.php',
    cardSelector: '.class-card',
    actionsSelector: '.class-enrollment-actions',
    spotsSelector: '.class-spots-value'
};
</script>
<?php
    $extraScripts = ['enrollment-actions.js'];
endif;
?>

<?php require INCLUDES_PATH . '/footer.php'; ?>