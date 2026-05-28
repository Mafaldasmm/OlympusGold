<?php
 
declare(strict_types=1);
 
/**
 * reset_db.php — Drop and recreate the Olympus Gold Gym database.
 *
 * ⚠️  For development / demo use only. Destroys all data.
 *
 * Usage:
 *   php reset_db.php
 *   # or via browser at http://localhost:8000/reset_db.php (dev only)
 */
 
define('APP_ROOT', __DIR__);
define('DB_PATH',  APP_ROOT . '/database/olympus.sqlite');
 
$schemaFile = APP_ROOT . '/database/schema.sql';
$seedFile   = APP_ROOT . '/database/seed.sql';
 
// ---- safety guard in web context ----
if (php_sapi_name() !== 'cli') {
    // Allow only in dev (no HTTPS = likely local)
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        http_response_code(403);
        exit('reset_db.php is disabled in production.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}
 
function say(string $msg): void
{
    echo $msg . PHP_EOL;
}
 
say('=== Olympus Gold Gym — Database Reset ===');
 
// 1. Remove existing database
if (file_exists(DB_PATH)) {
    unlink(DB_PATH);
    say('✓ Old database removed.');
}
 
// 2. Connect (creates new file)
try {
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    say('✓ New database created at: ' . DB_PATH);
} catch (PDOException $e) {
    say('✗ Failed to create database: ' . $e->getMessage());
    exit(1);
}
 
// 3. Apply schema
if (!file_exists($schemaFile)) {
    say('✗ schema.sql not found at: ' . $schemaFile);
    exit(1);
}
 
try {
    $pdo->exec(file_get_contents($schemaFile));
    say('✓ Schema applied (schema.sql).');
} catch (PDOException $e) {
    say('✗ Schema error: ' . $e->getMessage());
    exit(1);
}
 
// 4. Apply seed data
if (!file_exists($seedFile)) {
    say('⚠ seed.sql not found — skipping seed data.');
} else {
    try {
        $pdo->exec(file_get_contents($seedFile));
        say('✓ Seed data applied (seed.sql).');
    } catch (PDOException $e) {
        say('✗ Seed error: ' . $e->getMessage());
        exit(1);
    }
}
 
say('');
say('=== Reset complete! Demo credentials ===');
say('  Admin  :  admin / demo123');
say('  Trainer:  ana.silva / demo123');
say('  Member :  joao.membro / demo123');
say('');
say('Run server: php -S localhost:8000 -t public/');
 
