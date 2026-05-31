<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/reviews.php';
require_once INCLUDES_PATH . '/class_waitlist.php';
require_once INCLUDES_PATH . '/schedule_enrollment.php';

// Validar o id da aula
$classId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($classId <= 0) {
    renderErrorPage(404, 'Aula não encontrada', 'O identificador da aula é inválido.');
}

// Ir buscar a aula
$class = dbQuery(
    'SELECT c.*, u.name AS trainer_name, u.id AS trainer_user_id
     FROM classes c
     LEFT JOIN users u ON u.id = c.trainer_id AND u.role = \'trainer\' AND u.active = 1
     WHERE c.id = :id',
    ['id' => $classId]
)->fetch();

if (!$class) {
    renderErrorPage(404, 'Aula não encontrada', 'Esta aula não existe ou foi removida.');
}

// Número de inscritos
$enrolled = (int) dbQuery(
    'SELECT COUNT(*) FROM enrollments WHERE class_id = :cid',
    ['cid' => $classId]
)->fetchColumn();

$spots  = $class['capacity'] - $enrolled;
$isFull = $spots <= 0;

// Reviews da aula
$reviews = dbQuery(
    'SELECT r.*, u.name AS member_name
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     WHERE r.class_id = :cid
     ORDER BY r.created_at DESC',
    ['cid' => $classId]
)->fetchAll();

// Média de ratings
$avgRating = 0;
if (count($reviews) > 0) {
    $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
}

// Verificar se o utilizador já deixou review e se está inscrito
$hasUserReview = false;
$isEnrolled    = false;
$canLeaveReview = false;
$user          = currentUser();

$waitlistRows = annotateWaitlistPositions(fetchClassWaitlist($classId));
$canViewWaitlist = false;
$memberEnrollmentState = null;
$csrfToken = csrfToken();
$classRedirect = 'class.php?id=' . $classId;

if ($user !== null) {
    $userId = (int) $user['id'];

    $hasUserReview = userHasClassReview($userId, $classId);
    $isEnrolled = userIsEnrolledInClass($userId, $classId);
    $canLeaveReview = userCanLeaveReviews($userId);

    if ($user['role'] === 'member') {
        $memberEnrollmentState = classEnrollmentState($classId, $userId);
    }

    if ($user['role'] === 'admin') {
        $canViewWaitlist = true;
    } elseif ($user['role'] === 'trainer' && (int) ($class['trainer_id'] ?? 0) === $userId) {
        $canViewWaitlist = true;
    }
}

// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$dayLabels = [
    'monday'    => 'Segunda-feira',
    'tuesday'   => 'Terça-feira',
    'wednesday' => 'Quarta-feira',
    'thursday'  => 'Quinta-feira',
    'friday'    => 'Sexta-feira',
    'saturday'  => 'Sábado',
    'sunday'    => 'Domingo',
];

$currentPage      = 'schedule';
$pageTitle        = e($class['title']) . ' - Olympus Gold Gym';
$extraStylesheets = ['pages/schedule.css', 'pages/class.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="class-page">

    <!-- Cabeçalho da aula -->
    <section class="class-hero">
        <div class="class-hero-content">
            <span class="tag <?= e($class['type']) ?>"><?= e($class['type']) ?></span>
            <h1><?= e($class['title']) ?></h1>
            <p class="class-description"><?= e($class['description'] ?? '') ?></p>
        </div>
    </section>

    <div class="class-body">

        <!-- Detalhes -->
        <section class="class-details card">
            <h2>Detalhes</h2>
            <ul class="detail-list">
                <li><strong>Treinador:</strong>
                    <?php if (!empty($class['trainer_user_id'])): ?>
                        <a href="<?= e(pageUrl('trainer.php?id=' . (int) $class['trainer_user_id'])) ?>"
                           class="trainer-profile-link">
                            <?= e($class['trainer_name'] ?? 'N/D') ?>
                        </a>
                    <?php else: ?>
                        <?= e($class['trainer_name'] ?? 'N/D') ?>
                    <?php endif; ?>
                </li>
                <li><strong>Dia:</strong> <?= e($dayLabels[$class['day_of_week']] ?? $class['day_of_week']) ?></li>
                <li><strong>Horário:</strong> <?= e($class['start_time']) ?> – <?= e($class['end_time']) ?></li>
                <li><strong>Vagas:</strong>
                    <?php if ($isFull): ?>
                        <span class="full">CHEIO</span>
                    <?php else: ?>
                        <?= $spots ?> / <?= $class['capacity'] ?>
                    <?php endif; ?>
                </li>
                <?php if (count($reviews) > 0): ?>
                <li><strong>Avaliação média:</strong>
                    <span class="stars-display"><?= str_repeat('★', (int) round($avgRating)) ?><?= str_repeat('☆', 5 - (int) round($avgRating)) ?></span>
                    (<?= number_format($avgRating, 1) ?> / 5)
                </li>
                <?php endif; ?>
            </ul>

            <div class="class-actions">
                <a href="<?= e(pageUrl('schedule.php')) ?>" class="btn-secondary">← Voltar ao Horário</a>
            </div>
        </section>

        <?php if ($user !== null && $user['role'] === 'member' && $memberEnrollmentState !== null): ?>
        <section class="class-details card class-enrollment-section"
                 data-class-id="<?= $classId ?>"
                 data-capacity="<?= (int) $memberEnrollmentState['capacity'] ?>"
                 data-enrolled="<?= (int) $memberEnrollmentState['enrolledCount'] ?>">
            <h2>Inscrição</h2>
            <p class="class-enrollment-spots">
                <strong>Vagas:</strong>
                <span class="class-spots-value">
<?php if ($memberEnrollmentState['isFull']): ?>
                    <span class="full">CHEIO</span>
<?php else: ?>
                    <?= (int) $memberEnrollmentState['spots'] ?> / <?= (int) $memberEnrollmentState['capacity'] ?>
<?php endif; ?>
                </span>
            </p>
            <div class="class-enrollment-actions">
                <?php if ($memberEnrollmentState['isEnrolled']): ?>
                    <form class="enrollment-form"
                          method="POST"
                          action="<?= e(pageUrl('action_cancel.php')) ?>"
                          data-enrollment-action="cancel">
                        <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="class_id" value="<?= $classId ?>">
                        <input type="hidden" name="redirect" value="<?= e($classRedirect) ?>">
                        <button type="submit" class="btn-primary btn-cancel">Cancelar Inscrição</button>
                    </form>
                <?php elseif ($memberEnrollmentState['isOnWaitlist']): ?>
                    <p class="waitlist-status">
                        Está na lista de espera — posição <strong><?= (int) $memberEnrollmentState['waitlistPosition'] ?></strong>
                    </p>
                    <form class="enrollment-form"
                          method="POST"
                          action="<?= e(pageUrl('action_waitlist_leave.php')) ?>"
                          data-enrollment-action="waitlist-leave">
                        <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="class_id" value="<?= $classId ?>">
                        <input type="hidden" name="redirect" value="<?= e($classRedirect) ?>">
                        <button type="submit" class="btn-primary btn-cancel">Sair da lista de espera</button>
                    </form>
                <?php elseif ($memberEnrollmentState['isFull']): ?>
                    <form class="enrollment-form"
                          method="POST"
                          action="<?= e(pageUrl('action_waitlist_join.php')) ?>"
                          data-enrollment-action="waitlist-join">
                        <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="class_id" value="<?= $classId ?>">
                        <input type="hidden" name="redirect" value="<?= e($classRedirect) ?>">
                        <button type="submit" class="btn-primary btn-waitlist">Entrar na lista de espera</button>
                    </form>
                <?php else: ?>
                    <form class="enrollment-form"
                          method="POST"
                          action="<?= e(pageUrl('action_book.php')) ?>"
                          data-enrollment-action="book">
                        <input type="hidden" name="csrf_token" class="enrollment-csrf" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="class_id" value="<?= $classId ?>">
                        <input type="hidden" name="redirect" value="<?= e($classRedirect) ?>">
                        <button type="submit" class="btn-primary">Inscrever nesta aula</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($canViewWaitlist): ?>
        <section class="class-details card">
            <h2>Lista de espera <span class="badge-count"><?= count($waitlistRows) ?></span></h2>
            <?php if ($waitlistRows === []): ?>
                <p class="trainer-profile-empty">Ninguém na lista de espera.</p>
            <?php else: ?>
                <ol class="class-waitlist-admin-list">
                    <?php foreach ($waitlistRows as $entry): ?>
                        <li>
                            <span class="waitlist-position">#<?= (int) $entry['position'] ?></span>
                            <?= e((string) ($entry['member_name'] ?? $entry['username'])) ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Formulário de review -->
        <?php if ($user !== null && $isEnrolled && !$hasUserReview && $canLeaveReview): ?>
        <section class="review-form-section card">
            <h2>Deixar Avaliação</h2>
            <form id="class-review-form" method="POST" action="<?= e(pageUrl('action_review.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="class_id"   value="<?= $classId ?>">

                <div class="input-group">
                    <label for="rating">Classificação</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                            <label for="star<?= $i ?>" title="<?= $i ?> estrelas">★</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="input-group">
                    <label for="comment">Comentário <span class="optional">(opcional)</span></label>
                    <textarea id="comment" name="comment" maxlength="300" rows="3"
                              placeholder="Partilha a tua experiência (máx. 300 caracteres)…"></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width:auto;">Enviar Avaliação</button>
            </form>
        </section>

        <?php elseif ($user !== null && $isEnrolled && $hasUserReview): ?>
        <section class="review-form-section card">
            <p class="already-reviewed">✓ Já avaliaste esta aula. Obrigado pelo teu feedback!</p>
        </section>

        <?php elseif ($user !== null && $isEnrolled && !$hasUserReview && !$canLeaveReview): ?>
        <section class="review-form-section card">
            <p class="review-notice">Não tens permissão para deixar avaliações. Contacta um administrador se achares que isto é um erro.</p>
        </section>

        <?php elseif ($user !== null && !$isEnrolled): ?>
        <section class="review-form-section card">
            <p class="review-notice">Só podes avaliar aulas em que estejas inscrito.</p>
        </section>

        <?php elseif ($user === null): ?>
        <section class="review-form-section card">
            <p class="review-notice">
                <a href="<?= e(pageUrl('login.php')) ?>">Inicia sessão</a> para avaliar esta aula.
            </p>
        </section>
        <?php endif; ?>

        <!-- Lista de reviews -->
        <section class="reviews-section">
            <h2>Avaliações <span class="review-count">(<?= count($reviews) ?>)</span></h2>

            <?php if (count($reviews) === 0): ?>
                <p class="no-reviews">Ainda não há avaliações para esta aula. Sê o primeiro!</p>
            <?php else: ?>
                <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <article class="review-card card">
                        <div class="review-header">
                            <span class="reviewer-name"><?= e($review['member_name']) ?></span>
                            <span class="review-stars"><?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?></span>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                            <p class="review-comment"><?= e($review['comment']) ?></p>
                        <?php endif; ?>
                        <small class="review-date"><?= e(substr($review['created_at'], 0, 10)) ?></small>
                    </article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php if ($flashSuccess !== ''): ?>
    <span class="visually-hidden" data-toast-on-load data-toast-type="success"><?= e($flashSuccess) ?></span>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
    <span class="visually-hidden" data-toast-on-load data-toast-type="error"><?= e($flashError) ?></span>
<?php endif; ?>

<?php
$extraScripts = [];
if ($user !== null && $user['role'] === 'member' && $memberEnrollmentState !== null):
?>
<script>
window.__ENROLLMENT_CONFIG = {
    bookUrl: <?= json_encode(pageUrl('action_book.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    cancelUrl: <?= json_encode(pageUrl('action_cancel.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    waitlistJoinUrl: <?= json_encode(pageUrl('action_waitlist_join.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    waitlistLeaveUrl: <?= json_encode(pageUrl('action_waitlist_leave.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    redirect: <?= json_encode($classRedirect, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    bookButtonLabel: 'Inscrever nesta aula',
    cardSelector: '.class-enrollment-section',
    actionsSelector: '.class-enrollment-actions',
    spotsSelector: '.class-spots-value'
};
</script>
<?php
    $extraScripts[] = 'enrollment-actions.js';
endif;
if ($user !== null && $isEnrolled && !$hasUserReview && $canLeaveReview):
    $extraScripts[] = 'review-form.js';
endif;
?>

<?php require INCLUDES_PATH . '/footer.php'; ?>
