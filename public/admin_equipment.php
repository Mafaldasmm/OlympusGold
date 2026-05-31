<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/equipment.php';

requireRole('admin');

$errors    = [];
$success   = '';
$oldName   = '';
$oldType   = '';
$oldStatus = 'available';
$loadError = false;
$equipment = [];

require INCLUDES_PATH . '/equipment_manage_request.php';

try {
    $equipment = fetchMainAreaEquipment();
} catch (Throwable) {
    $loadError = true;
}

$typeLabels = equipmentTypeLabels();

$manageFormAction        = pageUrl('admin_equipment.php');
$manageAvailabilityUrl   = pageUrl('equipment.php');
$manageHeaderTitle       = 'Gestão de <span>Equipamentos</span>';
$manageHeaderSubtitle    = 'Área principal de treino';
$manageHeaderLinks       = [
    ['href' => pageUrl('admin_analytics.php'), 'label' => 'Analytics'],
    ['href' => pageUrl('admin_users.php'), 'label' => 'Gerir Utilizadores'],
    ['href' => pageUrl('admin_classes.php'), 'label' => 'Gerir Aulas'],
];
$manageBackHref          = '';
$manageBackLabel         = '';

$currentPage      = 'admin';
$pageTitle        = 'Gestão de Equipamentos | Admin';
$extraStylesheets = ['pages/admin.css', 'pages/equipment.css', 'pages/manage-equipment.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="admin-main equipment-manage-main">
    <div class="admin-container">

        <div class="admin-header equipment-page-header">
            <div class="equipment-page-header__text">
                <h1 class="admin-title"><?= $manageHeaderTitle ?></h1>
                <p class="equipment-subtitle equipment-manage-subtitle"><?= e($manageHeaderSubtitle) ?></p>
            </div>
            <div class="equipment-page-header__actions">
                <?php foreach ($manageHeaderLinks as $link): ?>
                    <a href="<?= e($link['href']) ?>" class="btn-admin-secondary"><?= e($link['label']) ?></a>
                <?php endforeach; ?>
                <a href="<?= e($manageAvailabilityUrl) ?>" class="btn-admin-primary equipment-header-btn">
                    Ver disponibilidade
                </a>
            </div>
        </div>

        <?php require INCLUDES_PATH . '/equipment_manage_view.php'; ?>
    </div>
</main>

<script src="<?= e(asset('js/equipment-filters.js')) ?>"></script>

<?php require INCLUDES_PATH . '/footer.php'; ?>
