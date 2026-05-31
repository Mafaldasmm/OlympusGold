<?php

declare(strict_types=1);

/**
 * Trainer profile data helpers.
 */

const TRAINER_BIO_MIN_LENGTH = 20;
const TRAINER_BIO_MAX_LENGTH = 2000;
const TRAINER_ITEM_NAME_MAX_LENGTH = 80;

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
function fetchTrainerCertifications(int $trainerId): array
{
    return dbQuery(
        'SELECT id, name
         FROM trainer_certifications
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

/**
 * Resolves which trainer profile may be edited by the current user.
 *
 * @param array<string, mixed> $user
 */
function resolveTrainerProfileTargetId(array $user): ?int
{
    $requested = (int) ($_GET['trainer_id'] ?? $_GET['id'] ?? 0);

    if ($user['role'] === 'trainer') {
        $ownId = (int) $user['id'];

        if ($requested > 0 && $requested !== $ownId) {
            return null;
        }

        return $ownId;
    }

    if ($user['role'] === 'admin') {
        if ($requested <= 0) {
            return null;
        }

        return fetchTrainerById($requested) !== null ? $requested : null;
    }

    return null;
}

/**
 * @param array<string, mixed> $user
 */
function userCanManageTrainerProfile(array $user, int $trainerId): bool
{
    if ($user['role'] === 'admin') {
        return fetchTrainerById($trainerId) !== null;
    }

    if ($user['role'] === 'trainer') {
        return (int) $user['id'] === $trainerId;
    }

    return false;
}

/**
 * @param list<string> $errors
 */
function validateTrainerBio(string $bio, array &$errors): ?string
{
    $bio = trim($bio);

    if ($bio === '') {
        $errors[] = 'A biografia é obrigatória.';
        return null;
    }

    if (utf8Length($bio) < TRAINER_BIO_MIN_LENGTH) {
        $errors[] = 'A biografia deve ter pelo menos ' . TRAINER_BIO_MIN_LENGTH . ' caracteres.';
    }

    if (utf8Length($bio) > TRAINER_BIO_MAX_LENGTH) {
        $errors[] = 'A biografia não pode ter mais de ' . TRAINER_BIO_MAX_LENGTH . ' caracteres.';
    }

    if ($errors !== []) {
        return null;
    }

    return $bio;
}

/**
 * @param list<string> $errors
 */
function validateTrainerItemName(string $name, string $fieldLabel, array &$errors): ?string
{
    $name = trim($name);

    if ($name === '') {
        $errors[] = "O nome da {$fieldLabel} é obrigatório.";
        return null;
    }

    if (utf8Length($name) > TRAINER_ITEM_NAME_MAX_LENGTH) {
        $errors[] = "O nome da {$fieldLabel} não pode ter mais de " . TRAINER_ITEM_NAME_MAX_LENGTH . ' caracteres.';
        return null;
    }

    return $name;
}

function updateTrainerBio(int $trainerId, string $bio): bool
{
    $stmt = dbQuery(
        'UPDATE users SET bio = :bio WHERE id = :id AND role = :role',
        ['bio' => $bio, 'id' => $trainerId, 'role' => 'trainer']
    );

    return $stmt->rowCount() > 0;
}

/**
 * @param list<string> $errors
 */
function addTrainerSpecialization(int $trainerId, string $name, array &$errors): bool
{
    $name = validateTrainerItemName($name, 'especialização', $errors);

    if ($name === null) {
        return false;
    }

    $dup = dbQuery(
        'SELECT id FROM trainer_specializations
         WHERE trainer_id = :trainer_id AND LOWER(name) = LOWER(:name)',
        ['trainer_id' => $trainerId, 'name' => $name]
    );

    if ($dup->fetch()) {
        $errors[] = 'Já existe uma especialização com este nome.';
        return false;
    }

    dbQuery(
        'INSERT INTO trainer_specializations (trainer_id, name) VALUES (:trainer_id, :name)',
        ['trainer_id' => $trainerId, 'name' => $name]
    );

    return true;
}

function deleteTrainerSpecialization(int $trainerId, int $specializationId): bool
{
    $stmt = dbQuery(
        'DELETE FROM trainer_specializations WHERE id = :id AND trainer_id = :trainer_id',
        ['id' => $specializationId, 'trainer_id' => $trainerId]
    );

    return $stmt->rowCount() > 0;
}

/**
 * @param list<string> $errors
 */
function addTrainerCertification(int $trainerId, string $name, array &$errors): bool
{
    $name = validateTrainerItemName($name, 'certificação', $errors);

    if ($name === null) {
        return false;
    }

    $dup = dbQuery(
        'SELECT id FROM trainer_certifications
         WHERE trainer_id = :trainer_id AND LOWER(name) = LOWER(:name)',
        ['trainer_id' => $trainerId, 'name' => $name]
    );

    if ($dup->fetch()) {
        $errors[] = 'Já existe uma certificação com este nome.';
        return false;
    }

    dbQuery(
        'INSERT INTO trainer_certifications (trainer_id, name) VALUES (:trainer_id, :name)',
        ['trainer_id' => $trainerId, 'name' => $name]
    );

    return true;
}

function deleteTrainerCertification(int $trainerId, int $certificationId): bool
{
    $stmt = dbQuery(
        'DELETE FROM trainer_certifications WHERE id = :id AND trainer_id = :trainer_id',
        ['id' => $certificationId, 'trainer_id' => $trainerId]
    );

    return $stmt->rowCount() > 0;
}

function fetchTrainerSpecializationById(int $trainerId, int $specializationId): ?array
{
    $row = dbQuery(
        'SELECT id, name FROM trainer_specializations WHERE id = :id AND trainer_id = :trainer_id',
        ['id' => $specializationId, 'trainer_id' => $trainerId]
    )->fetch();

    return $row !== false ? $row : null;
}

function fetchTrainerCertificationById(int $trainerId, int $certificationId): ?array
{
    $row = dbQuery(
        'SELECT id, name FROM trainer_certifications WHERE id = :id AND trainer_id = :trainer_id',
        ['id' => $certificationId, 'trainer_id' => $trainerId]
    )->fetch();

    return $row !== false ? $row : null;
}

/**
 * Distinct specialization names used across the gym (for suggestions).
 *
 * @return list<string>
 */
function fetchKnownSpecializationNames(int $excludeTrainerId = 0): array
{
    $sql = 'SELECT DISTINCT name FROM trainer_specializations';
    $params = [];

    if ($excludeTrainerId > 0) {
        $sql .= ' WHERE trainer_id != :trainer_id';
        $params['trainer_id'] = $excludeTrainerId;
    }

    $sql .= ' ORDER BY name COLLATE NOCASE';

    $rows = dbQuery($sql, $params)->fetchAll();

    return array_map(static fn (array $row): string => (string) $row['name'], $rows);
}

/**
 * Distinct certification names used across the gym (for suggestions).
 *
 * @return list<string>
 */
function fetchKnownCertificationNames(int $excludeTrainerId = 0): array
{
    $sql = 'SELECT DISTINCT name FROM trainer_certifications';
    $params = [];

    if ($excludeTrainerId > 0) {
        $sql .= ' WHERE trainer_id != :trainer_id';
        $params['trainer_id'] = $excludeTrainerId;
    }

    $sql .= ' ORDER BY name COLLATE NOCASE';

    $rows = dbQuery($sql, $params)->fetchAll();

    return array_map(static fn (array $row): string => (string) $row['name'], $rows);
}

/**
 * Names this trainer does not have yet (from the global pool).
 *
 * @return list<string>
 */
function fetchSuggestedSpecializationNames(int $trainerId): array
{
    $known = fetchKnownSpecializationNames();
    $current = array_map(
        static fn (array $row): string => (string) $row['name'],
        fetchTrainerSpecializations($trainerId)
    );
    $currentLower = array_map('strtolower', $current);

    return array_values(array_filter(
        $known,
        static fn (string $name): bool => !in_array(strtolower($name), $currentLower, true)
    ));
}

/**
 * @return list<string>
 */
function fetchSuggestedCertificationNames(int $trainerId): array
{
    $known = fetchKnownCertificationNames();
    $current = array_map(
        static fn (array $row): string => (string) $row['name'],
        fetchTrainerCertifications($trainerId)
    );
    $currentLower = array_map('strtolower', $current);

    return array_values(array_filter(
        $known,
        static fn (string $name): bool => !in_array(strtolower($name), $currentLower, true)
    ));
}
