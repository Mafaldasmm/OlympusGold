<?php

declare(strict_types=1);

/**
 * Expected variable: $currentPage — one of: home, schedule, profile, login, register
 */

require_once __DIR__ . '/auth.php';

$currentPage ??= '';
$user = currentUser();

$navItems = [
    'home'     => ['label' => 'Home',     'file' => 'home.php'],
    'schedule' => ['label' => 'Horários', 'file' => 'schedule.php'],
];

if ($user !== null) {
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
<?php if ($user !== null): ?>
            <li><span class="nav-label">Olá, <?= e((string) ($user['name'] ?: $user['username'])) ?></span></li>
            <li>
                <form class="nav-form" method="POST" action="<?= e(pageUrl('actions/logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button type="submit" class="nav-button">Logout</button>
                </form>
            </li>
<?php endif; ?>
        </ul>
    </header>
