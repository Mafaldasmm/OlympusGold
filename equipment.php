<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/equipment.php';

requireLogin();

$loadError  = false;
$equipment  = [];

try {
    $equipment = fetchMainAreaEquipment();
} catch (Throwable) {
    $loadError = true;
}

$availableCount    = 0;
$inUseCount        = 0;
$maintenanceCount  = 0;

foreach ($equipment as $item) {
    $status = equipmentNormalizeStatus((string) ($item['status'] ?? ''));
    if ($status === 'available') {
        $availableCount++;
    } elseif ($status === 'in_use') {
        $inUseCount++;
    } elseif ($status === 'maintenance') {
        $maintenanceCount++;
    }
}

$typeLabels = equipmentTypeLabels();

$currentPage      = 'equipment';
$pageTitle        = 'Disponibilidade de Equipamentos | Olympus Gold Gym';
$extraStylesheets = ['pages/admin.css', 'pages/equipment.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="equipment-page">
    <header class="equipment-hero" aria-labelledby="equipment-page-title">
        <img class="equipment-hero__image"
             src="<?= e(asset('img/backgrounds/gym-interior.jpg')) ?>"
             alt="Área principal do ginásio com equipamentos de musculação e cardio"
             width="1024"
             height="683"
             decoding="async"
             fetchpriority="high">
        <div class="equipment-hero__overlay" aria-hidden="true"></div>
        <div class="equipment-hero__text">
            <h1 id="equipment-page-title">Disponibilidade de <span>Equipamentos</span></h1>
            <p class="equipment-subtitle">Área principal de treino — estado em tempo real</p>
        </div>
    </header>

    <div class="equipment-page__content">
    <?php if ($loadError): ?>
        <section class="card equipment-message equipment-message--error" role="alert">
            <h2>Informação temporariamente indisponível</h2>
            <p>Não foi possível carregar a disponibilidade dos equipamentos. Tenta novamente dentro de alguns instantes.</p>
        </section>
    <?php elseif ($equipment === []): ?>
        <section class="card equipment-message equipment-message--empty">
            <h2>Sem equipamentos registados</h2>
            <p>Ainda não existe equipamento registado na área principal de treino.</p>
        </section>
    <?php else: ?>
        <div class="equipment-summary" aria-label="Resumo da disponibilidade">
            <div class="equipment-summary__item equipment-summary__item--available">
                <span class="equipment-summary__count"><?= $availableCount ?></span>
                <span class="equipment-summary__label">Disponível</span>
            </div>
            <div class="equipment-summary__item equipment-summary__item--in-use">
                <span class="equipment-summary__count"><?= $inUseCount ?></span>
                <span class="equipment-summary__label">Em uso</span>
            </div>
            <div class="equipment-summary__item equipment-summary__item--maintenance">
                <span class="equipment-summary__count"><?= $maintenanceCount ?></span>
                <span class="equipment-summary__label">Em manutenção</span>
            </div>
        </div>

        <details class="admin-card admin-details equipment-availability-details" open>
            <summary class="admin-details__summary">
                <span class="admin-details__title">
                    Lista de equipamentos
                    <span class="badge-count"><?= count($equipment) ?></span>
                </span>
            </summary>
            <div class="admin-details__body">
                <div class="admin-filters equipment-filters" role="search">
                    <input type="search"
                           id="equipment-availability-search"
                           placeholder="Pesquisar por nome ou tipo…"
                           aria-label="Pesquisar equipamentos">
                    <select id="equipment-availability-filter-type" aria-label="Filtrar por tipo">
                        <option value="">Todos os tipos</option>
                        <?php foreach ($typeLabels as $value => $label): ?>
                            <option value="<?= e($value) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="equipment-availability-filter-status" aria-label="Filtrar por estado">
                        <option value="">Todos os estados</option>
                        <?php foreach (EQUIPMENT_STATUSES as $statusValue): ?>
                            <option value="<?= e($statusValue) ?>"><?= e(equipmentStatusLabel($statusValue)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn-admin-filter-clear" id="equipment-availability-filter-clear">Limpar</button>
                    <span class="admin-filter-count" id="equipment-availability-filter-count"></span>
                </div>

                <ul class="equipment-grid" id="equipment-availability-list">
                    <?php foreach ($equipment as $item): ?>
                        <?php
                        $status = equipmentNormalizeStatus((string) ($item['status'] ?? ''));
                        if (!equipmentStatusIsValid($status)) {
                            $status = 'maintenance';
                        }
                        $statusLabel = equipmentStatusLabel($status);
                        $statusClass = equipmentStatusCssClass($status);
                        $typeValue   = (string) ($item['type'] ?? '');
                        $typeLabel   = equipmentTypeLabel($typeValue);
                        $updatedAt   = substr((string) ($item['updated_at'] ?? ''), 0, 16);
                        $searchData  = strtolower((string) $item['name'] . ' ' . $typeLabel . ' ' . $typeValue);
                        ?>
                        <li class="equipment-card card"
                            data-equipment-item
                            data-search="<?= e($searchData) ?>"
                            data-type="<?= e($typeValue) ?>"
                            data-status="<?= e($status) ?>">
                            <div class="equipment-card__header">
                                <h2 class="equipment-card__name"><?= e((string) $item['name']) ?></h2>
                                <span class="equipment-status <?= e($statusClass) ?>"
                                      aria-label="Estado: <?= e($statusLabel) ?>">
                                    <?= e($statusLabel) ?>
                                </span>
                            </div>
                            <p class="equipment-card__type">
                                <strong>Tipo:</strong> <?= e($typeLabel) ?>
                            </p>
                            <?php if ($updatedAt !== ''): ?>
                                <p class="equipment-card__updated">
                                    Atualizado: <?= e($updatedAt) ?>
                                </p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="empty-state equipment-filter-empty" id="equipment-availability-filter-empty" hidden>
                    Nenhum equipamento corresponde aos filtros.
                </p>
            </div>
        </details>
    <?php endif; ?>
    </div>
</main>

<script src="<?= e(asset('js/equipment-filters.js')) ?>"></script>

<?php require INCLUDES_PATH . '/footer.php'; ?>
