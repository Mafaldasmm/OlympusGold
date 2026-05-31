(function () {
    function syncCsrfInputs(token) {
        if (!token) {
            return;
        }

        window.__CSRF_TOKEN = token;
        document.querySelectorAll('input[name="csrf_token"]').forEach(function (input) {
            input.value = token;
        });
    }

    window.refreshCsrfToken = syncCsrfInputs;

    document.addEventListener('DOMContentLoaded', function () {
        if (window.__CSRF_TOKEN) {
            syncCsrfInputs(window.__CSRF_TOKEN);
        }

        document.querySelectorAll('.nav-form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (window.__CSRF_TOKEN) {
                    syncCsrfInputs(window.__CSRF_TOKEN);
                }
            });
        });
    });
})();
