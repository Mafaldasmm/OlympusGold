<?php

declare(strict_types=1);

/**
 * Equipment management UI partial.
 *
 * Required variables:
 *   $manageFormAction (string)
 *   $manageAvailabilityUrl (string)
 *   $errors, $success, $loadError, $equipment (array)
 *   $oldName, $oldType, $oldStatus
 *   $typeLabels (array)
 *
 * Optional:
 *   $manageHeaderTitle (string) — HTML allowed in span part only via caller
 *   $manageHeaderSubtitle (string)
 *   $manageHeaderLinks (list<array{href: string, label: string}>)
 *   $manageBackHref, $manageBackLabel (string)
 */

$manageHeaderTitle    = $manageHeaderTitle ?? 'Gestão de <span>Equipamentos</span>';
$manageHeaderSubtitle = $manageHeaderSubtitle ?? 'Área principal de treino';
$manageHeaderLinks    = $manageHeaderLinks ?? [];
$manageBackHref       = $manageBackHref ?? '';
$manageBackLabel      = $manageBackLabel ?? '';

?>
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

        <details class="admin-card admin-details">
            <summary class="admin-details__summary">
                <span class="admin-details__title">Adicionar equipamento</span>
            </summary>
            <div class="admin-details__body">
                <form method="POST" action="<?= e($manageFormAction) ?>" novalidate>
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
                    <span class="badge-count" id="equipment-manage-total"><?= count($equipment) ?></span>
                </span>
            </summary>
            <div class="admin-details__body">
                <?php if ($equipment === []): ?>
                    <p class="equipment-profile-empty">Ainda não existe equipamento registado na área principal.</p>
                <?php else: ?>
                    <div class="admin-filters equipment-filters" role="search">
                        <input type="search"
                               id="equipment-manage-search"
                               placeholder="Pesquisar por nome ou tipo…"
                               aria-label="Pesquisar equipamentos">
                        <select id="equipment-manage-filter-type" aria-label="Filtrar por tipo">
                            <option value="">Todos os tipos</option>
                            <?php foreach ($typeLabels as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="equipment-manage-filter-status" aria-label="Filtrar por estado">
                            <option value="">Todos os estados</option>
                            <?php foreach (EQUIPMENT_STATUSES as $statusValue): ?>
                                <option value="<?= e($statusValue) ?>"><?= e(equipmentStatusLabel($statusValue)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn-admin-filter-clear" id="equipment-manage-filter-clear">Limpar</button>
                        <span class="admin-filter-count" id="equipment-manage-filter-count"></span>
                    </div>

                    <div class="table-wrapper">
                        <table class="admin-table equipment-manage-table" id="equipment-manage-table">
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
                                    $itemStatus = equipmentNormalizeStatus((string) ($item['status'] ?? 'available'));
                                    if (!equipmentStatusIsValid($itemStatus)) {
                                        $itemStatus = 'maintenance';
                                    }
                                    $statusClass = equipmentStatusCssClass($itemStatus);
                                    $typeValue   = (string) ($item['type'] ?? '');
                                    $updatedAt   = substr((string) ($item['updated_at'] ?? ''), 0, 16);
                                    $searchData  = strtolower(
                                        (string) $item['name'] . ' '
                                        . equipmentTypeLabel($typeValue) . ' '
                                        . $typeValue
                                    );
                                    ?>
                                    <tr data-equipment-row
                                        data-search="<?= e($searchData) ?>"
                                        data-type="<?= e($typeValue) ?>"
                                        data-status="<?= e($itemStatus) ?>">
                                        <td data-label="Nome"><?= e((string) $item['name']) ?></td>
                                        <td data-label="Tipo"><?= e(equipmentTypeLabel($typeValue)) ?></td>
                                        <td data-label="Estado">
                                            <span class="equipment-status <?= e($statusClass) ?>">
                                                <?= e(equipmentStatusLabel($itemStatus)) ?>
                                            </span>
                                        </td>
                                        <td data-label="Atualizado"><?= e($updatedAt !== '' ? $updatedAt : '—') ?></td>
                                        <td data-label="Ações" class="equipment-manage-actions">
                                            <form method="POST"
                                                  action="<?= e($manageFormAction) ?>"
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
                                                  action="<?= e($manageFormAction) ?>"
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
                        <p class="empty-state equipment-filter-empty" id="equipment-manage-filter-empty" hidden>
                            Nenhum equipamento corresponde aos filtros.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </details>

        <?php endif; ?>
