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
