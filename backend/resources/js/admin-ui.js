// Delegated handlers replacing the inline onchange/onsubmit attributes the
// admin views used to carry. The app's CSP is `script-src 'self'` (no
// 'unsafe-inline'), so inline event-handler attributes never ran — filter
// dropdowns silently didn't auto-submit and the "suspend account"
// confirmation never fired. Behavior is now declared with data attributes.

document.addEventListener('change', (event) => {
    const target = event.target;
    if (target instanceof HTMLElement && target.matches('[data-auto-submit]')) {
        target.form?.submit();
    }
});

const dialog = document.getElementById('confirm-dialog');
let pendingForm = null;

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || !dialog) return;

    event.preventDefault();
    pendingForm = form;

    dialog.querySelector('[data-confirm-title]').textContent = form.dataset.confirmTitle || 'تأكيد الإجراء';
    dialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
    dialog.querySelector('[data-confirm-accept]').textContent = form.dataset.confirmLabel || 'تأكيد';
    dialog.showModal();
});

dialog?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    if (target.matches('[data-confirm-accept]') && pendingForm) {
        const form = pendingForm;
        pendingForm = null;
        dialog.close();
        // form.submit() bypasses the submit event, so this doesn't re-trigger the confirm.
        form.submit();
    } else if (target.matches('[data-confirm-cancel]') || target === dialog) {
        pendingForm = null;
        dialog.close();
    }
});

// Header account menu: a native <details>, closed on outside click or Escape.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
        if (!menu.contains(event.target)) menu.removeAttribute('open');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
        menu.removeAttribute('open');
        menu.querySelector('summary')?.focus();
    });
});

// Sidebar badges (new contact requests, pending upgrades) refresh on their own.
const pollUrl = document.querySelector('meta[name="poll-url"]')?.content;

async function refreshBadges() {
    if (!pollUrl || document.hidden) return;
    try {
        const response = await fetch(pollUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) return;
        const counts = await response.json();
        document.querySelectorAll('[data-badge]').forEach((badge) => {
            const n = Number(counts[badge.dataset.badge] || 0);
            badge.textContent = n > 99 ? '99+' : String(n);
            badge.hidden = n === 0;
        });
    } catch (e) {
        // Offline: keep the last known counts.
    }
}

refreshBadges();
setInterval(refreshBadges, 15000);
document.addEventListener('visibilitychange', refreshBadges);

// Appearance toggle: system -> light -> dark -> system. theme-init.js applies it.
const themeToggle = document.getElementById('theme-toggle');

function showThemeIcon() {
    const mode = document.documentElement.dataset.themeMode || 'system';
    document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
        icon.classList.toggle('hidden', icon.dataset.themeIcon !== mode);
    });
}

showThemeIcon();
themeToggle?.addEventListener('click', () => {
    const order = ['system', 'light', 'dark'];
    const current = document.documentElement.dataset.themeMode || 'system';
    window.__setThemeMode?.(order[(order.indexOf(current) + 1) % order.length]);
    showThemeIcon();
});