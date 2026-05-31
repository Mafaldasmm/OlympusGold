<?php

declare(strict_types=1);

/**
 * Expected variables before include:
 *   $pageTitle          string
 *   $extraStylesheets   string[]  paths under assets/css/ (optional)
 */

$pageTitle        ??= 'Olympus Gold Gym';
$extraStylesheets ??= [];
$bodyClass        ??= '';

?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/toast.css')) ?>">
<?php foreach ($extraStylesheets as $stylesheet): ?>
    <link rel="stylesheet" href="<?= e(asset('css/' . $stylesheet)) ?>">
<?php endforeach; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800&display=swap" rel="stylesheet">
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>

    <header class="navbar">
        <a href="<?= e(pageUrl('home.php')) ?>" class="logo" aria-label="Olympus Gold Gym — página inicial">OLYMPUS<span>GOLD</span></a>

        <button class="hamburger" id="hamburger" aria-label="Abrir menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
