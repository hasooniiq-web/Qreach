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

// ---- Numbers with thousands separators ----------------------------------
// <input data-thousands> shows 1,000 as the admin types; the server strips the
// commas (App\Support\NumberInput), so either form is accepted.
const arabicDigits = { '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9' };

function formatThousands(input) {
    const digits = input.value.replace(/[٠-٩]/g, (d) => arabicDigits[d]).replace(/\D/g, '');
    input.value = digits === '' ? '' : Number(digits).toLocaleString('en-US');
}

// Live range check: while typing, a value outside [data-min, data-max] turns the box red,
// shows the reason under it (in the page language) and blocks submitting. Messages come
// from the server as data-*-message-ar / -en so the wording lives next to the rule.
const pageIsArabic = () => !(document.documentElement.lang || 'ar').toLowerCase().startsWith('en');

function validateRange(input) {
    const digits = input.value.replace(/[٠-٩]/g, (d) => arabicDigits[d]).replace(/\D/g, '');
    const value = digits === '' ? null : Number(digits);
    const max = input.dataset.max !== undefined ? Number(input.dataset.max) : null;
    const min = input.dataset.min !== undefined ? Number(input.dataset.min) : null;
    const lang = pageIsArabic() ? 'Ar' : 'En';

    let message = '';
    if (value !== null && max !== null && value > max) message = input.dataset['maxMessage' + lang] || '';
    else if (value !== null && min !== null && value < min) message = input.dataset['minMessage' + lang] || '';

    let box = input.parentElement.querySelector('[data-range-error]');
    if (message && !box) {
        box = document.createElement('p');
        box.setAttribute('data-range-error', '');
        box.setAttribute('role', 'alert');
        box.className = 'mt-1 text-xs font-medium text-status-danger';
        input.insertAdjacentElement('afterend', box);
        input.setAttribute('aria-describedby', ((input.getAttribute('aria-describedby') || '') + ' range-error-' + input.id).trim());
        box.id = 'range-error-' + input.id;
    }

    if (message) {
        input.setAttribute('aria-invalid', 'true');
        box.textContent = message;
        box.hidden = false;
    } else {
        input.removeAttribute('aria-invalid');
        if (box) box.hidden = true;
    }

    return message === '';
}

document.addEventListener('input', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement) || !target.matches('[data-thousands]')) return;
    formatThousands(target);
    validateRange(target);
});

// Values restored after a failed submit are checked on load too.
document.querySelectorAll('[data-thousands]').forEach((input) => validateRange(input));

// An out-of-range number never leaves the page; the first bad field is focused.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const bad = [...form.querySelectorAll('[data-thousands]')].find((input) => !validateRange(input));
    if (bad) {
        event.preventDefault();
        bad.focus();
    }
}, true);

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

    // Optional free-text reason (copied into the form's hidden field on accept).
    const reasonWrap = dialog.querySelector('[data-confirm-reason-wrap]');
    const reasonInput = dialog.querySelector('[data-confirm-reason]');
    dialog.querySelector('[data-confirm-reason-error]')?.classList.add('hidden');
    if (form.dataset.reasonName && reasonWrap && reasonInput) {
        dialog.querySelector('[data-confirm-reason-label]').textContent = form.dataset.reasonLabel || 'السبب';
        reasonInput.value = '';
        reasonWrap.classList.remove('hidden');
    } else {
        reasonWrap?.classList.add('hidden');
    }

    dialog.showModal();
    if (form.dataset.reasonName) reasonInput?.focus();
});

dialog?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    if (target.matches('[data-confirm-accept]') && pendingForm) {
        const form = pendingForm;

        if (form.dataset.reasonName) {
            const reason = dialog.querySelector('[data-confirm-reason]').value.trim();
            if (reason === '') {
                dialog.querySelector('[data-confirm-reason-error]').classList.remove('hidden');
                dialog.querySelector('[data-confirm-reason]').focus();
                return; // keep the dialog open until a reason is given
            }
            const field = form.querySelector(`[name="${form.dataset.reasonName}"]`);
            if (field) field.value = reason;
        }

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

// ---- Bulk selection: tick rows, then use the buttons above the table -------
function refreshBulk(form) {
    const items = [...form.querySelectorAll('[data-bulk-item]')];
    const chosen = items.filter((box) => box.checked).length;

    form.querySelectorAll('[data-bulk-count]').forEach((el) => { el.textContent = String(chosen); });
    form.querySelectorAll('[data-bulk-submit]').forEach((button) => { button.disabled = chosen === 0; });

    const all = form.querySelector('[data-bulk-all]');
    if (all) {
        all.checked = items.length > 0 && chosen === items.length;
        all.indeterminate = chosen > 0 && chosen < items.length;
    }
}

document.addEventListener('change', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) return;
    const form = target.closest('form[data-bulk]');
    if (!form) return;

    if (target.matches('[data-bulk-all]')) {
        form.querySelectorAll('[data-bulk-item]').forEach((box) => { box.checked = target.checked; });
    }
    if (target.matches('[data-bulk-all], [data-bulk-item]')) refreshBulk(form);
});

document.querySelectorAll('form[data-bulk]').forEach(refreshBulk);

// ---- Tables as cards on a phone: each cell learns its column name ---------
document.querySelectorAll('table.data-table').forEach((table) => {
    const names = [...table.querySelectorAll('thead th')].map((th) => (th.querySelector('.sr-only') ? '' : th.textContent.trim()));

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cells = [...row.children];
        // A "nothing here" row is one cell spanning every column: no name for it.
        if (cells.length !== names.length) return;
        cells.forEach((cell, i) => {
            if (names[i]) cell.setAttribute('data-label', names[i]);
        });
    });
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
