(function () {
    const dialog = document.getElementById('site-confirm');
    if (!dialog) {
        return;
    }

    const titleEl = document.getElementById('site-confirm-title');
    const messageEl = document.getElementById('site-confirm-message');
    const okButton = dialog.querySelector('[data-confirm-ok]');
    const cancelButton = dialog.querySelector('[data-confirm-cancel]');
    const backdrop = dialog.querySelector('[data-confirm-dismiss]');

    let pendingResolve = null;
    let lastFocused = null;

    function closeDialog(result) {
        dialog.hidden = true;
        dialog.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('site-confirm-open');

        if (pendingResolve) {
            const resolve = pendingResolve;
            pendingResolve = null;
            resolve(result);
        }

        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    function openDialog(title, message) {
        return new Promise(function (resolve) {
            pendingResolve = resolve;
            lastFocused = document.activeElement;

            if (titleEl) {
                titleEl.textContent = title || 'Confirmar';
            }
            if (messageEl) {
                messageEl.textContent = message || '';
            }

            dialog.hidden = false;
            dialog.setAttribute('aria-hidden', 'false');
            document.body.classList.add('site-confirm-open');

            if (cancelButton) {
                cancelButton.focus();
            }
        });
    }

    if (okButton) {
        okButton.addEventListener('click', function () {
            closeDialog(true);
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            closeDialog(false);
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            closeDialog(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (dialog.hidden) {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            closeDialog(false);
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = form.getAttribute('data-confirm');
        if (!message) {
            return;
        }

        if (form.dataset.confirmBypass === '1') {
            delete form.dataset.confirmBypass;
            return;
        }

        event.preventDefault();

        const title = form.getAttribute('data-confirm-title') || 'Confirmar';
        const okLabel = form.getAttribute('data-confirm-ok') || 'Confirmar';

        if (okButton) {
            okButton.textContent = okLabel;
        }

        openDialog(title, message).then(function (confirmed) {
            if (!confirmed) {
                return;
            }
            form.dataset.confirmBypass = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }, true);
})();
