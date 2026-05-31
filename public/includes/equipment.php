<?php

declare(strict_types=1);

/**
 * Equipment availability helpers (main training area).
 */

const EQUIPMENT_AREA_MAIN = 'main';

/** @var list<string> */
const EQUIPMENT_STATUSES = ['available', 'in_use', 'unavailable'];

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
    return in_array($status, EQUIPMENT_STATUSES, true);
}

function equipmentStatusLabel(string $status): string
{
    return match ($status) {
        'available'   => 'Disponível',
        'in_use'      => 'Em uso',
        'unavailable' => 'Indisponível',
        default       => 'Desconhecido',
    };
}

function equipmentStatusCssClass(string $status): string
{
    return match ($status) {
        'available'   => 'equipment-status--available',
        'in_use'      => 'equipment-status--in-use',
        'unavailable' => 'equipment-status--unavailable',
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
