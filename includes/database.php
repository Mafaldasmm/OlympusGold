<?php

declare(strict_types=1);

/**
 * Shared PDO connection for the Olympus Gold Gym application.
 *
 * Usage in any PHP script:
 *   require_once dirname(__DIR__) . '/includes/database.php';
 *   $pdo = getDatabase();
 *   $stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = :id');
 *   $stmt->execute(['id' => $userId]);
 */

require_once __DIR__ . '/config.php';

/**
 * Returns a singleton PDO instance configured for secure SQLite access.
 *
 * - ERRMODE_EXCEPTION: errors throw PDOException instead of silent failures
 * - EMULATE_PREPARES false: real prepared statements on the driver
 * - foreign_keys ON: referential integrity enforced
 */
function getDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!file_exists(DB_PATH)) {
        throw new RuntimeException(
            'Database file not found at ' . DB_PATH . '. Run: php database/init.php'
        );
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');
    ensureUsersProfilePhotoColumn($pdo);
    ensureUsersReviewsBlockedColumn($pdo);
    ensureUsersBioColumn($pdo);
    ensureTrainerSpecializationsTable($pdo);

    return $pdo;
}

/**
 * Adds profile_photo to existing databases created before the column existed.
 */
function ensureUsersProfilePhotoColumn(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'profile_photo') {
            return;
        }
    }

    $pdo->exec('ALTER TABLE users ADD COLUMN profile_photo TEXT');
}

/**
 * Adds reviews_blocked to existing databases created before the column existed.
 */
function ensureUsersReviewsBlockedColumn(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'reviews_blocked') {
            return;
        }
    }

    $pdo->exec('ALTER TABLE users ADD COLUMN reviews_blocked INTEGER NOT NULL DEFAULT 0');
}

/**
 * Adds bio to existing databases created before the column existed.
 */
function ensureUsersBioColumn(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'bio') {
            return;
        }
    }

    $pdo->exec('ALTER TABLE users ADD COLUMN bio TEXT');
}

/**
 * Creates trainer_specializations on databases created before the table existed.
 */
function ensureTrainerSpecializationsTable(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS trainer_specializations (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            trainer_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
            name       TEXT    NOT NULL,
            UNIQUE (trainer_id, name)
        )'
    );
    $pdo->exec(
        'CREATE INDEX IF NOT EXISTS idx_trainer_specializations_trainer
         ON trainer_specializations (trainer_id)'
    );
}

/**
 * Executes a prepared statement with bound parameters.
 *
 * All application queries must use this helper (or PDO::prepare directly)
 * so user input is never concatenated into SQL strings.
 *
 * @param string               $sql    SQL with named (:param) or positional (?) placeholders
 * @param array<string, mixed> $params Values bound to placeholders
 * @return PDOStatement
 */
function dbQuery(string $sql, array $params = []): PDOStatement
{
    $stmt = getDatabase()->prepare($sql);
    $stmt->execute($params);

    return $stmt;
}
