<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

$trainer = requireRole('trainer');

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
                <p class="trainer-sub">Painel de gestão — escolhe uma área abaixo</p>
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
                <h2>Analytics</h2>
                <p>Inscrições, ocupação, avaliações, lista de espera e membros inscritos por aula.</p>
            </div>
            <a href="<?= e(pageUrl('trainer_analytics.php')) ?>" class="trainer-dashboard-btn">
                Ver analytics
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

    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
