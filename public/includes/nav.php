<?php

declare(strict_types=1);

/**
 * Expected variable: $currentPage — one of: home, schedule, equipment, profile, login, register, admin, trainer
 */

require_once __DIR__ . '/auth.php';

$currentPage ??= '';
$user = currentUser();

$navItems = [
    'home'     => ['label' => 'Home',     'file' => 'home.php'],
    'schedule' => ['label' => 'Horários', 'file' => 'schedule.php'],
];

if ($user !== null) {
    $navItems['equipment'] = ['label' => 'Equipamentos', 'file' => 'equipment.php'];
    $navItems['profile'] = ['label' => 'Perfil', 'file' => 'profile.php'];
} else {
    $navItems['login'] = ['label' => 'Login', 'file' => 'login.php'];
    $navItems['register'] = ['label' => 'Registar', 'file' => 'register.php'];
}

?>
        <ul class="nav-links" id="nav-links">
<?php foreach ($navItems as $key => $item):
    $isActive = $currentPage === $key;
    $href     = e(pageUrl($item['file']));
    $label    = e($item['label']);
?>
            <li><a href="<?= $href ?>"<?= $isActive ? ' class="active"' : '' ?>><?= $label ?></a></li>
<?php endforeach; ?>

<?php if ($user !== null && $user['role'] === 'trainer'): ?>
            <li><a href="<?= e(pageUrl('trainer_dashboard.php')) ?>"<?= $currentPage === 'trainer' ? ' class="active"' : '' ?>>Dashboard</a></li>
<?php endif; ?>

<?php if ($user !== null && $user['role'] === 'admin'): ?>
            <li><a href="<?= e(pageUrl('admin_classes.php')) ?>"<?= $currentPage === 'admin' ? ' class="active"' : '' ?>>Admin</a></li>
<?php endif; ?>

<?php if ($user !== null):
    $navCsrfToken = csrfToken();
?>
            <li><span class="nav-label" id="nav-user-greeting">Olá, <?= e(userDisplayName($user)) ?></span></li>
            <li>
                <form class="nav-form" method="POST" action="<?= e(pageUrl('actions/logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($navCsrfToken) ?>">
                    <button type="submit" class="nav-button">Logout</button>
                </form>
            </li>
            <script>window.__CSRF_TOKEN = <?= json_encode($navCsrfToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
<?php endif; ?>
        </ul>
    </header>