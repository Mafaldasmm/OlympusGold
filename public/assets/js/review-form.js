(function () {
    const form = document.getElementById('class-review-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

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
                if (typeof window.showSiteToast === 'function') {
                    window.showSiteToast('error', 'Resposta inesperada. Recarrega a página.');
                }
                return;
            }

            const payload = await response.json();

            if (payload.csrfToken && typeof window.refreshCsrfToken === 'function') {
                window.refreshCsrfToken(payload.csrfToken);
            }

            if (!response.ok || !payload.success) {
                if (typeof window.showSiteToast === 'function') {
                    window.showSiteToast('error', payload.message || 'Não foi possível enviar a avaliação.');
                }
                return;
            }

            if (typeof window.showSiteToast === 'function') {
                window.showSiteToast('success', payload.message);
            }

            const section = form.closest('.review-form-section');
            if (section) {
                section.innerHTML = '<p class="already-reviewed">✓ Já avaliaste esta aula. Obrigado pelo teu feedback!</p>';
            }
        } catch (error) {
            if (typeof window.showSiteToast === 'function') {
                window.showSiteToast('error', 'Erro de ligação. Tenta novamente.');
            }
        } finally {
            if (button) {
                button.disabled = false;
            }
        }
    });
})();
