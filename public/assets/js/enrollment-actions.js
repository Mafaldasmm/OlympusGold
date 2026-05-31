(function () {
    const config = window.__ENROLLMENT_CONFIG;
    if (!config) {
        return;
    }

    const bookUrl = config.bookUrl;
    const cancelUrl = config.cancelUrl;
    const waitlistJoinUrl = config.waitlistJoinUrl;
    const waitlistLeaveUrl = config.waitlistLeaveUrl;
    const redirectValue = config.redirect || 'schedule.php';
    const bookButtonLabel = config.bookButtonLabel || 'Inscrever';
    const cardSelector = config.cardSelector || '.class-card';
    const actionsSelector = config.actionsSelector || '.class-enrollment-actions';
    const spotsSelector = config.spotsSelector || '.class-spots-value';

    let submitting = false;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function notify(type, message) {
        if (typeof window.showSiteToast === 'function') {
            window.showSiteToast(type, message);
            return;
        }
    }

    function updateAllCsrfTokens(token) {
        if (!token) {
            return;
        }
        if (typeof window.refreshCsrfToken === 'function') {
            window.refreshCsrfToken(token);
            return;
        }
        document.querySelectorAll('.enrollment-csrf').forEach(function (input) {
            input.value = token;
        });
    }

    function buildEnrollmentHtml(state, csrfToken) {
        const classId = state.classId;
        const csrf = escapeHtml(csrfToken);
        const redirectField = '<input type="hidden" name="redirect" value="' + escapeHtml(redirectValue) + '">';

        if (state.isEnrolled) {
            return '<form class="enrollment-form" method="POST" action="' + escapeHtml(cancelUrl) + '" data-enrollment-action="cancel">' +
                '<input type="hidden" name="csrf_token" class="enrollment-csrf" value="' + csrf + '">' +
                '<input type="hidden" name="class_id" value="' + classId + '">' + redirectField +
                '<button type="submit" class="btn-primary btn-cancel">Cancelar Inscrição</button></form>';
        }

        if (state.isOnWaitlist) {
            const pos = state.waitlistPosition || '?';
            return '<p class="waitlist-status">Lista de espera — posição <strong>' + escapeHtml(String(pos)) + '</strong></p>' +
                '<form class="enrollment-form" method="POST" action="' + escapeHtml(waitlistLeaveUrl) + '" data-enrollment-action="waitlist-leave">' +
                '<input type="hidden" name="csrf_token" class="enrollment-csrf" value="' + csrf + '">' +
                '<input type="hidden" name="class_id" value="' + classId + '">' + redirectField +
                '<button type="submit" class="btn-primary btn-cancel">Sair da lista de espera</button></form>';
        }

        if (state.isFull) {
            return '<form class="enrollment-form" method="POST" action="' + escapeHtml(waitlistJoinUrl) + '" data-enrollment-action="waitlist-join">' +
                '<input type="hidden" name="csrf_token" class="enrollment-csrf" value="' + csrf + '">' +
                '<input type="hidden" name="class_id" value="' + classId + '">' + redirectField +
                '<button type="submit" class="btn-primary btn-waitlist">Entrar na lista de espera</button></form>';
        }

        return '<form class="enrollment-form" method="POST" action="' + escapeHtml(bookUrl) + '" data-enrollment-action="book">' +
            '<input type="hidden" name="csrf_token" class="enrollment-csrf" value="' + csrf + '">' +
            '<input type="hidden" name="class_id" value="' + classId + '">' + redirectField +
            '<button type="submit" class="btn-primary">' + escapeHtml(bookButtonLabel) + '</button></form>';
    }

    function updateSpots(card, state) {
        const spotsValue = card.querySelector(spotsSelector);
        if (!spotsValue) {
            return;
        }

        if (state.isFull) {
            spotsValue.innerHTML = '<span class="full">CHEIO</span>';
        } else {
            spotsValue.textContent = state.spots + ' / ' + state.capacity;
        }
    }

    function updateCard(card, state, csrfToken) {
        if (card.dataset) {
            card.dataset.capacity = String(state.capacity);
            card.dataset.enrolled = String(state.enrolledCount);
        }

        updateSpots(card, state);

        const actions = card.querySelector(actionsSelector);
        if (actions) {
            actions.innerHTML = buildEnrollmentHtml(state, csrfToken);
            bindEnrollmentForms(actions);
        }
    }

    async function handleEnrollmentSubmit(event) {
        event.preventDefault();

        if (submitting) {
            return;
        }

        const form = event.currentTarget;
        const card = form.closest(cardSelector);
        if (!card) {
            return;
        }

        submitting = true;
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                notify('error', 'Resposta inesperada do servidor. Recarrega a página e tenta novamente.');
                return;
            }

            const payload = await response.json();

            if (payload.csrfToken) {
                updateAllCsrfTokens(payload.csrfToken);
            }

            if (!response.ok || !payload.success) {
                notify('error', payload.message || 'Não foi possível concluir a operação.');
                if (
                    payload.classId &&
                    card.dataset.classId === String(payload.classId) &&
                    typeof payload.capacity !== 'undefined'
                ) {
                    updateCard(card, payload, payload.csrfToken || '');
                }
                return;
            }

            notify('success', payload.message);
            updateCard(card, payload, payload.csrfToken || '');

            if (typeof config.onSuccess === 'function') {
                config.onSuccess(payload, card);
            }
        } catch (error) {
            notify('error', 'Erro de ligação. Verifica a rede e tenta novamente.');
        } finally {
            submitting = false;
            if (button) {
                button.disabled = false;
            }
        }
    }

    function bindEnrollmentForms(root) {
        root.querySelectorAll('.enrollment-form').forEach(function (form) {
            form.addEventListener('submit', handleEnrollmentSubmit);
        });
    }

    document.querySelectorAll(actionsSelector).forEach(bindEnrollmentForms);
})();
