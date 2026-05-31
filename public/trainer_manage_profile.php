<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/trainer.php';

$user = requireRole('admin', 'trainer');

$trainerId = resolveTrainerProfileTargetId($user);

if ($trainerId === null) {
    if ($user['role'] === 'admin') {
        setFlash('auth_error', 'Seleciona um treinador válido (ex.: trainer_manage_profile.php?trainer_id=1).');
    } else {
        setFlash('auth_error', 'Não tens permissões para editar este perfil.');
    }
    redirect(pageUrl($user['role'] === 'admin' ? 'admin_users.php' : 'trainer_dashboard.php'));
}

if (!userCanManageTrainerProfile($user, $trainerId)) {
    setFlash('auth_error', 'Não tens permissões para editar este perfil.');
    redirect(pageUrl('home.php'));
}

$trainer = fetchTrainerById($trainerId);

if ($trainer === null) {
    renderErrorPage(404, 'Treinador não encontrado', 'Este treinador não existe ou não está disponível.');
}

$errors          = [];
$success         = '';
$oldBio          = trim((string) ($trainer['bio'] ?? ''));
$oldSpecName     = '';
$oldCertName     = '';
$isAdminOverride = $user['role'] === 'admin' && (int) $user['id'] !== $trainerId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_bio') {
            $bio = validateTrainerBio((string) ($_POST['bio'] ?? ''), $errors);

            if ($bio !== null) {
                if (!updateTrainerBio($trainerId, $bio)) {
                    $errors[] = 'Não foi possível atualizar a biografia.';
                } else {
                    $success  = 'Biografia atualizada com sucesso.';
                    $oldBio   = $bio;
                    $trainer['bio'] = $bio;
                }
            } else {
                $oldBio = trim((string) ($_POST['bio'] ?? ''));
            }
        }

        if ($action === 'add_specialization') {
            $oldSpecName = trim((string) ($_POST['specialization_name'] ?? ''));

            if (addTrainerSpecialization($trainerId, $oldSpecName, $errors)) {
                $success     = 'Especialização adicionada com sucesso.';
                $oldSpecName = '';
            }
        }

        if ($action === 'delete_specialization') {
            $specId = (int) ($_POST['specialization_id'] ?? 0);
            $spec   = $specId > 0 ? fetchTrainerSpecializationById($trainerId, $specId) : null;

            if ($spec === null) {
                $errors[] = 'Especialização não encontrada.';
            } elseif (!deleteTrainerSpecialization($trainerId, $specId)) {
                $errors[] = 'Não foi possível remover a especialização.';
            } else {
                $success = 'Especialização removida com sucesso.';
            }
        }

        if ($action === 'add_certification') {
            $oldCertName = trim((string) ($_POST['certification_name'] ?? ''));

            if (addTrainerCertification($trainerId, $oldCertName, $errors)) {
                $success     = 'Certificação adicionada com sucesso.';
                $oldCertName = '';
            }
        }

        if ($action === 'delete_certification') {
            $certId = (int) ($_POST['certification_id'] ?? 0);
            $cert   = $certId > 0 ? fetchTrainerCertificationById($trainerId, $certId) : null;

            if ($cert === null) {
                $errors[] = 'Certificação não encontrada.';
            } elseif (!deleteTrainerCertification($trainerId, $certId)) {
                $errors[] = 'Não foi possível remover a certificação.';
            } else {
                $success = 'Certificação removida com sucesso.';
            }
        }
    } catch (Throwable $exception) {
        $errors[] = 'Ocorreu um erro ao guardar as alterações. Tenta novamente dentro de alguns instantes.';
        error_log('trainer_manage_profile: ' . $exception->getMessage());
    }
}

$specializations = fetchTrainerSpecializations($trainerId);
$certifications  = fetchTrainerCertifications($trainerId);
$suggestedSpecs  = fetchSuggestedSpecializationNames($trainerId);
$suggestedCerts  = fetchSuggestedCertificationNames($trainerId);
$displayName     = userDisplayName($trainer);
$publicProfileUrl = pageUrl('trainer.php?id=' . $trainerId);
$formAction = $user['role'] === 'trainer'
    ? pageUrl('trainer_manage_profile.php')
    : pageUrl('trainer_manage_profile.php?trainer_id=' . $trainerId);

$currentPage      = 'trainer';
$pageTitle        = 'Perfil Público | ' . $displayName . ' | Olympus Gold Gym';
$extraStylesheets = ['pages/trainer.css', 'pages/admin.css', 'pages/trainer-manage-profile.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
<main class="trainer-main trainer-manage-profile-main">
    <div class="trainer-container admin-container">

        <div class="trainer-header equipment-page-header">
            <div class="equipment-page-header__text">
                <h1 class="trainer-title">Perfil público <span><?= e($displayName) ?></span></h1>
                <p class="trainer-sub">
                    <?php if ($isAdminOverride): ?>
                        A editar o perfil público de outro treinador.
                    <?php else: ?>
                        Atualiza a informação visível no teu perfil público.
                    <?php endif; ?>
                </p>
            </div>
            <div class="equipment-page-header__actions">
                <a href="<?= e($publicProfileUrl) ?>" class="btn-admin-secondary" target="_blank" rel="noopener">
                    Ver perfil público
                </a>
                <?php if ($user['role'] === 'trainer'): ?>
                    <a href="<?= e(pageUrl('trainer_dashboard.php')) ?>" class="btn-admin-secondary">← Dashboard</a>
                <?php else: ?>
                    <a href="<?= e(pageUrl('admin_users.php')) ?>" class="btn-admin-secondary">← Utilizadores</a>
                <?php endif; ?>
            </div>
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

        <details class="admin-card admin-details" open>
            <summary class="admin-details__summary">
                <span class="admin-details__title">Biografia</span>
            </summary>
            <div class="admin-details__body">
                <form method="POST" action="<?= e($formAction) ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="update_bio">

                    <div class="input-group">
                        <label for="bio">Biografia *</label>
                        <textarea id="bio"
                                  name="bio"
                                  rows="6"
                                  required
                                  minlength="<?= TRAINER_BIO_MIN_LENGTH ?>"
                                  maxlength="<?= TRAINER_BIO_MAX_LENGTH ?>"
                                  placeholder="Descreve a tua experiência, abordagem de treino e áreas de foco…"><?= e($oldBio) ?></textarea>
                        <small class="field-hint">Mínimo <?= TRAINER_BIO_MIN_LENGTH ?> caracteres.</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-admin-primary">Guardar biografia</button>
                    </div>
                </form>
            </div>
        </details>

        <details class="admin-card admin-details" open>
            <summary class="admin-details__summary">
                <span class="admin-details__title">
                    Especializações
                    <span class="badge-count"><?= count($specializations) ?></span>
                </span>
            </summary>
            <div class="admin-details__body">
                <form method="POST" action="<?= e($formAction) ?>" class="trainer-inline-add-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add_specialization">

                    <div class="trainer-inline-add-form__row">
                        <div class="input-group trainer-inline-add-form__field">
                            <label for="specialization_name">Especialização *</label>
                            <input type="text"
                                   id="specialization_name"
                                   name="specialization_name"
                                   list="specialization-suggestions"
                                   required
                                   maxlength="<?= TRAINER_ITEM_NAME_MAX_LENGTH ?>"
                                   placeholder="Escolhe da lista ou escreve uma nova…"
                                   value="<?= e($oldSpecName) ?>">
                            <datalist id="specialization-suggestions">
                                <?php foreach ($suggestedSpecs as $specName): ?>
                                    <option value="<?= e($specName) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <small class="field-hint">Sugestões de especializações já usadas no ginásio; podes criar uma nova.</small>
                        </div>
                        <button type="submit" class="btn-admin-primary trainer-inline-add-form__btn">➕ Adicionar</button>
                    </div>
                </form>

                <?php if ($specializations === []): ?>
                    <p class="trainer-profile-manage-empty">Ainda não tens especializações registadas.</p>
                <?php else: ?>
                    <ul class="trainer-manage-list">
                        <?php foreach ($specializations as $spec): ?>
                            <li class="trainer-manage-list__item">
                                <span><?= e((string) $spec['name']) ?></span>
                                <form method="POST"
                                      action="<?= e($formAction) ?>"
                                      class="inline-form"
                                      data-confirm="<?= e('Remover a especialização «' . (string) $spec['name'] . '»?') ?>"
                                      data-confirm-title="Remover especialização"
                                      data-confirm-ok="Remover">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete_specialization">
                                    <input type="hidden" name="specialization_id" value="<?= (int) $spec['id'] ?>">
                                    <button type="submit" class="btn-table-delete">Remover</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </details>

        <details class="admin-card admin-details" open>
            <summary class="admin-details__summary">
                <span class="admin-details__title">
                    Certificações
                    <span class="badge-count"><?= count($certifications) ?></span>
                </span>
            </summary>
            <div class="admin-details__body">
                <form method="POST" action="<?= e($formAction) ?>" class="trainer-inline-add-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add_certification">

                    <div class="trainer-inline-add-form__row">
                        <div class="input-group trainer-inline-add-form__field">
                            <label for="certification_name">Certificação *</label>
                            <input type="text"
                                   id="certification_name"
                                   name="certification_name"
                                   list="certification-suggestions"
                                   required
                                   maxlength="<?= TRAINER_ITEM_NAME_MAX_LENGTH ?>"
                                   placeholder="Escolhe da lista ou escreve uma nova…"
                                   value="<?= e($oldCertName) ?>">
                            <datalist id="certification-suggestions">
                                <?php foreach ($suggestedCerts as $certName): ?>
                                    <option value="<?= e($certName) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <small class="field-hint">Sugestões de certificações já usadas no ginásio; podes criar uma nova.</small>
                        </div>
                        <button type="submit" class="btn-admin-primary trainer-inline-add-form__btn">➕ Adicionar</button>
                    </div>
                </form>

                <?php if ($certifications === []): ?>
                    <p class="trainer-profile-manage-empty">Ainda não tens certificações registadas.</p>
                <?php else: ?>
                    <ul class="trainer-manage-list">
                        <?php foreach ($certifications as $cert): ?>
                            <li class="trainer-manage-list__item">
                                <span><?= e((string) $cert['name']) ?></span>
                                <form method="POST"
                                      action="<?= e($formAction) ?>"
                                      class="inline-form"
                                      data-confirm="<?= e('Remover a certificação «' . (string) $cert['name'] . '»?') ?>"
                                      data-confirm-title="Remover certificação"
                                      data-confirm-ok="Remover">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete_certification">
                                    <input type="hidden" name="certification_id" value="<?= (int) $cert['id'] ?>">
                                    <button type="submit" class="btn-table-delete">Remover</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </details>

    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>
