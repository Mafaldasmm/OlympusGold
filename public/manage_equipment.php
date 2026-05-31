<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/equipment.php';

$user = requireLogin();

if ($user['role'] === 'admin') {
    redirect(pageUrl('admin_equipment.php'));
}

requireRole('trainer');

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

$manageFormAction      = pageUrl('manage_equipment.php');
$manageAvailabilityUrl = pageUrl('equipment.php');
$manageHeaderTitle     = 'Gerir <span>Equipamentos</span>';
$manageHeaderSubtitle  = 'Área principal de treino';
$manageHeaderLinks     = [];
$manageBackHref        = pageUrl('trainer_dashboard.php');
$manageBackLabel       = '← Voltar ao Dashboard';

$currentPage      = 'trainer';
$pageTitle        = 'Gerir Equipamentos | Olympus Gold Gym';
$extraStylesheets = ['pages/trainer.css', 'pages/admin.css', 'pages/equipment.css', 'pages/manage-equipment.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="trainer-main equipment-manage-main">
    <div class="trainer-container admin-container">

        <div class="trainer-header equipment-page-header">
            <div class="equipment-page-header__text">
                <h1 class="trainer-title"><?= $manageHeaderTitle ?></h1>
                <p class="trainer-sub equipment-manage-subtitle"><?= e($manageHeaderSubtitle) ?></p>
            </div>
            <div class="equipment-page-header__actions">
                <a href="<?= e($manageBackHref) ?>" class="btn-admin-secondary"><?= e($manageBackLabel) ?></a>
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
