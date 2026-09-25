function setMobileNavOpen(open) {
    const nav = document.getElementById('mobile-nav');
    const backdrop = document.getElementById('mobile-nav-backdrop');
    if (!nav || !backdrop) return;

    nav.classList.toggle('translate-x-full', !open);
    backdrop.hidden = !open;
    document.body.style.overflow = open ? 'hidden' : '';
}

// Module scripts run after the DOM is already parsed (like `defer`), so
// DOMContentLoaded may already have fired by the time this executes —
// wiring listeners directly here is correct, no DOMContentLoaded wrapper needed.
document.getElementById('mobile-nav-open')?.addEventListener('click', () => setMobileNavOpen(true));
document.getElementById('mobile-nav-close')?.addEventListener('click', () => setMobileNavOpen(false));
document.getElementById('mobile-nav-backdrop')?.addEventListener('click', () => setMobileNavOpen(false));

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setMobileNavOpen(false);
});

// Desktop sidebar show/hide. The choice is remembered per browser; the sidebar
// and the main column scroll independently (see the admin layout).
const sidebar = document.getElementById('desktop-sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
const SIDEBAR_KEY = 'admin.sidebar.collapsed';

function setSidebarCollapsed(collapsed) {
    sidebar?.classList.toggle('lg:hidden!', collapsed);
    sidebarToggle?.setAttribute('aria-expanded', String(!collapsed));
}

try {
    setSidebarCollapsed(localStorage.getItem(SIDEBAR_KEY) === '1');
} catch (e) {
    // Storage blocked: the sidebar simply stays open.
}

sidebarToggle?.addEventListener('click', () => {
    const collapsed = !sidebar?.classList.contains('lg:hidden!');
    setSidebarCollapsed(collapsed);
    try {
        localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');
    } catch (e) {
        // Not persisted; still toggles for this page view.
    }
});