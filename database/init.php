<?php

declare(strict_types=1);

/**
 * Database initialization — creates olympus.sqlite from schema.sql and seed.sql.
 *
 * CLI:
 *   php database/init.php
 *   php database/init.php --force
 */

/**
 * @throws RuntimeException
 */
function bootstrapOlympusDatabase(bool $force = false): void
{
    $baseDir = __DIR__;
    $dbFile  = $baseDir . '/olympus.sqlite';

    if (file_exists($dbFile) && !$force) {
        return;
    }

    if ($force && file_exists($dbFile)) {
        unlink($dbFile);
    }

    $schemaFile = $baseDir . '/schema.sql';
    $seedFile   = $baseDir . '/seed.sql';

    foreach ([$schemaFile, $seedFile] as $file) {
        if (!is_readable($file)) {
            throw new RuntimeException("Missing SQL file: {$file}");
        }
    }

    $schema = file_get_contents($schemaFile);
    $seed   = file_get_contents($seedFile);

    if ($schema === false || $seed === false) {
        throw new RuntimeException('Failed to read SQL files.');
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
    } catch (PDOException $e) {
        if (file_exists($dbFile)) {
            unlink($dbFile);
        }

        throw new RuntimeException('Database initialization failed: ' . $e->getMessage(), 0, $e);
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $force = in_array('--force', $argv ?? [], true);

    if (file_exists(__DIR__ . '/olympus.sqlite') && !$force) {
        fwrite(STDERR, 'Database already exists: ' . __DIR__ . "/olympus.sqlite\n");
        fwrite(STDERR, "Use --force to recreate from schema and seed.\n");
        exit(1);
    }

    try {
        bootstrapOlympusDatabase($force);
    } catch (RuntimeException $e) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }

    echo 'Database created successfully: ' . __DIR__ . "/olympus.sqlite\n";
    echo "Demo accounts (demo123): admin, ana.silva, joao.membro\n";
    echo "Other seed users password: OgGym2026!\n";
}
