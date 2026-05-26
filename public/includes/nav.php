<?php
 
declare(strict_types=1);
 
/**
 * Expected variable: $currentPage — one of: home, schedule, profile
 * Requires auth.php to be loaded for currentUser().
 */
 
$currentPage ??= '';
$navUser = function_exists('currentUser') ? currentUser() : null;
 
$navItems = [
    'home'     => ['label' => 'Home',     'file' => 'home.php'],
    'schedule' => ['label' => 'Horários', 'file' => 'schedule.php'],
    'profile'  => ['label' => 'Perfil',   'file' => 'profile.php'],
];
 
?>
        <ul class="nav-links" id="nav-links">
<?php foreach ($navItems as $key => $item):
    $isActive = $currentPage === $key;
    $href     = htmlspecialchars(pageUrl($item['file']), ENT_QUOTES, 'UTF-8');
    $label    = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
?>
            <li><a href="<?= $href ?>"<?= $isActive ? ' class="active"' : '' ?>><?= $label ?></a></li>
<?php endforeach; ?>
 
<?php if ($navUser && $navUser['role'] === 'trainer'): ?>
            <li><a href="<?= htmlspecialchars(pageUrl('trainer_dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"<?= $currentPage === 'trainer' ? ' class="active"' : '' ?>>Dashboard</a></li>
<?php endif; ?>
 
<?php if ($navUser && $navUser['role'] === 'admin'): ?>
            <li><a href="<?= htmlspecialchars(pageUrl('admin_classes.php'), ENT_QUOTES, 'UTF-8') ?>"<?= $currentPage === 'admin' ? ' class="active"' : '' ?>>Admin</a></li>
<?php endif; ?>
 
<?php if ($navUser): ?>
            <li><a href="<?= htmlspecialchars(pageUrl('logout.php'), ENT_QUOTES, 'UTF-8') ?>">Sair</a></li>
<?php else: ?>
            <li><a href="<?= htmlspecialchars(pageUrl('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn-cta-small">Entrar</a></li>
<?php endif; ?>
        </ul>
    </header>
 
