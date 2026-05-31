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

        <?php if (empty($classes)): ?>
            <p class="empty-schedule">Nenhuma aula encontrada para os filtros selecionados.</p>
        <?php else: ?>
        <div class="schedule-list-wrap">
            <div id="schedule-feedback"
                 class="schedule-feedback-slot<?= $hasScheduleFlash ? ' is-visible' : '' ?>"
                 aria-live="polite">
<?php if ($flashSuccess !== ''): ?>
                <p class="flash flash-success"><?= e($flashSuccess) ?></p>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
                <p class="flash flash-error"><?= e($flashError) ?></p>
<?php endif; ?>
            </div>

        <div class="card-grid">
            <?php foreach ($classes as $class):
                $spots      = (int) $class['capacity'] - (int) $class['enrolled_count'];
                $isFull     = $spots <= 0;
                $isEnrolled = in_array($class['id'], $myEnrollments, true);
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
                        <p><strong>Treinador:</strong> <?= e($class['trainer_name'] ?? 'N/D') ?></p>
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
                                    <button type="submit" class="btn-primary btn-cancel">Cancelar Inscrição</button>
                                </form>
                            <?php elseif ($isFull): ?>
                                <button type="button" class="btn-primary disabled" disabled>Aula Cheia</button>
                            <?php else: ?>
                                <form class="enrollment-form"
                                      method="POST"
                                      action="<?= e(pageUrl('action_book.php')) ?>"
                                      data-enrollment-action="book">
                                    <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
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

<?php if ($isScheduleMember): ?>
<script>
(function () {
    const bookUrl = <?= json_encode(pageUrl('action_book.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const cancelUrl = <?= json_encode(pageUrl('action_cancel.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const feedback = document.getElementById('schedule-feedback');
    const AUTO_DISMISS_MS = 2000;
    let dismissTimer = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showFeedback(type, message) {
        if (!feedback) {
            return;
        }

        if (dismissTimer) {
            clearTimeout(dismissTimer);
            dismissTimer = null;
        }

        feedback.innerHTML = '<p class="flash flash-' + type + '">' + escapeHtml(message) + '</p>';
        feedback.classList.add('is-visible');

        dismissTimer = setTimeout(function () {
            feedback.innerHTML = '';
            feedback.classList.remove('is-visible');
            dismissTimer = null;
        }, AUTO_DISMISS_MS);
    }

    function scheduleDismissExisting() {
        if (!feedback || !feedback.querySelector('.flash-success, .flash-error')) {
            return;
        }

        feedback.classList.add('is-visible');
        dismissTimer = setTimeout(function () {
            feedback.innerHTML = '';
            feedback.classList.remove('is-visible');
            dismissTimer = null;
        }, AUTO_DISMISS_MS);
    }

    function updateAllCsrfTokens(token) {
        if (!token) {
            return;
        }
        if (typeof window.refreshCsrfToken === 'function') {
            window.refreshCsrfToken(token);
            return;
        }
        document.querySelectorAll('.enrollment-csrf').forEach(function (input) {
            input.value = token;
        });
    }

    function buildEnrollmentHtml(state, csrfToken) {
        const classId = state.classId;
        const csrf = escapeHtml(csrfToken);

        if (state.isEnrolled) {
            return '<form class="enrollment-form" method="POST" action="' + escapeHtml(cancelUrl) + '" data-enrollment-action="cancel">' +
                '<input type="hidden" name="csrf_token" class="enrollment-csrf" value="' + csrf + '">' +
                '<input type="hidden" name="class_id" value="' + classId + '">' +
                '<button type="submit" class="btn-primary btn-cancel">Cancelar Inscrição</button>' +
                '</form>';
        }

        if (state.isFull) {
            return '<button type="button" class="btn-primary disabled" disabled>Aula Cheia</button>';
        }

        return '<form class="enrollment-form" method="POST" action="' + escapeHtml(bookUrl) + '" data-enrollment-action="book">' +
            '<input type="hidden" name="csrf_token" class="enrollment-csrf" value="' + csrf + '">' +
            '<input type="hidden" name="class_id" value="' + classId + '">' +
            '<button type="submit" class="btn-primary">Inscrever</button>' +
            '</form>';
    }

    function updateSpots(card, state) {
        const spotsValue = card.querySelector('.class-spots-value');
        if (!spotsValue) {
            return;
        }

        if (state.isFull) {
            spotsValue.innerHTML = '<span class="full">CHEIO</span>';
        } else {
            spotsValue.textContent = state.spots + ' / ' + state.capacity;
        }
    }

    function updateCard(card, state, csrfToken) {
        card.dataset.capacity = String(state.capacity);
        card.dataset.enrolled = String(state.enrolledCount);

        updateSpots(card, state);

        const actions = card.querySelector('.class-enrollment-actions');
        if (actions) {
            actions.innerHTML = buildEnrollmentHtml(state, csrfToken);
            bindEnrollmentForms(actions);
        }
    }

    let submitting = false;

    async function handleEnrollmentSubmit(event) {
        event.preventDefault();

        if (submitting) {
            return;
        }

        const form = event.currentTarget;
        const card = form.closest('.class-card');
        if (!card) {
            return;
        }

        submitting = true;
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                showFeedback('error', 'Resposta inesperada do servidor. Recarrega a página e tenta novamente.');
                return;
            }

            const payload = await response.json();

            if (payload.csrfToken) {
                updateAllCsrfTokens(payload.csrfToken);
            }

            if (!response.ok || !payload.success) {
                showFeedback('error', payload.message || 'Não foi possível concluir a operação.');
                if (
                    payload.classId
                    && card.dataset.classId === String(payload.classId)
                    && typeof payload.capacity !== 'undefined'
                ) {
                    updateCard(card, payload, payload.csrfToken || '');
                }
                return;
            }

            showFeedback('success', payload.message);
            updateCard(card, payload, payload.csrfToken || '');
        } catch (error) {
            showFeedback('error', 'Erro de ligação. Verifica a rede e tenta novamente.');
        } finally {
            submitting = false;
            if (button) {
                button.disabled = false;
            }
        }
    }

    function bindEnrollmentForms(root) {
        root.querySelectorAll('.enrollment-form').forEach(function (form) {
            form.addEventListener('submit', handleEnrollmentSubmit);
        });
    }

    document.querySelectorAll('.class-enrollment-actions').forEach(bindEnrollmentForms);
    scheduleDismissExisting();
})();
</script>
<?php endif; ?>

<?php require INCLUDES_PATH . '/footer.php'; ?>