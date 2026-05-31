<?php

declare(strict_types=1);

/**
 * Trainer profile data helpers.
 */

/**
 * Returns an active trainer row or null when not found / not a trainer.
 *
 * @return array<string, mixed>|null
 */
function fetchTrainerById(int $trainerId): ?array
{
    if ($trainerId <= 0) {
        return null;
    }

    $trainer = dbQuery(
        'SELECT id, username, name, email, role, active, profile_photo, bio
         FROM users
         WHERE id = :id AND role = :role AND active = 1',
        ['id' => $trainerId, 'role' => 'trainer']
    )->fetch();

    return $trainer !== false ? $trainer : null;
}

/**
 * @return list<array<string, mixed>>
 */
function fetchTrainerSpecializations(int $trainerId): array
{
    return dbQuery(
        'SELECT id, name
         FROM trainer_specializations
         WHERE trainer_id = :trainer_id
         ORDER BY name COLLATE NOCASE',
        ['trainer_id' => $trainerId]
    )->fetchAll();
}

/**
 * @return list<array<string, mixed>>
 */
function fetchTrainerClasses(int $trainerId): array
{
    return dbQuery(
        'SELECT id, title, type, day_of_week, start_time, end_time
         FROM classes
         WHERE trainer_id = :trainer_id
         ORDER BY
             CASE day_of_week
                 WHEN "monday"    THEN 1
                 WHEN "tuesday"   THEN 2
                 WHEN "wednesday" THEN 3
                 WHEN "thursday"  THEN 4
                 WHEN "friday"    THEN 5
                 WHEN "saturday"  THEN 6
                 WHEN "sunday"    THEN 7
                 ELSE 8
             END,
             start_time',
        ['trainer_id' => $trainerId]
    )->fetchAll();
}
