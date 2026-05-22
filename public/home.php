<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

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
