// Applies the saved appearance (system | light | dark) before first paint so
// there is no flash of the wrong theme. It is an external file, not inline,
// because the site's CSP is `script-src 'self'`. The toggle in the dashboard
// header (resources/js/admin-ui.js) changes the same localStorage key.
(function () {
    var KEY = 'admin.theme';
    var mode = 'system';
    try {
        mode = localStorage.getItem(KEY) || 'system';
    } catch (e) {}

    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function apply() {
        var dark = mode === 'dark' || (mode === 'system' && media && media.matches);
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme-mode', mode);
    }

    apply();
    if (media && media.addEventListener) media.addEventListener('change', apply);
    window.__setThemeMode = function (next) {
        mode = next;
        try {
            localStorage.setItem(KEY, next);
        } catch (e) {}
        apply();
    };
})();
