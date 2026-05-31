(function () {
    const AUTO_DISMISS_MS = 2000;
    let container = null;

    function ensureContainer() {
        if (container) {
            return container;
        }

        container = document.createElement('div');
        container.className = 'site-toast-container';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
        return container;
    }

    function normalizeType(type) {
        if (type === 'success' || type === 'error' || type === 'info') {
            return type;
        }

        return 'info';
    }

    window.showSiteToast = function showSiteToast(type, message, durationMs) {
        const root = ensureContainer();
        const toast = document.createElement('div');
        const normalized = normalizeType(type);

        toast.className = 'site-toast site-toast--' + normalized;
        toast.setAttribute('role', normalized === 'error' ? 'alert' : 'status');
        toast.textContent = String(message || '');

        root.appendChild(toast);

        const dismissAfter = typeof durationMs === 'number' ? durationMs : AUTO_DISMISS_MS;

        window.setTimeout(function () {
            toast.classList.add('is-leaving');
            window.setTimeout(function () {
                toast.remove();
            }, 220);
        }, dismissAfter);

        return toast;
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-toast-on-load]').forEach(function (el) {
            const type = el.getAttribute('data-toast-type') || 'info';
            const message = el.textContent.trim();
            if (message !== '') {
                window.showSiteToast(type, message);
            }
            el.remove();
        });
    });
})();
