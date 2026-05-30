<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

$user = requireLogin();

$errors = getFlash('form_errors', []);
$old = getFlash('form_old', []);
$success = getFlash('profile_success', '');
$info = getFlash('profile_info', '');
$cancelled = isset($_GET['cancelled']) && $_GET['cancelled'] === '1';
$displayNameFlash = getFlash('profile_display_name', '');

$formName = (string) ($old['name'] ?? $user['name'] ?? '');
$formUsername = (string) ($old['username'] ?? $user['username']);
$photoUrl = profilePhotoUrl($user);
$navDisplayName = userDisplayName($user);
$hasInitialFeedback = $success !== '' || $info !== '' || $cancelled || $errors !== [];

$currentPage = 'profile';
$pageTitle = 'Perfil do Utilizador - Olympus Gold Gym';
$extraStylesheets = ['pages/profile.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';

?>
    <main class="profile-container">
        <div class="profile-header">
            <h1>EDITAR <span class="highlight">PERFIL</span></h1>
            <p>Gira as suas informações pessoais.</p>
        </div>

        <form id="profile-form"
              action="<?= e(pageUrl('update_profile.php')) ?>"
              method="POST"
              enctype="multipart/form-data"
              novalidate
              lang="pt-PT">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="card-grid">

                <article class="card featured">
                    <h2>Foto de Perfil</h2>
                    <div class="profile-photo-section">
                        <img src="<?= e($photoUrl) ?>"
                             alt="Foto de perfil"
                             class="profile-img"
                             id="profile-photo-preview"
                             width="150"
                             height="150">
                        <div class="file-upload">
                            <input type="file"
                                   id="profile_photo"
                                   name="profile_photo"
                                   class="file-upload-input"
                                   accept="image/jpeg,image/png,image/webp"
                                   aria-describedby="profile-photo-filename">
                            <label for="profile_photo" class="file-upload-button">Escolher ficheiro</label>
                            <span class="file-upload-name" id="profile-photo-filename">Nenhum ficheiro selecionado</span>
                        </div>
                    </div>
                </article>

                <article class="card featured">
                    <h2>Dados Pessoais</h2>
                    <div class="input-group">
                        <label for="name">Nome</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Ex: André Restivo"
                            required
                            maxlength="100"
                            value="<?= e($formName) ?>"
                            data-msg-required="Preenche o nome."
                            data-msg-too-long="O nome é demasiado longo."
                        >
                    </div>
                    <div class="input-group">
                        <label for="username">Nome de Utilizador</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="utilizador123"
                            required
                            maxlength="30"
                            pattern="[A-Za-z0-9_.-]{3,30}"
                            value="<?= e($formUsername) ?>"
                            data-msg-required="Preenche o nome de utilizador."
                            data-msg-pattern="O nome de utilizador deve ter 3–30 caracteres (letras, números, ponto, underscore ou hífen)."
                        >
                    </div>
                </article>

                <article class="card featured">
                    <h2>Segurança</h2>
                    <div class="input-group">
                        <label for="password">Nova Palavra Passe</label>
                        <input type="password" id="password" name="password" placeholder="Introduza para alterar" autocomplete="new-password">
                        <small>Deixe em branco caso não deseje alterar.</small>
                    </div>
                </article>

            </div>

            <div class="form-actions-wrap">
                <div id="profile-alerts" class="profile-feedback-slot<?= $hasInitialFeedback ? ' is-visible' : '' ?>" aria-live="polite">
<?php if ($success !== ''): ?>
                    <div class="alert alert-success">
                        <p><?= e($success) ?></p>
                    </div>
<?php endif; ?>

<?php if ($info !== ''): ?>
                    <div class="alert alert-info">
                        <p><?= e($info) ?></p>
                    </div>
<?php endif; ?>

<?php if ($cancelled): ?>
                    <div class="alert alert-info">
                        <p>Alterações canceladas. Os dados guardados foram restaurados.</p>
                    </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
                    <div class="alert alert-error">
<?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
<?php endforeach; ?>
                    </div>
<?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save" id="profile-save">Guardar Alterações</button>
                    <button type="button" class="btn-cancel is-hidden" id="profile-cancel">Cancelar</button>
                </div>
            </div>

        </form>
    </main>

    <script>
        (function () {
            const form = document.getElementById('profile-form');
            const alerts = document.getElementById('profile-alerts');
            const greeting = document.getElementById('nav-user-greeting');
            const photoPreview = document.getElementById('profile-photo-preview');
            const photoInput = document.getElementById('profile_photo');
            const cancelButton = document.getElementById('profile-cancel');
            const saveButton = document.getElementById('profile-save');
            const csrfInput = document.getElementById('csrf_token');
            const nameInput = document.getElementById('name');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');

            const initialDisplayName = <?= json_encode($navDisplayName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            const flashDisplayName = <?= json_encode($displayNameFlash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

            let savedState = {
                name: nameInput ? nameInput.value : '',
                username: usernameInput ? usernameInput.value : '',
                photoUrl: photoPreview ? photoPreview.src : '',
            };

            let isSubmitting = false;
            let photoPreviewObjectUrl = null;
            let alertDismissTimer = null;
            const AUTO_DISMISS_MS = 2000;

            function setGreeting(name) {
                if (!greeting || !name) {
                    return;
                }
                greeting.textContent = 'Olá, ' + name;
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function getValidationMessage(input) {
                if (input.validity.valueMissing) {
                    return input.dataset.msgRequired || 'Preenche este campo.';
                }
                if (input.validity.patternMismatch) {
                    return input.dataset.msgPattern || 'O valor introduzido não é válido.';
                }
                if (input.validity.tooLong) {
                    return input.dataset.msgTooLong || 'O valor é demasiado longo.';
                }
                return 'O valor introduzido não é válido.';
            }

            function validateFormFields() {
                const fields = form.querySelectorAll('input[required], input[pattern]');
                let firstInvalid = null;

                fields.forEach(function (field) {
                    if (!field.checkValidity()) {
                        if (!firstInvalid) {
                            firstInvalid = field;
                        }
                    }
                });

                if (firstInvalid) {
                    showAlert('error', getValidationMessage(firstInvalid), { autoDismiss: false });
                    firstInvalid.focus({ preventScroll: false });
                    return false;
                }

                return true;
            }

            function scrollFeedbackIntoView() {
                if (!alerts || !saveButton) {
                    return;
                }
                saveButton.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function showAlert(type, message, options) {
                const opts = options || {};
                const autoDismiss = opts.autoDismiss !== false
                    && (type === 'success' || type === 'info');

                if (alertDismissTimer) {
                    clearTimeout(alertDismissTimer);
                    alertDismissTimer = null;
                }

                alerts.innerHTML = '<div class="alert alert-' + type + '"><p>' + escapeHtml(message) + '</p></div>';
                alerts.classList.add('is-visible');
                scrollFeedbackIntoView();

                if (autoDismiss) {
                    alertDismissTimer = setTimeout(function () {
                        if (alerts) {
                            alerts.innerHTML = '';
                            alerts.classList.remove('is-visible');
                        }
                        alertDismissTimer = null;
                    }, AUTO_DISMISS_MS);
                }
            }

            function scheduleDismissForExistingAlerts() {
                if (!alerts) {
                    return;
                }

                const existing = alerts.querySelector('.alert-success, .alert-info');
                if (!existing) {
                    return;
                }

                alerts.classList.add('is-visible');

                if (alertDismissTimer) {
                    clearTimeout(alertDismissTimer);
                }

                alertDismissTimer = setTimeout(function () {
                    alerts.innerHTML = '';
                    alerts.classList.remove('is-visible');
                    alertDismissTimer = null;
                }, AUTO_DISMISS_MS);
            }

            function updateCsrfToken(token) {
                if (csrfInput && token) {
                    csrfInput.value = token;
                }
            }

            function readFormState() {
                return {
                    name: nameInput ? nameInput.value.trim() : '',
                    username: usernameInput ? usernameInput.value.trim() : '',
                    password: passwordInput ? passwordInput.value : '',
                    hasNewPhoto: !!(photoInput && photoInput.files && photoInput.files.length > 0),
                };
            }

            function isDirty() {
                const current = readFormState();
                return current.name !== savedState.name
                    || current.username !== savedState.username
                    || current.password !== ''
                    || current.hasNewPhoto;
            }

            function updateCancelVisibility() {
                if (!cancelButton) {
                    return;
                }
                cancelButton.classList.toggle('is-hidden', !isDirty());
            }

            function captureSavedStateFromForm(photoUrl) {
                savedState = {
                    name: nameInput ? nameInput.value.trim() : '',
                    username: usernameInput ? usernameInput.value.trim() : '',
                    photoUrl: photoUrl || (photoPreview ? photoPreview.src : ''),
                };
            }

            function resetFormToSaved() {
                if (nameInput) {
                    nameInput.value = savedState.name;
                }
                if (usernameInput) {
                    usernameInput.value = savedState.username;
                }
                if (passwordInput) {
                    passwordInput.value = '';
                }
                if (photoInput) {
                    photoInput.value = '';
                }
                updatePhotoFilenameLabel();
                if (photoPreview) {
                    photoPreview.src = savedState.photoUrl;
                }
                if (photoPreviewObjectUrl) {
                    URL.revokeObjectURL(photoPreviewObjectUrl);
                    photoPreviewObjectUrl = null;
                }
                updateCancelVisibility();
            }

            function handleSaveResponse(payload) {
                if (payload.csrfToken) {
                    updateCsrfToken(payload.csrfToken);
                }

                const alertType = payload.alreadySaved ? 'info' : 'success';
                showAlert(alertType, payload.message);

                if (payload.displayName) {
                    setGreeting(payload.displayName);
                }

                const nextPhotoUrl = payload.profilePhotoUrl || (photoPreview ? photoPreview.src : '');
                if (photoPreview && payload.profilePhotoUrl) {
                    photoPreview.src = payload.profilePhotoUrl;
                }

                if (passwordInput) {
                    passwordInput.value = '';
                }
                if (photoInput) {
                    photoInput.value = '';
                }
                updatePhotoFilenameLabel();
                if (photoPreviewObjectUrl) {
                    URL.revokeObjectURL(photoPreviewObjectUrl);
                    photoPreviewObjectUrl = null;
                }

                captureSavedStateFromForm(nextPhotoUrl);
                updateCancelVisibility();
            }

            if (flashDisplayName) {
                setGreeting(flashDisplayName);
            }

            scheduleDismissForExistingAlerts();
            updateCancelVisibility();

            [nameInput, usernameInput, passwordInput].forEach(function (input) {
                if (!input) {
                    return;
                }
                input.addEventListener('input', updateCancelVisibility);
                input.addEventListener('change', updateCancelVisibility);
            });

            const photoFilename = document.getElementById('profile-photo-filename');

            function updatePhotoFilenameLabel() {
                if (!photoFilename) {
                    return;
                }
                const file = photoInput && photoInput.files && photoInput.files[0];
                photoFilename.textContent = file ? file.name : 'Nenhum ficheiro selecionado';
            }

            if (photoInput) {
                photoInput.addEventListener('change', function () {
                    const file = photoInput.files && photoInput.files[0];
                    if (file && photoPreview) {
                        if (photoPreviewObjectUrl) {
                            URL.revokeObjectURL(photoPreviewObjectUrl);
                        }
                        photoPreviewObjectUrl = URL.createObjectURL(file);
                        photoPreview.src = photoPreviewObjectUrl;
                    }
                    updatePhotoFilenameLabel();
                    updateCancelVisibility();
                });
            }

            if (cancelButton) {
                cancelButton.addEventListener('click', function () {
                    resetFormToSaved();
                    showAlert('info', 'Alterações descartadas.', { autoDismiss: false });
                });
            }

            if (!form) {
                return;
            }

            form.addEventListener('submit', async function (event) {
                if (!window.fetch || !window.FormData) {
                    return;
                }

                event.preventDefault();

                if (isSubmitting) {
                    return;
                }

                if (!validateFormFields()) {
                    return;
                }

                if (!isDirty()) {
                    showAlert('info', 'Estas informações já foram guardadas.');
                    return;
                }

                isSubmitting = true;
                if (saveButton) {
                    saveButton.disabled = true;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        showAlert('error', 'Resposta inesperada do servidor. Recarrega a página e tenta novamente.');
                        return;
                    }

                    const payload = await response.json();

                    if (payload.csrfToken) {
                        updateCsrfToken(payload.csrfToken);
                    }

                    if (!response.ok || !payload.success) {
                        const messages = Array.isArray(payload.errors)
                            ? payload.errors
                            : [payload.message || 'Não foi possível atualizar o perfil.'];
                        showAlert('error', messages.join(' '));
                        return;
                    }

                    handleSaveResponse(payload);
                } catch (error) {
                    showAlert('error', 'Erro de ligação. Verifica a rede e tenta novamente.');
                } finally {
                    isSubmitting = false;
                    if (saveButton) {
                        saveButton.disabled = false;
                    }
                }
            });
        })();
    </script>

<?php require INCLUDES_PATH . '/footer.php'; ?>
