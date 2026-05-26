<?php
 
declare(strict_types=1);
 
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
 
// Already logged in → redirect
if (currentUser()) {
    redirect(pageUrl('home.php'));
}
 
$errors  = [];
$success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
 
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
 
    if ($username === '') $errors[] = 'O username é obrigatório.';
    if ($password === '') $errors[] = 'A password é obrigatória.';
 
    if (empty($errors)) {
        $stmt = dbQuery(
            'SELECT id, username, name, password, role, active FROM users WHERE username = :u',
            ['u' => $username]
        );
        $user = $stmt->fetch();
 
        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Username ou password incorretos.';
        } elseif (!$user['active']) {
            $errors[] = 'A tua conta está desativada. Contacta um administrador.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
 
            // Redirect admins to admin panel, others to home
            if ($user['role'] === 'admin') {
                redirect(pageUrl('admin_classes.php'));
            }
            redirect(pageUrl('home.php'));
        }
    }
}
 
$currentPage      = '';
$pageTitle        = 'Login | Olympus Gold Gym';
$extraStylesheets = ['pages/login.css'];
 

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>
 
<main class="auth-main">
    <div class="auth-card">
        <div class="auth-logo">OLYMPUS<span>GOLD</span></div>
        <h1>Entrar</h1>
        <p class="auth-sub">Acesso à tua conta</p>
 
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
 
        <form method="POST" action="<?= e(pageUrl('login.php')) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
 
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       autocomplete="username"
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>
 
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       autocomplete="current-password">
            </div>
 
            <button type="submit" class="btn-primary">Entrar</button>
        </form>
 
        <p class="auth-link">Não tens conta? <a href="<?= e(pageUrl('register.php')) ?>">Regista-te</a></p>
    </div>
</main>
 
<?php require INCLUDES_PATH . '/footer.php'; ?>
 
