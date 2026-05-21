<?php

declare(strict_types=1);

/**
 * Expected variable: $currentPage — one of: home, schedule, profile
 */

$currentPage ??= '';

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
        </ul>
    </header>
