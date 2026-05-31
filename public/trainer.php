<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/trainer.php';

requireLogin();

$trainerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($trainerId <= 0) {
    renderErrorPage(404, 'Treinador não encontrado', 'O identificador do treinador é inválido.');
}

$trainer = fetchTrainerById($trainerId);

if ($trainer === null) {
    renderErrorPage(404, 'Treinador não encontrado', 'Este treinador não existe ou não está disponível.');
}

$specializations  = fetchTrainerSpecializations($trainerId);
$certifications   = fetchTrainerCertifications($trainerId);
$classes          = fetchTrainerClasses($trainerId);
$currentUser      = currentUser();
$canEditPublicProfile = $currentUser !== null
    && userCanManageTrainerProfile($currentUser, $trainerId);

$displayName = userDisplayName($trainer);
$photoUrl    = profilePhotoUrl($trainer);
$bio         = trim((string) ($trainer['bio'] ?? ''));

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
$pageTitle        = e($displayName) . ' | Treinador | Olympus Gold Gym';
$extraStylesheets = ['pages/trainer-profile.css', 'pages/admin.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="trainer-profile-page">
    <section class="trainer-profile-hero">
        <div class="trainer-profile-hero__inner">
            <img src="<?= e($photoUrl) ?>"
                 alt="Foto de <?= e($displayName) ?>"
                 class="trainer-profile-photo"
                 width="160"
                 height="160">
            <div class="trainer-profile-hero__text">
                <p class="trainer-profile-eyebrow">Treinador</p>
                <h1><?= e($displayName) ?></h1>
            </div>
        </div>
    </section>

    <div class="trainer-profile-body">
        <section class="card trainer-profile-section">
            <h2>Biografia</h2>
            <?php if ($bio !== ''): ?>
                <p class="trainer-profile-bio"><?= e($bio) ?></p>
            <?php else: ?>
                <p class="trainer-profile-empty">Ainda não existe biografia disponível para este treinador.</p>
            <?php endif; ?>
        </section>

        <section class="card trainer-profile-section">
            <h2>Especializações</h2>
            <?php if ($specializations !== []): ?>
                <ul class="trainer-spec-list">
                    <?php foreach ($specializations as $spec): ?>
                        <li class="trainer-spec-tag"><?= e((string) $spec['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="trainer-profile-empty">Este treinador ainda não tem especializações registadas.</p>
            <?php endif; ?>
        </section>

        <section class="card trainer-profile-section">
            <h2>Certificações</h2>
            <?php if ($certifications !== []): ?>
                <ul class="trainer-spec-list">
                    <?php foreach ($certifications as $cert): ?>
                        <li class="trainer-spec-tag trainer-cert-tag"><?= e((string) $cert['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="trainer-profile-empty">Este treinador ainda não tem certificações registadas.</p>
            <?php endif; ?>
        </section>

        <?php if ($canEditPublicProfile): ?>
            <p class="trainer-profile-edit-link">
                <a href="<?= e(pageUrl('trainer_manage_profile.php?trainer_id=' . $trainerId)) ?>"
                   class="btn-admin-primary">
                    Editar perfil público
                </a>
            </p>
        <?php endif; ?>

        <section class="card trainer-profile-section">
            <h2>Aulas</h2>
            <?php if ($classes !== []): ?>
                <ul class="trainer-class-list">
                    <?php foreach ($classes as $class): ?>
                        <?php
                        $dayLabel  = $dayLabels[$class['day_of_week']] ?? (string) $class['day_of_week'];
                        $typeLabel = $typeLabels[$class['type']] ?? (string) ($class['type'] ?? '');
                        ?>
                        <li class="trainer-class-item">
                            <div class="trainer-class-item__main">
                                <span class="tag <?= e((string) ($class['type'] ?? 'other')) ?>"><?= e($typeLabel) ?></span>
                                <h3><?= e((string) $class['title']) ?></h3>
                                <p class="trainer-class-schedule">
                                    <?= e($dayLabel) ?> · <?= e((string) $class['start_time']) ?> – <?= e((string) $class['end_time']) ?>
                                </p>
                            </div>
                            <a href="<?= e(pageUrl('class.php?id=' . (int) $class['id'])) ?>"
                               class="btn-secondary trainer-class-link">
                                Ver aula
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="trainer-profile-empty">Este treinador ainda não tem aulas atribuídas.</p>
            <?php endif; ?>
        </section>

        <p class="trainer-profile-back">
            <a href="<?= e(pageUrl('schedule.php')) ?>" class="btn-secondary">← Voltar ao horário</a>
        </p>
    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
