<?php

declare(strict_types=1);

/**
 * Bootstrap for public pages: path helpers and include directory.
 */

define('PUBLIC_ROOT', dirname(__DIR__));
define('INCLUDES_PATH', __DIR__);

/**
 * Escapes text for safe HTML output.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * String length safe when the mbstring extension is not loaded.
 */
function utf8Length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

/**
 * UTF-8 safe substring when mbstring is unavailable.
 */
function utf8Substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null
            ? mb_substr($value, $start)
            : mb_substr($value, $start, $length);
    }

    return $length === null
        ? substr($value, $start)
        : substr($value, $start, $length);
}

function utf8Strtoupper(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
}

/**
 * Relative URL prefix from the executing script back to public/.
 */
function publicBasePath(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $publicRoot = realpath(PUBLIC_ROOT);
    $scriptDir  = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? PUBLIC_ROOT));

    if ($publicRoot === false || $scriptDir === false || $scriptDir === $publicRoot) {
        $base = '';
        return $base;
    }

    if (!str_starts_with($scriptDir, $publicRoot)) {
        $base = '';
        return $base;
    }

    $relative = trim(str_replace('\\', '/', substr($scriptDir, strlen($publicRoot))), '/');

    if ($relative === '') {
        $base = '';
        return $base;
    }

    $depth = substr_count($relative, '/') + 1;
    $base  = str_repeat('../', $depth);

    return $base;
}

/** Asset path relative to the current page (CSS, JS, images). */
function asset(string $path): string
{
    return publicBasePath() . 'assets/' . ltrim($path, '/');
}

/** Link to another page under public/. */
function pageUrl(string $page): string
{
    return publicBasePath() . ltrim($page, '/');
}

/**
 * Renders a minimal error page and stops execution.
 */
function renderErrorPage(int $statusCode, string $title, string $message): never
{
    http_response_code($statusCode);

    ?><!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
</head>
<body>
    <main class="profile-container" style="min-height: calc(100vh - 80px); display: flex; align-items: center; justify-content: center; padding: 2rem;">
        <section class="card" style="max-width: 42rem; width: 100%;">
            <h1><?= e($title) ?></h1>
            <p><?= e($message) ?></p>
            <p><a class="btn-primary" href="<?= e(pageUrl('home.php')) ?>" style="display: inline-block; width: auto;">Voltar ao site</a></p>
        </section>
    </main>
</body>
</html>
<?php
    exit;
}

