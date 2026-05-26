<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

requireGuest();

$errors = getFlash('form_errors', []);
$old = getFlash('form_old', []);
$success = getFlash('auth_success', '');
$authError = getFlash('auth_error', '');

$currentPage = 'login';
$pageTitle = 'Login | Olympus Gold Gym';
$extraStylesheets = ['pages/login.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="auth-main">
    <div class="auth-card">
        <div class="auth-logo">OLYMPUS<span>GOLD</span></div>
        <h1>Entrar</h1>
        <p class="auth-sub">Acede à tua conta com segurança.</p>

<?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <p><?= e($success) ?></p>
        </div>
<?php endif; ?>

<?php if ($authError !== ''): ?>
        <div class="alert alert-error">
            <p><?= e($authError) ?></p>
        </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
        <div class="alert alert-error">
<?php foreach ($errors as $error): ?>
            <p><?= e((string) $error) ?></p>
<?php endforeach; ?>
        </div>
<?php endif; ?>

        <form method="POST" action="<?= e(pageUrl('actions/login.php')) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="input-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    autocomplete="username"
                    required
                    value="<?= e((string) ($old['username'] ?? '')) ?>"
                >
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn-primary">Entrar</button>
        </form>

        <p class="auth-link">
            Ainda não tens conta?
            <a href="<?= e(pageUrl('register.php')) ?>">Regista-te</a>
        </p>
    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
