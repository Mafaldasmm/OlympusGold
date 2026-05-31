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

$availableCount   = 0;
$inUseCount       = 0;
$unavailableCount = 0;

foreach ($equipment as $item) {
    $status = (string) ($item['status'] ?? '');
    if ($status === 'available') {
        $availableCount++;
    } elseif ($status === 'in_use') {
        $inUseCount++;
    } elseif ($status === 'unavailable') {
        $unavailableCount++;
    }
}

$currentPage      = 'equipment';
$pageTitle        = 'Disponibilidade de Equipamentos | Olympus Gold Gym';
$extraStylesheets = ['pages/equipment.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="equipment-page">
    <header class="equipment-header">
        <h1>Disponibilidade de <span>Equipamentos</span></h1>
        <p class="equipment-subtitle">Área principal de treino — estado em tempo real</p>
    </header>

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
            <div class="equipment-summary__item equipment-summary__item--unavailable">
                <span class="equipment-summary__count"><?= $unavailableCount ?></span>
                <span class="equipment-summary__label">Indisponível</span>
            </div>
        </div>

        <ul class="equipment-grid">
            <?php foreach ($equipment as $item): ?>
                <?php
                $status = (string) ($item['status'] ?? '');
                if (!equipmentStatusIsValid($status)) {
                    $status = 'unavailable';
                }
                $statusLabel = equipmentStatusLabel($status);
                $statusClass = equipmentStatusCssClass($status);
                $typeLabel   = equipmentTypeLabel((string) ($item['type'] ?? ''));
                $updatedAt   = substr((string) ($item['updated_at'] ?? ''), 0, 16);
                ?>
                <li class="equipment-card card">
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
    <?php endif; ?>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
