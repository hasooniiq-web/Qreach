// Navigation chrome: the mobile drawer, the collapsible desktop sidebar (with
// tooltips in icon-only mode), the "/" search shortcut and the page-load
// progress bar. No inline handlers — the CSP is `script-src 'self'`.

const html = document.documentElement;

// ---- Mobile drawer ---------------------------------------------------
function setMobileNavOpen(open) {
    const nav = document.getElementById('mobile-nav');
    const backdrop = document.getElementById('mobile-nav-backdrop');
    if (!nav || !backdrop) return;

    // Hidden position depends on reading direction: off the start edge.
    nav.classList.toggle('-translate-x-full', !open);
    nav.classList.toggle('rtl:translate-x-full', !open);
    nav.classList.toggle('translate-x-0', open);
    backdrop.hidden = !open;
    document.body.style.overflow = open ? 'hidden' : '';
}

document.getElementById('mobile-nav-open')?.addEventListener('click', () => setMobileNavOpen(true));
document.getElementById('mobile-nav-close')?.addEventListener('click', () => setMobileNavOpen(false));
document.getElementById('mobile-nav-backdrop')?.addEventListener('click', () => setMobileNavOpen(false));
document.querySelectorAll('#mobile-nav .sb-item').forEach((a) => a.addEventListener('click', () => setMobileNavOpen(false)));

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setMobileNavOpen(false);
});

// ---- Desktop sidebar: full / icon-only --------------------------------
const SIDEBAR_KEY = 'admin.sidebar.collapsed';
const sidebarToggle = document.getElementById('sidebar-toggle');

function setCollapsed(collapsed) {
    if (collapsed) html.setAttribute('data-sidebar', 'collapsed');
    else html.removeAttribute('data-sidebar');
    sidebarToggle?.setAttribute('aria-expanded', String(!collapsed));
}

try {
    setCollapsed(localStorage.getItem(SIDEBAR_KEY) === '1');
} catch (e) {
    // Storage blocked: the sidebar simply stays open.
}

sidebarToggle?.addEventListener('click', () => {
    const collapsed = html.getAttribute('data-sidebar') !== 'collapsed';
    setCollapsed(collapsed);
    try {
        localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');
    } catch (e) {
        // Not persisted; still toggles for this page view.
    }
});

// Tooltips for icon-only mode. The nav scrolls, so a CSS tooltip would be
// clipped; one fixed element is positioned next to the hovered item instead.
const tip = document.createElement('div');
tip.id = 'sb-tooltip';
tip.setAttribute('role', 'tooltip');
document.body.appendChild(tip);

function showTip(el) {
    if (html.getAttribute('data-sidebar') !== 'collapsed' || !el.closest('#desktop-sidebar')) return;
    tip.textContent = el.dataset.tip || '';
    const r = el.getBoundingClientRect();
    const rtl = getComputedStyle(html).direction === 'rtl';
    tip.style.top = `${r.top + r.height / 2}px`;
    tip.style.transform = 'translateY(-50%)';
    if (rtl) {
        tip.style.left = 'auto';
        tip.style.right = `${window.innerWidth - r.left + 10}px`;
    } else {
        tip.style.right = 'auto';
        tip.style.left = `${r.right + 10}px`;
    }
    tip.setAttribute('data-show', '');
}

function hideTip() {
    tip.removeAttribute('data-show');
}

document.querySelectorAll('#desktop-sidebar [data-tip]').forEach((el) => {
    el.addEventListener('mouseenter', () => showTip(el));
    el.addEventListener('focus', () => showTip(el));
    el.addEventListener('mouseleave', hideTip);
    el.addEventListener('blur', hideTip);
});

// ---- "/" focuses the global search ------------------------------------
document.addEventListener('keydown', (e) => {
    const target = e.target;
    const typing = target instanceof HTMLElement && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
    if (e.key === '/' && !typing && !e.ctrlKey && !e.metaKey) {
        const search = document.getElementById('global-search');
        if (search) {
            e.preventDefault();
            search.focus();
        }
    }
});

// ---- Page-load progress bar -------------------------------------------
const bar = document.getElementById('nav-progress');

function startProgress() {
    if (!bar) return;
    bar.removeAttribute('data-active');
    // Force a reflow so the animation restarts.
    void bar.offsetWidth;
    bar.setAttribute('data-active', '');
    document.body.setAttribute('data-navigating', '');
}

document.addEventListener('click', (e) => {
    const a = e.target instanceof Element ? e.target.closest('a[href]') : null;
    if (!a || a.target === '_blank' || a.hasAttribute('download') || e.metaKey || e.ctrlKey || e.shiftKey) return;
    const href = a.getAttribute('href') || '';
    if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
    if (a.origin !== location.origin) return;
    // A file download never replaces the page, so no navigation ever finishes and
    // the dimmed "loading" state would stay on until a manual refresh. Exports are
    // marked by their address (/export...) or by data-no-progress.
    if (a.hasAttribute('data-no-progress') || /\/export(\/|$|\?)/.test(a.pathname)) return;
    startProgress();
});

document.addEventListener('submit', (e) => {
    // Decided after every other submit listener has run: the confirmation dialog
    // (admin-ui.js) cancels the submit, and a page that is not really navigating
    // must not be left dimmed.
    setTimeout(() => {
        if (!e.defaultPrevented) startProgress();
    }, 0);
});

// Belt and braces: whenever a confirmation dialog closes, the page is interactive again.
document.getElementById('confirm-dialog')?.addEventListener('close', () => {
    bar?.removeAttribute('data-active');
    document.body.removeAttribute('data-navigating');
});

// Coming back through the back/forward cache must not leave the bar running.
window.addEventListener('pageshow', () => {
    bar?.removeAttribute('data-active');
    document.body.removeAttribute('data-navigating');
});
