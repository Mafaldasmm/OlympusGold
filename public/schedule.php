<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$currentPage        = 'schedule';
$pageTitle          = 'Horário de Aulas - Olympus Gold Gym';
$extraStylesheets   = ['pages/schedule.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
    <main>
        <section class="schedule-section">
            <div class="schedule-header">
                <h2><span class="highlight">HORÁRIOS</span> DAS AULAS </h2>
                <p>Filtre por modalidade, treinador ou dia para encontrar o seu treino ideal.</p>

                <form class="filters-bar">
                    <select name="type" aria-label="Filtrar por Modalidade">
                        <option value="">Todas as Modalidades</option>
                        <option value="yoga">Yoga</option>
                        <option value="pilates">Pilates</option>
                        <option value="zumba">Zumba</option>
                        <option value="hiit">HIIT</option>
                    </select>

                    <select name="day" aria-label="Filtrar por Dia">
                        <option value="">Qualquer Dia</option>
                        <option value="monday">Segunda-feira</option>
                        <option value="tuesday">Terça-feira</option>
                        <option value="wednesday">Quarta-feira</option>
                        <option value="thursday">Quinta-feira</option>
                        <option value="friday">Sexta-feira</option>
                        <option value="saturday">Sábado</option>
                    </select>

                    <select name="trainer" aria-label="Filtrar por Treinador">
                        <option value="">Todos os Treinadores</option>
                        <option value="ana">Ana Silva</option>
                        <option value="sofia">Sofia Costa</option>
                        <option value="marco">Marco Rebelo</option>
                        <option value="ricardo">Ricardo Pereira</option>
                    </select>

                    <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">Filtrar</button>
                </form>
            </div>

            <div class="card-grid">

                <article class="card">
                    <div class="card-header">
                        <span class="tag yoga">Yoga</span>
                        <h3>Yoga Matinal</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> Ana Silva</p>
                        <p><strong>Horário:</strong> Segunda | 08:00 - 09:00</p>
                        <p><strong>Vagas:</strong> 5 / 15</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-primary">Inscrever</button>
                    </div>
                </article>

                <article class="card">
                    <div class="card-header">
                        <span class="tag pilates">Pilates</span>
                        <h3>Pilates Core</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> Sofia Costa</p>
                        <p><strong>Horário:</strong> Segunda | 10:30 - 11:30</p>
                        <p><strong>Vagas:</strong> 8 / 12</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-primary">Inscrever</button>
                    </div>
                </article>

                <article class="card">
                    <div class="card-header">
                        <span class="tag zumba">Zumba</span>
                        <h3>Zumba Party</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> Marco Rebelo</p>
                        <p><strong>Horário:</strong> Terça | 19:00 - 20:00</p>
                        <p><strong>Vagas:</strong> 25 / 30</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-primary">Inscrever</button>
                    </div>
                </article>

                <article class="card">
                    <div class="card-header">
                        <span class="tag hiit">HIIT</span>
                        <h3>HIIT Intenso</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> Ricardo Pereira</p>
                        <p><strong>Horário:</strong> Quarta | 18:30 - 19:15</p>
                        <p><strong>Vagas:</strong> <span class="full">CHEIO</span></p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-primary disabled" disabled aria-label="Aula cheia, entrar em lista de espera">Lista de Espera</button>
                    </div>
                </article>

                <article class="card">
                    <div class="card-header">
                        <span class="tag pilates">Pilates</span>
                        <h3>Pilates Sénior</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> Sofia Costa</p>
                        <p><strong>Horário:</strong> Quinta | 09:00 - 10:00</p>
                        <p><strong>Vagas:</strong> 2 / 10</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-primary">Inscrever</button>
                    </div>
                </article>

                <article class="card">
                    <div class="card-header">
                        <span class="tag zumba">Zumba</span>
                        <h3>Zumba Noite</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> Marco Rebelo</p>
                        <p><strong>Horário:</strong> Sexta | 20:30 - 21:30</p>
                        <p><strong>Vagas:</strong> 10 / 30</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-primary">Inscrever</button>
                    </div>
                </article>

                <article class="card">
                    <div class="card-header">
                        <span class="tag yoga">Yoga</span>
                        <h3>Yoga Sunset</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Treinador:</strong> Ana Silva</p>
                        <p><strong>Horário:</strong> Sexta | 18:00 - 19:00</p>
                        <p><strong>Vagas:</strong> 14 / 15</p>
                    </div>
                    <div class="card-footer">
                        <button class="btn-primary">Inscrever</button>
                    </div>
                </article>

            </div>
        </section>
    </main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
