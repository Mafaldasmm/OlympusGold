<?php

declare(strict_types=1);

/**
 * Processes equipment management POST actions.
 * Expects: verifyCsrf and equipment helpers already loaded.
 *
 * Sets: $errors, $success, $oldName, $oldType, $oldStatus
 */

$errors    = $errors ?? [];
$success   = $success ?? '';
$oldName   = $oldName ?? '';
$oldType   = $oldType ?? '';
$oldStatus = $oldStatus ?? 'available';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

verifyCsrf();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $data = validateEquipmentInput($_POST, $errors);

        if ($data !== null) {
            createEquipment($data['name'], $data['type'], $data['status']);
            $success = "Equipamento «{$data['name']}» adicionado com sucesso.";
            $oldName   = '';
            $oldType   = '';
            $oldStatus = 'available';
        } else {
            $oldName   = trim((string) ($_POST['name'] ?? ''));
            $oldType   = trim((string) ($_POST['type'] ?? ''));
            $oldStatus = equipmentNormalizeStatus(trim((string) ($_POST['status'] ?? 'available')));
        }
    }

    if ($action === 'update_status') {
        $equipmentId = (int) ($_POST['equipment_id'] ?? 0);
        $status      = equipmentNormalizeStatus(trim((string) ($_POST['status'] ?? '')));

        if ($equipmentId <= 0) {
            $errors[] = 'Equipamento inválido.';
        } elseif (!equipmentStatusIsValid($status)) {
            $errors[] = 'O estado selecionado não é válido.';
        } elseif (fetchMainAreaEquipmentById($equipmentId) === null) {
            $errors[] = 'Equipamento não encontrado.';
        } elseif (!updateEquipmentStatus($equipmentId, $status)) {
            $errors[] = 'Não foi possível atualizar o estado do equipamento.';
        } else {
            $success = 'Estado do equipamento atualizado com sucesso.';
        }
    }

    if ($action === 'delete') {
        $equipmentId = (int) ($_POST['equipment_id'] ?? 0);
        $item        = $equipmentId > 0 ? fetchMainAreaEquipmentById($equipmentId) : null;

        if ($item === null) {
            $errors[] = 'Equipamento não encontrado.';
        } elseif (!deleteEquipment($equipmentId)) {
            $errors[] = 'Não foi possível remover o equipamento.';
        } else {
            $success = 'Equipamento «' . (string) $item['name'] . '» removido com sucesso.';
        }
    }
} catch (Throwable) {
    $errors[] = 'Ocorreu um erro ao processar a operação. Tenta novamente dentro de alguns instantes.';
}
