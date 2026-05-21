<?php

declare(strict_types=1);

/**
 * Bootstrap for public pages: path helpers and include directory.
 */

define('PUBLIC_ROOT', dirname(__DIR__));
define('INCLUDES_PATH', __DIR__);

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

