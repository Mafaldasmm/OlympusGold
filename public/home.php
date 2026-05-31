<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_once INCLUDES_PATH . '/promotions.php';

$featuredClasses  = [];
$featuredTrainers = [];
$promotionsError  = false;

try {
    $pdo              = getDatabase();
    $featuredClasses  = fetchFeaturedClassesForHomepage($pdo);
    $featuredTrainers = fetchFeaturedTrainersForHomepage($pdo);
} catch (Throwable) {
    $promotionsError = true;
}

$hasPromotions = !$promotionsError
    && ($featuredClasses !== [] || $featuredTrainers !== []);

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

$currentPage        = 'home';
$pageTitle          = 'Olympus Gold Gym | Excelência em Fitness';
$extraStylesheets   = ['pages/home.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>ELEVE O SEU <span class="highlight">POTENCIAL</span> AO MÁXIMO</h1>
            <p>Treino de elite, equipamentos de última geração e acompanhamento personalizado num ambiente exclusivo.</p>
            <div class="hero-btns">
                <a href="#planos" class="btn-primary">Ver Planos</a>
                <a href="#" class="btn-secondary">Aula Experimental</a>
            </div>
        </div>
    </section>

    <?php if ($promotionsError): ?>
        <section class="home-promotions home-promotions--muted container" aria-live="polite">
            <p class="home-promotions-fallback">Os destaques estão temporariamente indisponíveis.</p>
        </section>
    <?php elseif ($hasPromotions): ?>
        <section class="home-promotions" aria-labelledby="home-promotions-title">
            <div class="home-promotions__glow" aria-hidden="true"></div>
            <div class="container home-promotions__inner">
                <header class="home-promotions__header">
                    <p class="home-promotions__eyebrow">Escolhas da equipa</p>
                    <h2 id="home-promotions-title" class="home-promotions__title">
                        <span class="home-promotions__title-line">Vive a experiência <span class="highlight">Olympus Gold</span></span>
                    </h2>
                    <p class="home-promotions__lead">
                        Aulas e treinadores selecionados para te inspirar — reserva a tua próxima sessão ou conhece quem vai guiar o teu progresso.
                    </p>
                </header>

                <div class="home-promotions__showcase">
                    <?php if ($featuredClasses !== []): ?>
                        <div class="home-promotions__group">
                            <h3 class="home-promotions__group-label">Aulas em destaque</h3>
                            <div class="home-promotions__grid">
                                <?php foreach ($featuredClasses as $class): ?>
                                    <?php
                                    $classUrl  = pageUrl('class.php?id=' . (int) $class['id']);
                                    $typeKey   = (string) ($class['type'] ?? 'other');
                                    $visual    = promotionClassVisual($typeKey);
                                    $typeLabel = $typeLabels[$typeKey] ?? $typeKey;
                                    $dayLabel  = $dayLabels[$class['day_of_week'] ?? ''] ?? '';
                                    $desc      = trim((string) ($class['description'] ?? ''));
                                    $descShort = $desc !== '' ? promotionBioExcerpt($desc, 110) : 'Uma experiência pensada para resultados reais no nosso ginásio.';
                                    ?>
                                    <a href="<?= e($classUrl) ?>"
                                       class="home-promo-card home-promo-card--class home-promo-card--<?= e($visual['accent']) ?>">
                                        <div class="home-promo-card__visual">
                                            <span class="home-promo-card__icon" aria-hidden="true"><?= $visual['icon'] ?></span>
                                            <span class="home-promo-card__shine" aria-hidden="true"></span>
                                            <span class="home-promo-card__pill">Destaque</span>
                                        </div>
                                        <div class="home-promo-card__body">
                                            <span class="home-promo-card__category"><?= e($typeLabel) ?></span>
                                            <h4 class="home-promo-card__title"><?= e($class['title']) ?></h4>
                                            <p class="home-promo-card__text"><?= e($descShort) ?></p>
                                            <ul class="home-promo-card__chips">
                                                <?php if ($dayLabel !== ''): ?>
                                                    <li><?= e($dayLabel) ?><?= $class['start_time'] ? ' · ' . e($class['start_time']) : '' ?></li>
                                                <?php endif; ?>
                                                <?php if (!empty($class['trainer_name'])): ?>
                                                    <li>Com <?= e($class['trainer_name']) ?></li>
                                                <?php endif; ?>
                                            </ul>
                                            <span class="home-promo-card__cta">Explorar aula</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($featuredTrainers !== []): ?>
                        <div class="home-promotions__group">
                            <h3 class="home-promotions__group-label">Treinadores em destaque</h3>
                            <div class="home-promotions__grid home-promotions__grid--trainers">
                                <?php foreach ($featuredTrainers as $trainer): ?>
                                    <?php
                                    $trainerUrl  = pageUrl('trainer.php?id=' . (int) $trainer['id']);
                                    $displayName = trim((string) ($trainer['name'] ?? '')) !== ''
                                        ? (string) $trainer['name']
                                        : (string) $trainer['username'];
                                    $bioExcerpt  = promotionBioExcerpt((string) ($trainer['bio'] ?? ''), 120);
                                    $initials    = promotionTrainerInitials($displayName, (string) $trainer['username']);
                                    ?>
                                    <a href="<?= e($trainerUrl) ?>" class="home-promo-card home-promo-card--trainer">
                                        <div class="home-promo-card__visual home-promo-card__visual--trainer">
                                            <span class="home-promo-card__avatar" aria-hidden="true"><?= e($initials) ?></span>
                                            <span class="home-promo-card__shine" aria-hidden="true"></span>
                                            <span class="home-promo-card__pill">Destaque</span>
                                        </div>
                                        <div class="home-promo-card__body">
                                            <span class="home-promo-card__category">Treinador</span>
                                            <h4 class="home-promo-card__title"><?= e($displayName) ?></h4>
                                            <p class="home-promo-card__text"><?= e($bioExcerpt) ?></p>
                                            <span class="home-promo-card__cta">Conhecer perfil</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section id="modalidades" class="features">
        <div class="container">
            <h2 class="section-title">As Nossas Modalidades</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="icon">🏋️</div>
                    <h3>Musculação</h3>
                    <p>Equipamentos premium para hipertrofia e força máxima.</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🥊</div>
                    <h3>Boxe & Artes Marciais</h3>
                    <p>Liberte o stress e melhore a sua agilidade e reflexos.</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🧘</div>
                    <h3>Yoga & Pilates</h3>
                    <p>Equilíbrio entre corpo e mente num ambiente sereno.</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🚴</div>
                    <h3>Cycling</h3>
                    <p>Sessões de alta intensidade com os melhores instrutores.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="planos" class="pricing">
        <div class="container">
            <h2 class="section-title">Escolha o seu Plano</h2>
            <div class="price-grid">
                <div class="price-card">
                    <h3>Silver</h3>
                    <div class="price">29€<span>/mês</span></div>
                    <ul>
                        <li>Acesso ao Ginásio (Horário Limitado)</li>
                        <li>Balneários Premium</li>
                        <li>Avaliação Inicial</li>
                    </ul>
                    <a href="#" class="btn-outline">Selecionar</a>
                </div>
                <div class="price-card featured">
                    <div class="badge">Recomendado</div>
                    <h3>Gold</h3>
                    <div class="price">49€<span>/mês</span></div>
                    <ul>
                        <li>Acesso Livre 24/7</li>
                        <li>Todas as Aulas de Grupo</li>
                        <li>Plano de Treino Mensal</li>
                        <li>Acesso à Sauna</li>
                    </ul>
                    <a href="#" class="btn-primary">Selecionar</a>
                </div>
                <div class="price-card">
                    <h3>Platinum</h3>
                    <div class="price">89€<span>/mês</span></div>
                    <ul>
                        <li>Tudo do Plano Gold</li>
                        <li>Personal Trainer (2x semana)</li>
                        <li>Nutricionista Incluído</li>
                        <li>Toalhas e Suplementação</li>
                    </ul>
                    <a href="#" class="btn-outline">Selecionar</a>
                </div>
            </div>
        </div>
    </section>

<?php require INCLUDES_PATH . '/footer.php'; ?>
