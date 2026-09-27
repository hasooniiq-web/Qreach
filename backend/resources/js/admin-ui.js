// Delegated handlers replacing the inline onchange/onsubmit attributes the
// admin views used to carry. The app's CSP is `script-src 'self'` (no
// 'unsafe-inline'), so inline event-handler attributes never ran. Behavior is
// declared with data attributes and handled here.

document.addEventListener('change', (event) => {
    const target = event.target;
    if (target instanceof HTMLElement && target.matches('[data-auto-submit]')) {
        target.form?.submit();
    }
});

// ---- Confirmation dialog ------------------------------------------------
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

// ---- Submit buttons show a spinner and ignore double clicks -------------
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || !(form instanceof HTMLFormElement)) return;

    const button = event.submitter instanceof HTMLElement ? event.submitter : form.querySelector('[type="submit"]');
    if (!(button instanceof HTMLElement) || button.hasAttribute('data-no-loading')) return;

    // Deferred so the submission itself is not blocked by the disabled state.
    setTimeout(() => button.setAttribute('data-loading', ''), 0);
});

// Back/forward cache restores the page as it was; clear any spinner.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-loading]').forEach((el) => el.removeAttribute('data-loading'));
});

// ---- Dropdown menus: native <details>, closed on outside click / Escape --
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

// ---- Toasts dismiss themselves ------------------------------------------
function dismissToast(toast) {
    toast.setAttribute('data-leaving', '');
    setTimeout(() => toast.remove(), 220);
}

document.querySelectorAll('[data-toast]').forEach((toast) => {
    setTimeout(() => dismissToast(toast), 5000);
});

document.addEventListener('click', (event) => {
    const close = event.target instanceof Element ? event.target.closest('[data-toast-close]') : null;
    if (close) dismissToast(close.closest('[data-toast]'));
});

// ---- Live counts: sidebar badges and the notification bell ---------------
const pollUrl = document.querySelector('meta[name="poll-url"]')?.content;

async function refreshCounts() {
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

        let total = 0;
        document.querySelectorAll('[data-alert-count]').forEach((el) => {
            const n = Number(counts[el.dataset.alertCount] || 0);
            total += n;
            el.textContent = String(n);
            el.closest('[data-alert-row]')?.classList.toggle('hidden', n === 0);
        });
        document.querySelector('[data-bell-dot]')?.classList.toggle('hidden', total === 0);
        document.querySelector('[data-alert-empty]')?.classList.toggle('hidden', total > 0);
    } catch (e) {
        // Offline: keep the last known counts.
    }
}

refreshCounts();
setInterval(refreshCounts, 15000);
document.addEventListener('visibilitychange', refreshCounts);

// ---- Role permission picker: "select all" per group ----------------------
document.addEventListener('click', (event) => {
    const button = event.target instanceof Element ? event.target.closest('[data-select-group]') : null;
    if (!button) return;
    const boxes = document.querySelectorAll(`input[data-permission-group="${CSS.escape(button.dataset.selectGroup)}"]`);
    const allChecked = [...boxes].every((box) => box.checked);
    boxes.forEach((box) => { box.checked = !allChecked; });
});

// ---- Appearance menu (system / light / dark) ------------------------------
function showThemeState() {
    const mode = document.documentElement.dataset.themeMode || 'system';
    document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
        icon.classList.toggle('hidden', icon.dataset.themeIcon !== mode);
    });
    document.querySelectorAll('[data-theme-check]').forEach((check) => {
        check.classList.toggle('hidden', check.dataset.themeCheck !== mode);
    });
}

showThemeState();
document.addEventListener('click', (event) => {
    const option = event.target instanceof Element ? event.target.closest('[data-theme-set]') : null;
    if (!option) return;
    window.__setThemeMode?.(option.dataset.themeSet);
    showThemeState();
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => menu.removeAttribute('open'));
});
