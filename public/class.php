<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/reviews.php';

// Validar o id da aula
$classId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($classId <= 0) {
    renderErrorPage(404, 'Aula não encontrada', 'O identificador da aula é inválido.');
}

// Ir buscar a aula
$class = dbQuery(
    'SELECT c.*, u.name AS trainer_name
     FROM classes c
     LEFT JOIN users u ON u.id = c.trainer_id
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

if ($user !== null) {
    $userId = (int) $user['id'];

    $hasUserReview = userHasClassReview($userId, $classId);
    $isEnrolled = userIsEnrolledInClass($userId, $classId);
    $canLeaveReview = userCanLeaveReviews($userId);
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
                <li><strong>Treinador:</strong> <?= e($class['trainer_name'] ?? 'N/D') ?></li>
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

        <!-- Flash messages -->
        <?php if ($flashSuccess !== ''): ?>
            <p class="flash flash-success"><?= e($flashSuccess) ?></p>
        <?php endif; ?>
        <?php if ($flashError !== ''): ?>
            <p class="flash flash-error"><?= e($flashError) ?></p>
        <?php endif; ?>

        <!-- Formulário de review -->
        <?php if ($user !== null && $isEnrolled && !$hasUserReview && $canLeaveReview): ?>
        <section class="review-form-section card">
            <h2>Deixar Avaliação</h2>
            <form method="POST" action="<?= e(pageUrl('action_review.php')) ?>">
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

<?php require INCLUDES_PATH . '/footer.php'; ?>
