(function () {
    document.querySelectorAll('[data-utilization]').forEach(function (bar) {
        var value = parseInt(bar.getAttribute('data-utilization'), 10);
        if (Number.isNaN(value)) {
            value = 0;
        }
        value = Math.max(0, Math.min(100, value));
        bar.style.width = value + '%';
    });
})();
