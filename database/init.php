<?php

declare(strict_types=1);

/**
 * Database initialization script (CLI).
 *
 * Creates database/olympus.sqlite if it does not exist,
 * then applies schema.sql and seed.sql.
 *
 * Usage:
 *   php database/init.php
 *   php database/init.php --force   # recreate from scratch
 */

$baseDir = __DIR__;
$dbFile  = $baseDir . '/olympus.sqlite';
$force   = in_array('--force', $argv ?? [], true);

if (file_exists($dbFile) && !$force) {
    fwrite(STDERR, "Database already exists: {$dbFile}\n");
    fwrite(STDERR, "Use --force to recreate from schema and seed.\n");
    exit(1);
}

if ($force && file_exists($dbFile)) {
    unlink($dbFile);
}

$schemaFile = $baseDir . '/schema.sql';
$seedFile   = $baseDir . '/seed.sql';

foreach ([$schemaFile, $seedFile] as $file) {
    if (!is_readable($file)) {
        fwrite(STDERR, "Missing SQL file: {$file}\n");
        exit(1);
    }
}

$schema = file_get_contents($schemaFile);
$seed   = file_get_contents($seedFile);

if ($schema === false || $seed === false) {
    fwrite(STDERR, "Failed to read SQL files.\n");
    exit(1);
}

try {
    $pdo = new PDO('sqlite:' . $dbFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec($schema);
    $pdo->exec($seed);

    if (!is_writable($dbFile)) {
        throw new RuntimeException('Database file is not writable.');
    }

    echo "Database created successfully: {$dbFile}\n";
    echo "Tables: users, classes, enrollments, reviews\n";
    echo "Demo password for all seed users: demo123\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Database initialization failed: ' . $e->getMessage() . "\n");
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }
    exit(1);
}
