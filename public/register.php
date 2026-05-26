<?php
 
declare(strict_types=1);
 
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
 
if (currentUser()) {
    redirect(pageUrl('home.php'));
}
 
$errors  = [];
$success = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
 
    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';
 
    if ($name     === '') $errors[] = 'O nome é obrigatório.';
    if ($username === '') $errors[] = 'O username é obrigatório.';
    if ($email    === '') $errors[] = 'O email é obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (strlen($password) < 6) $errors[] = 'A password deve ter pelo menos 6 caracteres.';
    if ($password !== $confirm) $errors[] = 'As passwords não coincidem.';
 
    if (empty($errors)) {
        $dup = dbQuery(
            'SELECT id FROM users WHERE username = :u OR email = :e',
            ['u' => $username, 'e' => $email]
        );
        if ($dup->fetch()) {
            $errors[] = 'Já existe uma conta com esse username ou email.';
        }
    }
 
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        dbQuery(
            'INSERT INTO users (username, password, email, name, role) VALUES (:u, :p, :e, :n, :r)',
            ['u' => $username, 'p' => $hash, 'e' => $email, 'n' => $name, 'r' => 'member']
        );
        $success = 'Conta criada! Podes fazer login agora.';
    }
}
 
$currentPage      = '';
$pageTitle        = 'Registar | Olympus Gold Gym';
$extraStylesheets = ['pages/login.css'];
 
require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>
 
<main class="auth-main">
    <div class="auth-card">
        <div class="auth-logo">OLYMPUS<span>GOLD</span></div>
        <h1>Criar Conta</h1>
        <p class="auth-sub">Junta-te ao Olympus Gold</p>
 
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= e($success) ?>
                <a href="<?= e(pageUrl('login.php')) ?>">Fazer login →</a>
            </div>
        <?php endif; ?>
 
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
 
        <form method="POST" action="<?= e(pageUrl('register.php')) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
 
            <div class="input-group">
                <label for="name">Nome Completo</label>
                <input type="text" id="name" name="name" required
                       value="<?= e($_POST['name'] ?? '') ?>">
            </div>
 
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       autocomplete="username"
                       value="<?= e($_POST['username'] ?? '') ?>">
            </div>
 
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
 
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
 
            <div class="input-group">
                <label for="confirm">Confirmar Password</label>
                <input type="password" id="confirm" name="confirm" required autocomplete="new-password">
            </div>
 
            <button type="submit" class="btn-primary">Registar</button>
        </form>
 
        <p class="auth-link">Já tens conta? <a href="<?= e(pageUrl('login.php')) ?>">Fazer login</a></p>
    </div>
</main>
 
<?php require INCLUDES_PATH . '/footer.php'; ?>
 
