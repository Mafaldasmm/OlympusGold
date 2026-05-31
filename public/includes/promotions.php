<?php

declare(strict_types=1);

const MAX_FEATURED_CLASSES  = 3;
const MAX_FEATURED_TRAINERS = 3;

/**
 * @return list<array<string, mixed>>
 */
function fetchFeaturedClassesForHomepage(PDO $pdo): array
{
    $stmt = $pdo->prepare('
        SELECT
            c.id,
            c.title,
            c.description,
            c.type,
            c.day_of_week,
            c.start_time,
            c.end_time,
            u.name AS trainer_name
        FROM classes c
        LEFT JOIN users u ON u.id = c.trainer_id AND u.role = \'trainer\' AND u.active = 1
        WHERE c.featured = 1
        ORDER BY c.title COLLATE NOCASE ASC
        LIMIT :limit
    ');
    $stmt->bindValue(':limit', MAX_FEATURED_CLASSES, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * @return list<array<string, mixed>>
 */
function fetchFeaturedTrainersForHomepage(PDO $pdo): array
{
    $stmt = $pdo->prepare('
        SELECT id, name, username, bio
        FROM users
        WHERE role = \'trainer\' AND active = 1 AND featured = 1
        ORDER BY name COLLATE NOCASE ASC, username COLLATE NOCASE ASC
        LIMIT :limit
    ');
    $stmt->bindValue(':limit', MAX_FEATURED_TRAINERS, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function countFeaturedClasses(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM classes WHERE featured = 1')->fetchColumn();
}

function countFeaturedTrainers(PDO $pdo): int
{
    return (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'trainer' AND active = 1 AND featured = 1"
    )->fetchColumn();
}

/**
 * @return list<array<string, mixed>>
 */
function fetchAllClassesForPromotionAdmin(PDO $pdo): array
{
    return $pdo->query('
        SELECT c.id, c.title, c.type, c.day_of_week, c.start_time, c.featured
        FROM classes c
        ORDER BY c.featured DESC, c.title COLLATE NOCASE ASC
    ')->fetchAll();
}

/**
 * @return list<array<string, mixed>>
 */
function fetchTrainersForPromotionAdmin(PDO $pdo): array
{
    return $pdo->query("
        SELECT id, name, username, active, featured
        FROM users
        WHERE role = 'trainer'
        ORDER BY active DESC, featured DESC, name COLLATE NOCASE ASC
    ")->fetchAll();
}

/**
 * @return array{ok: bool, error: ?string}
 */
function setClassFeatured(PDO $pdo, int $classId, bool $featured): array
{
    $class = $pdo->prepare('SELECT id FROM classes WHERE id = :id');
    $class->execute(['id' => $classId]);
    if (!$class->fetch()) {
        return ['ok' => false, 'error' => 'Aula não encontrada.'];
    }

    if ($featured && countFeaturedClasses($pdo) >= MAX_FEATURED_CLASSES) {
        $current = $pdo->prepare('SELECT featured FROM classes WHERE id = :id');
        $current->execute(['id' => $classId]);
        $row = $current->fetch();
        if (!$row || !(int) $row['featured']) {
            return [
                'ok'    => false,
                'error' => 'Já existem ' . MAX_FEATURED_CLASSES . ' aulas em destaque. Remove uma antes de adicionar outra.',
            ];
        }
    }

    $pdo->prepare('UPDATE classes SET featured = :featured WHERE id = :id')
        ->execute(['featured' => $featured ? 1 : 0, 'id' => $classId]);

    return ['ok' => true, 'error' => null];
}

/**
 * @return array{ok: bool, error: ?string}
 */
function setTrainerFeatured(PDO $pdo, int $trainerId, bool $featured): array
{
    $trainer = $pdo->prepare('SELECT id, role, active FROM users WHERE id = :id');
    $trainer->execute(['id' => $trainerId]);
    $row = $trainer->fetch();

    if (!$row || $row['role'] !== 'trainer') {
        return ['ok' => false, 'error' => 'Treinador não encontrado.'];
    }

    if (!(int) $row['active']) {
        return ['ok' => false, 'error' => 'Só treinadores ativos podem ser destacados.'];
    }

    if ($featured && countFeaturedTrainers($pdo) >= MAX_FEATURED_TRAINERS) {
        $current = $pdo->prepare('SELECT featured FROM users WHERE id = :id');
        $current->execute(['id' => $trainerId]);
        $currentRow = $current->fetch();
        if (!$currentRow || !(int) $currentRow['featured']) {
            return [
                'ok'    => false,
                'error' => 'Já existem ' . MAX_FEATURED_TRAINERS . ' treinadores em destaque. Remove um antes de adicionar outro.',
            ];
        }
    }

    $pdo->prepare('UPDATE users SET featured = :featured WHERE id = :id')
        ->execute(['featured' => $featured ? 1 : 0, 'id' => $trainerId]);

    return ['ok' => true, 'error' => null];
}

/**
 * @return array{accent: string, icon: string}
 */
function promotionClassVisual(string $type): array
{
    $map = [
        'yoga'    => ['accent' => 'yoga', 'icon' => '🧘'],
        'pilates' => ['accent' => 'pilates', 'icon' => '🤸'],
        'hiit'    => ['accent' => 'hiit', 'icon' => '⚡'],
        'zumba'   => ['accent' => 'zumba', 'icon' => '💃'],
        'cycling' => ['accent' => 'cycling', 'icon' => '🚴'],
        'boxing'  => ['accent' => 'boxing', 'icon' => '🥊'],
        'other'   => ['accent' => 'other', 'icon' => '✨'],
    ];

    return $map[$type] ?? $map['other'];
}

function promotionTrainerInitials(string $name, string $username): string
{
    $source = trim($name) !== '' ? trim($name) : trim($username);
    if ($source === '') {
        return '?';
    }

    $parts = preg_split('/\s+/', $source) ?: [];
    $slice = static function (string $value, int $start, int $length = 1): string {
        if (function_exists('mb_substr')) {
            return mb_substr($value, $start, $length);
        }

        return substr($value, $start, $length);
    };

    if (count($parts) >= 2) {
        return strtoupper($slice($parts[0], 0) . $slice($parts[1], 0));
    }

    return strtoupper($slice($source, 0, 2));
}

function promotionBioExcerpt(string $bio, int $maxLength = 140): string
{
    $bio = trim($bio);
    if ($bio === '') {
        return 'Perfil profissional disponível — descobre especializações e aulas.';
    }

    if (utf8Length($bio) <= $maxLength) {
        return $bio;
    }

    $excerpt = function_exists('mb_substr')
        ? mb_substr($bio, 0, $maxLength - 1)
        : substr($bio, 0, $maxLength - 1);

    return rtrim($excerpt) . '…';
}
