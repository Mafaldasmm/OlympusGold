(function () {
    const MOBILE_QUERY = '(max-width: 768px)';

    function applyMobileCollapse() {
        const mq = window.matchMedia(MOBILE_QUERY);

        document.querySelectorAll('[data-mobile-collapse]').forEach(function (panel) {
            const openOnDesktop = panel.getAttribute('data-open-desktop') === 'true';

            function sync() {
                if (mq.matches) {
                    if (!panel.hasAttribute('data-user-toggled')) {
                        panel.removeAttribute('open');
                    }
                } else if (openOnDesktop) {
                    panel.setAttribute('open', '');
                }
            }

            panel.addEventListener('toggle', function () {
                panel.setAttribute('data-user-toggled', '1');
            });

            sync();
            mq.addEventListener('change', sync);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyMobileCollapse);
    } else {
        applyMobileCollapse();
    }
})();
