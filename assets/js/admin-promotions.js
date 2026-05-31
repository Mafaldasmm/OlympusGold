(function () {
    const classPanel = document.querySelector('[data-promotion-panel="class"]');
    const trainerPanel = document.querySelector('[data-promotion-panel="trainer"]');
    const classCountEl = document.getElementById('promotions-class-count');
    const trainerCountEl = document.getElementById('promotions-trainer-count');

    if (!classPanel && !trainerPanel) {
        return;
    }

    let submitting = false;

    function notify(type, message) {
        if (typeof window.showSiteToast === 'function') {
            window.showSiteToast(type, message);
        }
    }

    function updateCsrf(token) {
        if (token && typeof window.refreshCsrfToken === 'function') {
            window.refreshCsrfToken(token);
        }
    }

    function updateCount(el, count, max) {
        if (el) {
            el.textContent = count + '/' + max;
        }
    }

    function syncClassButtons(count, max) {
        if (!classPanel) {
            return;
        }
        const atLimit = count >= max;
        classPanel.querySelectorAll('[data-promotion-row][data-promotion-type="class"]').forEach(function (row) {
            const isFeatured = row.getAttribute('data-featured') === '1';
            const form = row.querySelector('.admin-promotion-form');
            const button = form ? form.querySelector('button[type="submit"]') : null;
            const featuredInput = form ? form.querySelector('input[name="featured"]') : null;
            if (!button || !featuredInput) {
                return;
            }
            row.classList.toggle('row--featured', isFeatured);
            button.textContent = isFeatured ? 'Remover' : 'Destacar';
            featuredInput.value = isFeatured ? '0' : '1';
            button.disabled = !isFeatured && atLimit;
            button.title = !isFeatured && atLimit ? 'Limite de destaques atingido' : '';
        });
    }

    function syncTrainerButtons(count, max) {
        if (!trainerPanel) {
            return;
        }
        const atLimit = count >= max;
        trainerPanel.querySelectorAll('[data-promotion-row][data-promotion-type="trainer"]').forEach(function (row) {
            const isFeatured = row.getAttribute('data-featured') === '1';
            const isActive = row.getAttribute('data-active') === '1';
            const form = row.querySelector('.admin-promotion-form');
            const button = form ? form.querySelector('button[type="submit"]') : null;
            const featuredInput = form ? form.querySelector('input[name="featured"]') : null;
            if (!button || !featuredInput) {
                return;
            }
            row.classList.toggle('row--featured', isFeatured);
            button.textContent = isFeatured ? 'Remover' : 'Destacar';
            featuredInput.value = isFeatured ? '0' : '1';
            button.disabled = !isActive || (!isFeatured && atLimit);
            if (!isActive) {
                button.title = 'Treinador inativo';
            } else if (!isFeatured && atLimit) {
                button.title = 'Limite de destaques atingido';
            } else {
                button.title = '';
            }
        });
    }

    function readCount(el, fallbackMax) {
        if (!el) {
            return { count: 0, max: fallbackMax };
        }
        const parts = el.textContent.split('/');
        return {
            count: parseInt(parts[0], 10) || 0,
            max: parseInt(parts[1], 10) || fallbackMax,
        };
    }

    function applyPayload(payload) {
        updateCsrf(payload.csrfToken);
        updateCount(classCountEl, payload.featuredClassCount, payload.maxFeaturedClasses);
        updateCount(trainerCountEl, payload.featuredTrainerCount, payload.maxFeaturedTrainers);

        if (payload.entityType && payload.entityId) {
            const selector = '[data-promotion-row][data-promotion-type="' + payload.entityType + '"][data-entity-id="' + payload.entityId + '"]';
            const row = document.querySelector(selector);
            if (row) {
                row.setAttribute('data-featured', payload.featured ? '1' : '0');
            }
        }

        syncClassButtons(payload.featuredClassCount, payload.maxFeaturedClasses);
        syncTrainerButtons(payload.featuredTrainerCount, payload.maxFeaturedTrainers);
    }

    function refreshAllButtons() {
        const classCounts = readCount(classCountEl, 3);
        const trainerCounts = readCount(trainerCountEl, 3);
        syncClassButtons(classCounts.count, classCounts.max);
        syncTrainerButtons(trainerCounts.count, trainerCounts.max);
    }

    function isPromotionForm(form) {
        if (!(form instanceof HTMLFormElement)) {
            return false;
        }
        const actionInput = form.querySelector('input[name="action"]');
        return actionInput !== null
            && (actionInput.value === 'toggle_class_featured' || actionInput.value === 'toggle_trainer_featured');
    }

    function resolveActionUrl(form) {
        const action = form.getAttribute('action') || 'action_admin_promotion.php';
        try {
            return new URL(action, window.location.href).href;
        } catch (error) {
            return action;
        }
    }

    async function submitPromotionForm(form) {
        if (submitting) {
            return;
        }

        submitting = true;
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        try {
            const response = await fetch(resolveActionUrl(form), {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const raw = await response.text();
            let payload;

            try {
                payload = JSON.parse(raw);
            } catch (parseError) {
                notify('error', 'Resposta inesperada do servidor. Recarrega a página e tenta novamente.');
                return;
            }

            if (payload.csrfToken) {
                updateCsrf(payload.csrfToken);
            }

            if (!response.ok || !payload.success) {
                notify('error', payload.message || 'Não foi possível atualizar o destaque.');
                if (typeof payload.featuredClassCount !== 'undefined') {
                    applyPayload(payload);
                }
                return;
            }

            notify('success', payload.message);
            applyPayload(payload);
        } catch (error) {
            notify('error', 'Erro de ligação. Verifica a rede e se o servidor PHP está a correr.');
        } finally {
            submitting = false;
            refreshAllButtons();
        }
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!isPromotionForm(form)) {
            return;
        }
        event.preventDefault();
        submitPromotionForm(form);
    }, true);
})();
