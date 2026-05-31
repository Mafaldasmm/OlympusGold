<?php

declare(strict_types=1);

/**
 * Equipment availability helpers (main training area).
 */

const EQUIPMENT_AREA_MAIN = 'main';

/** @var list<string> */
const EQUIPMENT_STATUSES = ['available', 'in_use', 'maintenance'];

/**
 * Maps legacy DB values to the current status vocabulary.
 */
function equipmentNormalizeStatus(string $status): string
{
    return $status === 'unavailable' ? 'maintenance' : $status;
}

/**
 * @return list<array<string, mixed>>
 */
function fetchMainAreaEquipment(): array
{
    return dbQuery(
        'SELECT id, name, type, area, status, updated_at
         FROM equipment
         WHERE area = :area
         ORDER BY type COLLATE NOCASE, name COLLATE NOCASE',
        ['area' => EQUIPMENT_AREA_MAIN]
    )->fetchAll();
}

function equipmentStatusIsValid(string $status): bool
{
    $status = equipmentNormalizeStatus($status);

    return in_array($status, EQUIPMENT_STATUSES, true);
}

function equipmentStatusLabel(string $status): string
{
    return match (equipmentNormalizeStatus($status)) {
        'available'   => 'Disponível',
        'in_use'      => 'Em uso',
        'maintenance' => 'Em manutenção',
        default       => 'Desconhecido',
    };
}

function equipmentStatusCssClass(string $status): string
{
    return match (equipmentNormalizeStatus($status)) {
        'available'   => 'equipment-status--available',
        'in_use'      => 'equipment-status--in-use',
        'maintenance' => 'equipment-status--maintenance',
        default       => 'equipment-status--unknown',
    };
}

/**
 * @return array<string, string>
 */
function equipmentTypeLabels(): array
{
    return [
        'cardio'     => 'Cardio',
        'strength'   => 'Força',
        'functional' => 'Funcional',
        'other'      => 'Outro',
    ];
}

function equipmentTypeLabel(string $type): string
{
    $labels = equipmentTypeLabels();

    return $labels[$type] ?? ucfirst($type);
}

function equipmentTypeIsValid(string $type): bool
{
    return array_key_exists($type, equipmentTypeLabels());
}

/**
 * @return array<string, mixed>|null
 */
function fetchMainAreaEquipmentById(int $equipmentId): ?array
{
    if ($equipmentId <= 0) {
        return null;
    }

    $row = dbQuery(
        'SELECT id, name, type, area, status, updated_at
         FROM equipment
         WHERE id = :id AND area = :area',
        ['id' => $equipmentId, 'area' => EQUIPMENT_AREA_MAIN]
    )->fetch();

    return $row !== false ? $row : null;
}

/**
 * @param list<string> $errors
 * @return array{name: string, type: string, status: string}|null
 */
function validateEquipmentInput(array $input, array &$errors, ?int $excludeId = null): ?array
{
    $name   = trim((string) ($input['name'] ?? ''));
    $type   = trim((string) ($input['type'] ?? ''));
    $status = trim((string) ($input['status'] ?? 'available'));

    if ($name === '') {
        $errors[] = 'O nome do equipamento é obrigatório.';
    } elseif (utf8Length($name) > 100) {
        $errors[] = 'O nome não pode ter mais de 100 caracteres.';
    }

    if ($type === '') {
        $errors[] = 'O tipo de equipamento é obrigatório.';
    } elseif (!equipmentTypeIsValid($type)) {
        $errors[] = 'O tipo de equipamento selecionado não é válido.';
    }

    if ($status === '') {
        $status = 'available';
    }

    $status = equipmentNormalizeStatus($status);

    if (!equipmentStatusIsValid($status)) {
        $errors[] = 'O estado selecionado não é válido.';
    }

    if ($excludeId !== null) {
        $dup = dbQuery(
            'SELECT id FROM equipment
             WHERE area = :area AND LOWER(name) = LOWER(:name) AND id != :exclude_id',
            ['area' => EQUIPMENT_AREA_MAIN, 'name' => $name, 'exclude_id' => $excludeId]
        );
    } else {
        $dup = dbQuery(
            'SELECT id FROM equipment
             WHERE area = :area AND LOWER(name) = LOWER(:name)',
            ['area' => EQUIPMENT_AREA_MAIN, 'name' => $name]
        );
    }

    if ($dup->fetch()) {
        $errors[] = 'Já existe um equipamento com este nome na área principal.';
    }

    if ($errors !== []) {
        return null;
    }

    return [
        'name'   => $name,
        'type'   => $type,
        'status' => $status,
    ];
}

function createEquipment(string $name, string $type, string $status = 'available'): int
{
    dbQuery(
        'INSERT INTO equipment (name, type, area, status, updated_at)
         VALUES (:name, :type, :area, :status, datetime(\'now\'))',
        [
            'name'   => $name,
            'type'   => $type,
            'area'   => EQUIPMENT_AREA_MAIN,
            'status' => $status,
        ]
    );

    return (int) getDatabase()->lastInsertId();
}

function updateEquipmentStatus(int $equipmentId, string $status): bool
{
    if (!equipmentStatusIsValid($status)) {
        return false;
    }

    $stmt = dbQuery(
        'UPDATE equipment
         SET status = :status, updated_at = datetime(\'now\')
         WHERE id = :id AND area = :area',
        [
            'status' => $status,
            'id'     => $equipmentId,
            'area'   => EQUIPMENT_AREA_MAIN,
        ]
    );

    return $stmt->rowCount() > 0;
}

function deleteEquipment(int $equipmentId): bool
{
    $stmt = dbQuery(
        'DELETE FROM equipment WHERE id = :id AND area = :area',
        ['id' => $equipmentId, 'area' => EQUIPMENT_AREA_MAIN]
    );

    return $stmt->rowCount() > 0;
}
