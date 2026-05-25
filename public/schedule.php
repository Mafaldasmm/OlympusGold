<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/session.php';

$currentPage        = 'schedule';
$pageTitle          = 'Horário de Aulas - Olympus Gold Gym';
$extraStylesheets   = ['pages/schedule.css'];

$allowedTypes  = ['yoga', 'pilates', 'zumba', 'hiit'];
$allowedDays   = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

$filterType    = in_array($_GET['type'] ?? '', $allowedTypes) ? $_GET['type'] : '';
$filterDay     = in_array($_GET['day'] ?? '', $allowedDays)   ? $_GET['day']  : '';
$filterTrainer = isset($_GET['trainer']) ? (int)$_GET['trainer'] : 0;

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
    $where[]           = 'c.trainer_id = :trainer';
    $params['trainer'] = $filterTrainer;
}

$sql = '
    SELECT c.*, u.name AS trainer_name,
           (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id) AS enrolled
    FROM classes c
    LEFT JOIN users u ON u.id = c.trainer_id
' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . '
ORDER BY c.day_of_week, c.start_time
';

$classes = dbQuery($sql, $params)->fetchAll();

// IDs das aulas em que o utilizador já está inscrito
$userEnrollments = [];
if (isLoggedIn()) {
    $rows = dbQuery(
        'SELECT class_id FROM enrollments WHERE user_id = :uid',
        ['uid' => $_SESSION['user_id']]
    )->fetchAll();
    $userEnrollments = array_column($rows, 'class_id');
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

// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$csrfToken = generateCsrfToken();

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
    <main>
        <section class="schedule-section">
            <div class="schedule-header">
                <h2><span class="highlight">HORÁRIOS</span> DAS AULAS</h2>
                <p>Filtre por modalidade, treinador ou dia para encontrar o seu treino ideal.</p>

                <form class="filters-bar" method="GET">
                    <select name="type" aria-label="Filtrar por Modalidade">
                        <option value="">Todas as Modalidades</option>
                        <?php foreach ($allowedTypes as $t): ?>
                            <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>>
                                <?= ucfirst($t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="day" aria-label="Filtrar por Dia">
                        <option value="">Qualquer Dia</option>
                        <?php foreach ($allowedDays as $d): ?>
                            <option value="<?= $d ?>" <?= $filterDay === $d ? 'selected' : '' ?>>
                                <?= $dayLabels[$d] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="trainer" aria-label="Filtrar por Treinador">
                        <option value="">Todos os Treinadores</option>
                        <?php
                        $trainers = dbQuery('SELECT id, name FROM users WHERE role = :r', ['r' => 'trainer'])->fetchAll();
                        foreach ($trainers as $tr):
                        ?>
                            <option value="<?= $tr['id'] ?>" <?= $filterTrainer === $tr['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tr['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">Filtrar</button>
                </form>
            </div>

            <?php if ($flashSuccess !== ''): ?>
                <p class="flash flash-success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <?php if ($flashError !== ''): ?>
                <p class="flash flash-error"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <div class="card-grid">
            <?php foreach ($classes as $class):
                $spots      = $class['capacity'] - $class['enrolled'];
                $isFull     = $spots <= 0;
                $isEnrolled = in_array($class['id'], $userEnrollments, true);
                $day        = $dayLabels[$class['day_of_week']] ?? $class['day_of_week'];
            ?>
                <article class="card">
                    <div class="card-header">
                        <span class="tag <?= htmlspecialchars($class['type'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($class['type'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <h3><?= htmlspecialchars($class['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> <?= htmlspecialchars($class['trainer_name'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Horário:</strong> <?= $day ?> | <?= $class['start_time'] ?> - <?= $class['end_time'] ?></p>
                        <p><strong>Vagas:</strong>
                            <?php if ($isFull): ?>
                                <span class="full">CHEIO</span>
                            <?php else: ?>
                                <?= $spots ?> / <?= $class['capacity'] ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-footer">
                        <?php if (!isLoggedIn()): ?>
                            <a href="<?= htmlspecialchars(pageUrl('login.php'), ENT_QUOTES, 'UTF-8') ?>"
                               class="btn-primary">
                                Iniciar sessão para inscrever
                            </a>

                        <?php elseif ($isEnrolled): ?>
                            <form method="POST" action="<?= htmlspecialchars(pageUrl('action_cancel.php'), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="class_id"   value="<?= $class['id'] ?>">
                                <button type="submit" class="btn-primary btn-cancel">Cancelar Inscrição</button>
                            </form>

                        <?php elseif ($isFull): ?>
                            <button class="btn-primary disabled" disabled>Aula Cheia</button>

                        <?php else: ?>
                            <form method="POST" action="<?= htmlspecialchars(pageUrl('action_book.php'), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="class_id"   value="<?= $class['id'] ?>">
                                <button type="submit" class="btn-primary">Inscrever</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>

        </section>
    </main>

<?php require INCLUDES_PATH . '/footer.php'; ?>