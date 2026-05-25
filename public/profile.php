<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

$user = requireLogin();

$currentPage        = 'profile';
$pageTitle          = 'Perfil do Utilizador - Olympus Gold Gym';
$extraStylesheets   = ['pages/profile.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
    <main class="profile-container">
        <div class="profile-header">
            <h1>EDITAR <span class="highlight">PERFIL</span></h1>
            <p>Gira as suas informações pessoais.</p>
        </div>

        <form action="<?= htmlspecialchars(pageUrl('update_profile.php'), ENT_QUOTES, 'UTF-8') ?>"
              method="POST"
              enctype="multipart/form-data">

            <div class="card-grid">

                <article class="card featured">
                    <h2>Foto de Perfil</h2>
                    <div class="profile-photo-section">
                        <img src="<?= htmlspecialchars(pageUrl('default-profile.png'), ENT_QUOTES, 'UTF-8') ?>"
                             alt="Foto de perfil"
                             class="profile-img">
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                    </div>
                </article>

                <article class="card featured">
                    <h2>Dados Pessoais</h2>
                    <div class="input-group">
                        <label for="name">Nome</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Ex: André Restivo"
                            required
                            value="<?= e((string) ($user['name'] ?? '')) ?>"
                        >
                    </div>
                    <div class="input-group">
                        <label for="username">Nome de Utilizador</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="utilizador123"
                            required
                            value="<?= e((string) $user['username']) ?>"
                        >
                    </div>
                </article>

                <article class="card featured">
                    <h2>Segurança</h2>
                    <div class="input-group">
                        <label for="password">Nova Palavra Passe</label>
                        <input type="password" id="password" name="password" placeholder="Introduza para alterar">
                        <small>Deixe em branco caso não deseje alterar.</small>
                    </div>
                </article>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">Guardar Alterações</button>
            </div>

        </form>
    </main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
