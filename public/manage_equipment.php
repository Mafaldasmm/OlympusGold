<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/equipment.php';

requireRole('admin', 'trainer');

$errors  = [];
$success = '';
$loadError = false;
$equipment = [];

$oldName   = '';
$oldType   = '';
$oldStatus = 'available';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $data = validateEquipmentInput($_POST, $errors);

            if ($data !== null) {
                createEquipment($data['name'], $data['type'], $data['status']);
                $success = "Equipamento «{$data['name']}» adicionado com sucesso.";
                $oldName = '';
                $oldType = '';
                $oldStatus = 'available';
            } else {
                $oldName   = trim((string) ($_POST['name'] ?? ''));
                $oldType   = trim((string) ($_POST['type'] ?? ''));
                $oldStatus = trim((string) ($_POST['status'] ?? 'available'));
            }
        }

        if ($action === 'update_status') {
            $equipmentId = (int) ($_POST['equipment_id'] ?? 0);
            $status      = trim((string) ($_POST['status'] ?? ''));

            if ($equipmentId <= 0) {
                $errors[] = 'Equipamento inválido.';
            } elseif (!equipmentStatusIsValid($status)) {
                $errors[] = 'O estado de disponibilidade selecionado não é válido.';
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
}

try {
    $equipment = fetchMainAreaEquipment();
} catch (Throwable) {
    $loadError = true;
}

$typeLabels = equipmentTypeLabels();

$currentPage      = 'equipment-manage';
$pageTitle        = 'Gerir Equipamentos | Olympus Gold Gym';
$extraStylesheets = ['pages/admin.css', 'pages/equipment.css', 'pages/manage-equipment.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="admin-main equipment-manage-main">
    <div class="admin-container">

        <div class="admin-header">
            <div>
                <h1 class="admin-title">Gerir <span>Equipamentos</span></h1>
                <p class="equipment-subtitle equipment-manage-subtitle">Área principal de treino</p>
            </div>
            <a href="<?= e(pageUrl('equipment.php')) ?>" class="btn-secondary equipment-manage-view-link">
                Ver disponibilidade
            </a>
        </div>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert-error" role="alert">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($loadError): ?>
            <section class="admin-card equipment-message equipment-message--error" role="alert">
                <h2>Informação temporariamente indisponível</h2>
                <p>Não foi possível carregar a lista de equipamentos. Tenta novamente dentro de alguns instantes.</p>
            </section>
        <?php else: ?>

        <details class="admin-card admin-details" open>
            <summary class="admin-details__summary">
                <span class="admin-details__title">Adicionar equipamento</span>
            </summary>
            <div class="admin-details__body">
                <form method="POST" action="<?= e(pageUrl('manage_equipment.php')) ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-grid equipment-form-grid">
                        <div class="input-group">
                            <label for="name">Nome *</label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   required
                                   maxlength="100"
                                   placeholder="Ex: Passadeira elétrica 3"
                                   value="<?= e($oldName) ?>">
                        </div>

                        <div class="input-group">
                            <label for="type">Tipo *</label>
                            <select id="type" name="type" required>
                                <option value="">— Selecionar —</option>
                                <?php foreach ($typeLabels as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= $oldType === $value ? ' selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label for="create_status">Estado inicial *</label>
                            <select id="create_status" name="status" required>
                                <?php foreach (EQUIPMENT_STATUSES as $statusValue): ?>
                                    <option value="<?= e($statusValue) ?>"<?= $oldStatus === $statusValue ? ' selected' : '' ?>>
                                        <?= e(equipmentStatusLabel($statusValue)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-admin-primary">➕ Adicionar equipamento</button>
                    </div>
                </form>
            </div>
        </details>

        <details class="admin-card admin-details" open>
            <summary class="admin-details__summary">
                <span class="admin-details__title">
                    Equipamentos
                    <span class="badge-count"><?= count($equipment) ?></span>
                </span>
            </summary>
            <div class="admin-details__body">
                <?php if ($equipment === []): ?>
                    <p class="equipment-profile-empty">Ainda não existe equipamento registado na área principal.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="admin-table equipment-manage-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Atualizado</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipment as $item): ?>
                                    <?php
                                    $itemId     = (int) $item['id'];
                                    $itemStatus = (string) ($item['status'] ?? 'available');
                                    if (!equipmentStatusIsValid($itemStatus)) {
                                        $itemStatus = 'unavailable';
                                    }
                                    $statusClass = equipmentStatusCssClass($itemStatus);
                                    $updatedAt   = substr((string) ($item['updated_at'] ?? ''), 0, 16);
                                    ?>
                                    <tr>
                                        <td data-label="Nome"><?= e((string) $item['name']) ?></td>
                                        <td data-label="Tipo"><?= e(equipmentTypeLabel((string) ($item['type'] ?? ''))) ?></td>
                                        <td data-label="Estado">
                                            <span class="equipment-status <?= e($statusClass) ?>">
                                                <?= e(equipmentStatusLabel($itemStatus)) ?>
                                            </span>
                                        </td>
                                        <td data-label="Atualizado"><?= e($updatedAt !== '' ? $updatedAt : '—') ?></td>
                                        <td data-label="Ações" class="equipment-manage-actions">
                                            <form method="POST"
                                                  action="<?= e(pageUrl('manage_equipment.php')) ?>"
                                                  class="inline-form equipment-status-form">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="equipment_id" value="<?= $itemId ?>">
                                                <select name="status"
                                                        class="select-inline"
                                                        aria-label="Alterar estado de <?= e((string) $item['name']) ?>">
                                                    <?php foreach (EQUIPMENT_STATUSES as $statusValue): ?>
                                                        <option value="<?= e($statusValue) ?>"<?= $itemStatus === $statusValue ? ' selected' : '' ?>>
                                                            <?= e(equipmentStatusLabel($statusValue)) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn-table-edit">✓ Aplicar</button>
                                            </form>

                                            <form method="POST"
                                                  action="<?= e(pageUrl('manage_equipment.php')) ?>"
                                                  class="inline-form equipment-delete-form"
                                                  data-confirm="<?= e('Remover «' . (string) $item['name'] . '»? Esta ação não pode ser desfeita.') ?>"
                                                  data-confirm-title="Remover equipamento"
                                                  data-confirm-ok="Remover">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="equipment_id" value="<?= $itemId ?>">
                                                <button type="submit" class="btn-table-delete">🗑 Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </details>

        <?php endif; ?>
    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
